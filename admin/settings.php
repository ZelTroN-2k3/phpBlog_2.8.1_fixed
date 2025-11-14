<?php
include "header.php";

// --- MODIFICATION #1 : Logique de suppression d'image (BDD) CORRIGÉE ---
if (isset($_GET['delete_bgrimg'])) {
    validate_csrf_token_get();
    
    // S'assurer que le chemin est correct pour unlink
    $bgr_img_path = '../' . $settings['background_image'];
    if (file_exists($bgr_img_path) && is_file($bgr_img_path) && $settings['background_image'] != "") {
        @unlink($bgr_img_path); // Utiliser @ pour éviter les erreurs si le fichier est protégé
    }
    
    // Mettre à jour le tableau local
    $settings['background_image'] = '';
    
    // --- CORRECTION DE LA REQUÊTE ---
    // Mettre à jour la BDD en utilisant la nouvelle colonne 'background_image'
    $stmt = mysqli_prepare($connect, "UPDATE settings SET background_image = ? WHERE id = 1");
    $new_bg_empty = ""; // Créer une variable vide pour le bind_param
    mysqli_stmt_bind_param($stmt, "s", $new_bg_empty);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // --- FIN CORRECTION ---

    echo '<meta http-equiv="refresh" content="0;url=settings.php">';
    exit;
}
// --- FIN MODIFICATION #1 ---


// --- MODIFICATION #2 : Logique de sauvegarde (BDD) CORRIGÉE ---
if (isset($_POST['save'])) {

    validate_csrf_token();

    $uploadOk = 1;
    // Garder l'ancienne image par défaut, au cas où une nouvelle n'est pas uploadée
    $new_background_image = $settings['background_image']; 

    // --- VOTRE LOGIQUE D'UPLOAD (INCHANGÉE) ---
    if (isset($_FILES['background_image']) && $_FILES['background_image']['name'] != '') {
        $target_dir    = "../uploads/other/"; 
        $target_file   = $target_dir . basename($_FILES["background_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $string     = "0123456789wsderfgtyhjuk";
        $new_string = str_shuffle($string);
        $new_filename = "bgr_" . $new_string . "." . $imageFileType;
        $destination_path = $target_dir . $new_filename;

        $check = @getimagesize($_FILES["background_image"]["tmp_name"]);
        if ($check === false) {
            echo '<div class="alert alert-danger">Le fichier n\'est pas une image.</div>';
            $uploadOk = 0;
        }

        if ($_FILES["background_image"]["size"] > 10000000) { // 10MB
            echo '<div class="alert alert-warning">Désolé, votre fichier est trop volumineux.</div>';
            $uploadOk = 0;
        }

        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo '<div class="alert alert-warning">Désolé, seuls les fichiers JPG, JPEG, PNG & GIF sont autorisés.</div>';
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["background_image"]["tmp_name"], $destination_path)) {
                
                if (!empty($settings['background_image']) && file_exists('../' . $settings['background_image'])) {
                    @unlink('../' . $settings['background_image']);
                }
                
                $new_background_image = 'uploads/other/' . $new_filename;
                
            } else {
                echo '<div class="alert alert-danger">Erreur lors de l\'upload de l\'image.</div>';
                $uploadOk = 0;
            }
        }
    }
    // --- FIN DE VOTRE LOGIQUE D'UPLOAD ---


    // --- DÉBUT DE LA NOUVELLE LOGIQUE DE SAUVEGARDE BDD ---
    if ($uploadOk == 1) {
        
        try {
            // 1. Préparer UNE SEULE requête UPDATE pour toutes les colonnes
            // AJOUT de sticky_header
            $sql = "UPDATE settings SET 
                        site_url = ?, sitename = ?, description = ?, email = ?, 
                        gcaptcha_sitekey = ?, gcaptcha_secretkey = ?, head_customcode = ?, 
                        head_customcode_enabled = ?,
                        facebook = ?, instagram = ?, twitter = ?, youtube = ?, 
                        linkedin = ?, rtl = ?, date_format = ?, 
                        layout = ?, latestposts_bar = ?, homepage_slider = ?, sidebar_position = ?, 
                        posts_per_row = ?, theme = ?, posts_per_page = ?, 
                        background_image = ?, 
                        meta_title = ?, favicon_url = ?, apple_touch_icon_url = ?,
                        meta_author = ?, meta_generator = ?, meta_robots = ?,
                        sticky_header = ? -- <--- CHAMP AJOUTÉ
                    WHERE id = 1";
                    
            $stmt = mysqli_prepare($connect, $sql);

            if ($stmt === false) {
                throw new Exception("MySQL prepare error: " . mysqli_error($connect));
            }

            // 2. Définir les types (maintenant 30 colonnes = 30 's')
            $types = "ssssssssssssssssssssssssssssss"; // 30 's'
            
            // 3. Encoder le 'head_customcode' en base64
            $head_customcode_encoded = base64_encode($_POST['head_customcode']);

            // 4. Lier tous les paramètres (dans le bon ordre)
            mysqli_stmt_bind_param($stmt, $types,
                $_POST['site_url'],
                $_POST['sitename'],
                $_POST['description'],
                $_POST['email'],
                $_POST['gcaptcha_sitekey'],
                $_POST['gcaptcha_secretkey'],
                $head_customcode_encoded,
                $_POST['head_customcode_enabled'], 
                $_POST['facebook'],
                $_POST['instagram'],
                $_POST['twitter'],
                $_POST['youtube'],
                $_POST['linkedin'],
                $_POST['rtl'],
                $_POST['date_format'],
                $_POST['layout'],
                $_POST['latestposts_bar'],
                $_POST['homepage_slider'],
                $_POST['sidebar_position'],
                $_POST['posts_per_row'],
                $_POST['theme'],
                $_POST['posts_per_page'],
                $new_background_image,
                $_POST['meta_title'],
                $_POST['favicon_url'],
                $_POST['apple_touch_icon_url'],
                $_POST['meta_author'],
                $_POST['meta_generator'],
                $_POST['meta_robots'],
                $_POST['sticky_header'] 
            );

            // 5. Exécuter et fermer
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 6. Afficher le message de succès (le vôtre)
            echo '
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                Settings have been successfully saved.
            </div>';
            
            // 7. Re-charger les settings (important)
            $stmt_reload = mysqli_prepare($connect, "SELECT * FROM settings WHERE id = 1");
            mysqli_stmt_execute($stmt_reload);
            $result_reload = mysqli_stmt_get_result($stmt_reload);
            $settings = mysqli_fetch_assoc($result_reload);
            mysqli_stmt_close($stmt_reload);

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erreur lors de la sauvegarde: ' . $e->getMessage() . '</div>';
        }
    }
    // --- FIN DE LA NOUVELLE LOGIQUE DE SAUVEGARDE BDD ---
}
// --- FIN MODIFICATION #2 ---
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-cogs"></i> Site Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Site Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tools"></i> General Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-globe text-primary"></i> Site Name</label>
                                        <input type="text" name="sitename" class="form-control" value="<?php echo htmlspecialchars($settings['sitename']); ?>" required>
                                    </div>
                                </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-thumbtack text-primary"></i> Menu collant (Sticky Header)</label>
                                            <select name="sticky_header" class="form-control" required>
                                                <option value="On" <?php if ($settings['sticky_header'] == 'On') echo 'selected'; ?>>On</option>
                                                <option value="Off" <?php if ($settings['sticky_header'] == 'Off') echo 'selected'; ?>>Off</option>
                                            </select>
                                        </div>
                                    </div>  
                            </div>                          
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-link text-primary"></i> Site URL</label>
                                        <input type="text" name="site_url" class="form-control" value="<?php echo htmlspecialchars($settings['site_url']); ?>" placeholder="Ex: https://www.votresite.com (sans / à la fin)" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-envelope text-primary"></i> Site E-Mail</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($settings['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-calendar-alt text-primary"></i> Date Format</label>
                                        <select name="date_format" class="form-control" required>
                                            <option value="d.m.Y" <?php if ($settings['date_format'] == "d.m.Y") { echo 'selected'; } ?>><?php echo date("d.m.Y"); ?></option>
                                            <option value="m.d.Y" <?php if ($settings['date_format'] == "m.d.Y") { echo 'selected'; } ?>><?php echo date("m.d.Y"); ?></option>
                                            <option value="Y.m.d" <?php if ($settings['date_format'] == "Y.m.d") { echo 'selected'; } ?>><?php echo date("Y.m.d"); ?></option>
                                            <option disabled>───────────</option>
                                            <option value="d F Y" <?php if ($settings['date_format'] == "d F Y") { echo 'selected'; } ?>><?php echo date("d F Y"); ?></option>
                                            <option value="F j, Y" <?php if ($settings['date_format'] == "F j, Y") { echo 'selected'; } ?>><?php echo date("F j, Y"); ?></option>
                                            <option value="Y F j" <?php if ($settings['date_format'] == "Y F j") { echo 'selected'; } ?>><?php echo date("Y F j"); ?></option>
                                            <option disabled>───────────</option>
                                            <option value="d-m-Y" <?php if ($settings['date_format'] == "d-m-Y") { echo 'selected'; } ?>><?php echo date("d-m-Y"); ?></option>
                                            <option value="m-d-Y" <?php if ($settings['date_format'] == "m-d-Y") { echo 'selected'; } ?>><?php echo date("m-d-Y"); ?></option>
                                            <option value="Y-m-d" <?php if ($settings['date_format'] == "Y-m-d") { echo 'selected'; } ?>><?php echo date("Y-m-d"); ?></option>
                                            <option disabled>───────────</option>
                                            <option value="d/m/Y" <?php if ($settings['date_format'] == "d/m/Y") { echo 'selected'; } ?>><?php echo date("d/m/Y"); ?></option>
                                            <option value="m/d/Y" <?php if ($settings['date_format'] == "m/d/Y") { echo 'selected'; } ?>><?php echo date("m/d/Y"); ?></option>
                                            <option value="Y/m/d" <?php if ($settings['date_format'] == "Y/m/d") { echo 'selected'; } ?>><?php echo date("Y/m/d"); ?></option>
                                        </select>
                                    </div>
                                </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-list-ol text-primary"></i> Posts per page (Blog)</label>
                                            <input type="number" name="posts_per_page" class="form-control" value="<?php echo (int) $settings['posts_per_page']; ?>" min="1" required>
                                        </div>
                                    </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-columns text-primary"></i> Sidebar Position</label>
                                        <select name="sidebar_position" class="form-control" required>
                                            <option value="Left" <?php if ($settings['sidebar_position'] == 'Left') echo 'selected'; ?>>Left</option>
                                            <option value="Right" <?php if ($settings['sidebar_position'] == 'Right') echo 'selected'; ?>>Right</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-desktop text-primary"></i> Layout</label>
                                        <select name="layout" class="form-control" required>
                                            <option value="Wide" <?php if ($settings['layout'] == 'Wide') echo 'selected'; ?>>Wide</option>
                                            <option value="Fixed" <?php if ($settings['layout'] == 'Fixed') echo 'selected'; ?>>Fixed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-palette text-primary"></i> Theme</label>
                                        <select name="theme" class="form-control" required>
                                            <option value="Bootstrap 5" <?php if ($settings['theme'] == 'Bootstrap 5') echo 'selected'; ?>>Bootstrap 5 (Default)</option>
                                            <option value="Cerulean" <?php if ($settings['theme'] == 'Cerulean') echo 'selected'; ?>>Cerulean</option>
                                            <option value="Cosmo" <?php if ($settings['theme'] == 'Cosmo') echo 'selected'; ?>>Cosmo</option>
                                            <option value="Cyborg" <?php if ($settings['theme'] == 'Cyborg') echo 'selected'; ?>>Cyborg</option>
                                            <option value="Flatly" <?php if ($settings['theme'] == 'Flatly') echo 'selected'; ?>>Flatly</option>
                                            <option value="Journal" <?php if ($settings['theme'] == 'Journal') echo 'selected'; ?>>Journal</option>
                                            <option value="Litera" <?php if ($settings['theme'] == 'Litera') echo 'selected'; ?>>Litera</option>
                                            <option value="Lumen" <?php if ($settings['theme'] == 'Lumen') echo 'selected'; ?>>Lumen</option>
                                            <option value="Lux" <?php if ($settings['theme'] == 'Lux') echo 'selected'; ?>>Lux</option>
                                            <option value="Materia" <?php if ($settings['theme'] == 'Materia') echo 'selected'; ?>>Materia</option>
                                            <option value="Minty" <?php if ($settings['theme'] == 'Minty') echo 'selected'; ?>>Minty</option>
                                            <option value="Morph" <?php if ($settings['theme'] == 'Morph') echo 'selected'; ?>>Morph</option>
                                            <option value="Pulse" <?php if ($settings['theme'] == 'Pulse') echo 'selected'; ?>>Pulse</option>
                                            <option value="Quartz" <?php if ($settings['theme'] == 'Quartz') echo 'selected'; ?>>Quartz</option>
                                            <option value="Sandstone" <?php if ($settings['theme'] == 'Sandstone') echo 'selected'; ?>>Sandstone</option>
                                            <option value="Simplex" <?php if ($settings['theme'] == 'Simplex') echo 'selected'; ?>>Simplex</option>
                                            <option value="Sketchy" <?php if ($settings['theme'] == 'Sketchy') echo 'selected'; ?>>Sketchy</option>
                                            <option value="Solar" <?php if ($settings['theme'] == 'Solar') echo 'selected'; ?>>Solar</option>
                                            <option value="Spacelab" <?php if ($settings['theme'] == 'Spacelab') echo 'selected'; ?>>Spacelab</option>
                                            <option value="United" <?php if ($settings['theme'] == 'United') echo 'selected'; ?>>United</option>
                                            <option value="Vapor" <?php if ($settings['theme'] == 'Vapor') echo 'selected'; ?>>Vapor</option>
                                            <option value="Yeti" <?php if ($settings['theme'] == 'Yeti') echo 'selected'; ?>>Yeti</option>
                                            <option value="Zephyr" <?php if ($settings['theme'] == 'Zephyr') echo 'selected'; ?>>Zephyr</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-newspaper text-primary"></i> Latest Posts Bar</label>
                                        <select name="latestposts_bar" class="form-control" required>
                                            <option value="Enabled" <?php if ($settings['latestposts_bar'] == 'Enabled') echo 'selected'; ?>>Enabled</option>
                                            <option value="Disabled" <?php if ($settings['latestposts_bar'] == 'Disabled') echo 'selected'; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            <hr>
                                <div class="form-group">
                                    <label>Homepage Slider Type</label>
                                    <select name="homepage_slider" class="form-control">
                                        <option value="Featured" <?php if($settings['homepage_slider'] == 'Featured') { echo 'selected'; } ?>>Featured Posts Slider (Default)</option>
                                        <option value="Custom" <?php if($settings['homepage_slider'] == 'Custom') { echo 'selected'; } ?>>Custom Slider</option>
                                    </select>
                                    <small class="text-muted">Choose between the default "Featured Posts" slider or the new "Custom Slider" module.</small>
                                </div>                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-align-right text-primary"></i> Right-to-Left (RTL)</label>
                                        <select name="rtl" class="form-control" required>
                                            <option value="Yes" <?php if ($settings['rtl'] == 'Yes') echo 'selected'; ?>>Yes</option>
                                            <option value="No" <?php if ($settings['rtl'] == 'No') echo 'selected'; ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-grip-horizontal text-primary"></i> Homepage posts per row</label>
                                        <select name="posts_per_row" class="form-control" required>
                                            <option value="2" <?php
                                            if ($settings['posts_per_row'] == "2") {
                                                echo 'selected';
                                            }
                                            ?>>2</option>
                                            <option value="3" <?php
                                            if ($settings['posts_per_row'] == "3") {
                                                echo 'selected';
                                            }
                                            ?>>3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> SEO & Méta-tags</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><i class="fas fa-heading text-primary"></i> Titre principal (Title & og:title)</label>
                                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($settings['meta_title']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><i class="fas fa-align-left text-primary"></i> Méta Description (et og:description)</label>
                                        <textarea name="description" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-images text-primary"></i> URL du Favicon</label>
                                        <input type="text" name="favicon_url" class="form-control" value="<?php echo htmlspecialchars($settings['favicon_url']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fab fa-apple text-primary"></i> URL de l'Apple Touch Icon</label>
                                        <input type="text" name="apple_touch_icon_url" class="form-control" value="<?php echo htmlspecialchars($settings['apple_touch_icon_url']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user-edit text-primary"></i> Méta Author</label>
                                        <input type="text" name="meta_author" class="form-control" value="<?php echo htmlspecialchars($settings['meta_author']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-cogs text-primary"></i> Méta Generator</label>
                                        <input type="text" name="meta_generator" class="form-control" value="<?php echo htmlspecialchars($settings['meta_generator']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><i class="fas fa-robot text-primary"></i> Méta Robots</label>
                                        <input type="text" name="meta_robots" class="form-control" value="<?php echo htmlspecialchars($settings['meta_robots']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-share-alt"></i> Social Media Links</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fab fa-facebook-square text-primary"></i> Facebook</label>
                                        <input type="text" name="facebook" class="form-control" value="<?php echo htmlspecialchars($settings['facebook']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fab fa-instagram text-danger"></i> Instagram</label>
                                        <input type="text" name="instagram" class="form-control" value="<?php echo htmlspecialchars($settings['instagram']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fab fa-twitter-square text-info"></i> Twitter</label>
                                        <input type="text" name="twitter" class="form-control" value="<?php echo htmlspecialchars($settings['twitter']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fab fa-youtube text-danger"></i> YouTube</label>
                                        <input type="text" name="youtube" class="form-control" value="<?php echo htmlspecialchars($settings['youtube']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fab fa-linkedin text-primary"></i> LinkedIn</label>
                                        <input type="text" name="linkedin" class="form-control" value="<?php echo htmlspecialchars($settings['linkedin']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Security & Integration</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-key text-success"></i> reCAPTCHA Site Key</label>
                                        <input type="text" name="gcaptcha_sitekey" class="form-control" value="<?php echo htmlspecialchars($settings['gcaptcha_sitekey']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user-secret text-success"></i> reCAPTCHA Secret Key</label>
                                        <input type="text" name="gcaptcha_secretkey" class="form-control" value="<?php echo htmlspecialchars($settings['gcaptcha_secretkey']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-code text-warning"></i> Head Custom Code (Analytics, etc.)</label>
                                <textarea name="head_customcode" class="form-control" rows="4"><?php echo htmlspecialchars(base64_decode($settings['head_customcode'])); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success"></i> Activate the custom code (Head Custom Code)</label>
                                <select name="head_customcode_enabled" class="form-control" required>
                                    <option value="On" <?php if ($settings['head_customcode_enabled'] == 'On') echo 'selected'; ?>>On</option>
                                    <option value="Off" <?php if ($settings['head_customcode_enabled'] == 'Off') echo 'selected'; ?>>Off</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-image"></i> Background Image</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <?php
                                if ($settings['background_image'] != "") {
                                    // Assurer que le token CSRF est dans l'URL de suppression
                                    $delete_url = "?delete_bgrimg&token=" . $_SESSION['csrf_token'];
                                    echo '<div class="row align-items-center mb-2">
                                        <div class="col-md-4">
                                            <img src="../' . htmlspecialchars($settings['background_image']) . '" class="img-fluid" style="max-height: 120px; border-radius: 5px;" />
                                        </div>
                                        <div class="col-md-8">
                                            <a href="' . $delete_url . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this image?\');">
                                                <i class="fas fa-trash"></i> Delete Image
                                            </a>
                                        </div>
                                    </div>';
                                }
                                ?>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input name="background_image" class="custom-file-input" type="file" id="formFile">
                                        <label class="custom-file-label" for="formFile"><i class="fas fa-file-upload"></i> Choose file</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </form>                           
                </div>   
            </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>