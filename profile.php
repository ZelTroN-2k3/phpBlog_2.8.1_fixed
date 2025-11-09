<?php
include "core.php";
// require_once 'vendor/htmlpurifier/library/HTMLPurifier.auto.php';
head();

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><i class="fas fa-cog"></i> Account Settings</div>
                    <div class="card-body">
<?php
$uname   = $_SESSION['sec-username'];
$user_id = $rowu['id'];
$message = '';

if (isset($_POST['save'])) {
    
    // --- Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $email    = $_POST['email'];
    $username = $_POST['username'];
    $avatar   = $rowu['avatar'];
    $password = $_POST['password']; // C'est le mot de passe en clair
    
    // --- MODIFICATION : Récupérer et nettoyer les champs ---
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    $bio = $purifier->purify($_POST['bio']);

    // Valider l'URL du site web
    $website = filter_var($_POST['website'] ?? '', FILTER_VALIDATE_URL);
    if ($website === false) {
        $website = ''; // Si l'URL n'est pas valide, la sauvegarder comme une chaîne vide
    }

    $location = htmlspecialchars($_POST['location'] ?? '');
    // --- FIN MODIFICATION ---
    
    $emused = 'No';
    
    // Use prepared statement for email check
    $stmt = mysqli_prepare($connect, "SELECT id FROM `users` WHERE email=? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $emused = 'Yes';
    }
    
    $unused = 'No';
    
    // Use prepared statement for username check
    $stmt = mysqli_prepare($connect, "SELECT id FROM `users` WHERE username=? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $unused = 'Yes';
    }
    
    if ($emused == 'Yes') {
        $message = '<div class="alert alert-danger">This E-Mail Address is already used by another user.</div>';
    } elseif ($unused == 'Yes') {
        $message = '<div class="alert alert-danger">This Username is already used by another user.</div>';
    } elseif (strlen($username) < 3) {
        $message = '<div class="alert alert-danger">Username must be at least 3 characters long.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Please enter a valid E-Mail Address.</div>';
    } else {
        
        // Gérer l'upload de l'avatar
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['name'] != '') {
            $avatar_file = $_FILES['avatar_file'];
            $extensions  = array(
                "image/jpeg",
                "image/jpg",
                "image/png"
            );
            $max_size    = 1024 * 1024; // 1 MB
            $avatar_path = 'uploads/avatars/';
            
            if ($avatar_file['size'] > $max_size) {
                $message = '<div class="alert alert-danger">The file is too large. Max size 1 MB.</div>';
            } elseif (!in_array($avatar_file['type'], $extensions)) {
                $message = '<div class="alert alert-danger">Invalid file type. Only JPEG and PNG are allowed.</div>';
            } else {
                // Supprimer l'ancien avatar s'il existe et n'est pas l'avatar par défaut
                if ($avatar != 'assets/img/avatar.png' && file_exists($avatar)) {
                    @unlink($avatar);
                }
                
                // --- MODIFICATION ---
                // Chemin SANS extension
                $destination_path_base = $avatar_path . $user_id . '_' . time() . '_' . pathinfo($avatar_file['name'], PATHINFO_FILENAME);
                
                // Les avatars sont petits, 300px max, qualité 90
                $optimized_path = optimize_and_save_image($avatar_file['tmp_name'], $destination_path_base, 300, 90);
                
                if ($optimized_path) {
                    $avatar = $optimized_path; // Le chemin est déjà correct (ex: 'uploads/avatars/...')
                } else {
                    $message = '<div class="alert alert-danger">An error occurred while processing the avatar.</div>';
                }
                // --- FIN MODIFICATION ---
            }
        }
        
        // --- MODIFICATION : Mettre à jour la requête UPDATE ---
        
        $update_query_sql = "UPDATE `users` SET email=?, username=?, avatar=?, bio=?, website=?, location=? WHERE id=?";
        $bind_types = "ssssssi";
        $bind_params = [$email, $username, $avatar, $bio, $website, $location, $user_id];
        
        // Vérifier si un nouveau mot de passe est fourni
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $message = '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Ajouter le mot de passe à la requête
                $update_query_sql = "UPDATE `users` SET email=?, username=?, avatar=?, bio=?, website=?, location=?, password=? WHERE id=?";
                $bind_types = "sssssssi";
                $bind_params = [$email, $username, $avatar, $bio, $website, $location, $hashed_password, $user_id];
            }
        }
        
        // Exécuter la mise à jour seulement s'il n'y a pas eu d'erreur de mot de passe
        if (empty($message)) {
            $stmt_update = mysqli_prepare($connect, $update_query_sql);
            
            // Passer les paramètres par référence pour bind_param
            $bind_refs = [$bind_types];
            foreach ($bind_params as $key => &$param) {
                $bind_refs[] = &$param;
            }
            call_user_func_array(array($stmt_update, 'bind_param'), $bind_refs);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $message = '<div class="alert alert-success">Your account settings have been updated.</div>';
                // Recharger les données utilisateur après la mise à jour
                $stmt_reload = mysqli_prepare($connect, "SELECT * FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt_reload, "i", $user_id);
                mysqli_stmt_execute($stmt_reload);
                $result_reload = mysqli_stmt_get_result($stmt_reload);
                $rowu = mysqli_fetch_assoc($result_reload);
                mysqli_stmt_close($stmt_reload);
            } else {
                $message = '<div class="alert alert-danger">An error occurred while updating settings.</div>';
            }
            mysqli_stmt_close($stmt_update);
        }
        // --- FIN MODIFICATION ---
    }
}
?>
                    <?php echo $message; ?>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group mb-3">
                            <label for="username"><i class="fa fa-user"></i> Username:</label>
                            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($rowu['username']); ?>" class="form-control" required />
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email"><i class="fa fa-envelope"></i> E-Mail Address:</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($rowu['email']); ?>" class="form-control" required />
                        </div>
                        
                        <div class="form-group mb-3">
							<label for="avatar_file"><i class="fa fa-image"></i> Avatar:</label><br />
                            <img src="<?php echo htmlspecialchars($rowu['avatar']); ?>" alt="Avatar" width="128" height="128" class="mb-2 img-thumbnail"><br />
							<input type="file" name="avatar_file" id="avatar_file" class="form-control" />
                            <small class="form-text text-muted">Max size 1 MB. Allowed formats: JPEG, PNG.</small>
						</div>

                        <div class="form-group mb-3">
                            <label for="bio"><i class="fa fa-info-circle"></i> Biography:</label>
                            <textarea name="bio" id="summernote" class="form-control"><?php
                            echo html_entity_decode($rowu['bio'] ?? '');
                            ?></textarea>
                            <small class="form-text text-muted">A short biography that will appear on your public profile.</small>
						</div>
                        
                        <div class="form-group mb-3">
                            <label for="website"><i class="fa fa-globe"></i> Website:</label>
                            <input type="url" name="website" id="website" value="<?php echo htmlspecialchars($rowu['website'] ?? ''); ?>" class="form-control" placeholder="https://yourwebsite.com" />
                            <small class="form-text text-muted">Your personal or professional website (optional).</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="location"><i class="fa fa-map-marker-alt"></i> Location:</label>
                            <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($rowu['location'] ?? ''); ?>" class="form-control" placeholder="Paris, France" />
                            <small class="form-text text-muted">Where are you based? (optional).</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name"><i class="fa fa-key"></i> Password:</label>
                            <input type="password" name="password" id="name" value="" class="form-control" />
                            <small class="form-text text-muted">Fill this field only if you want to change your password.</small>
						</div>

                        <div class="form-actions mt-4">
                            <input type="submit" name="save" class="btn btn-primary col-12" value="Update" />
                        </div>
                    </form>
                    </div>
			    </div>
		</div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>