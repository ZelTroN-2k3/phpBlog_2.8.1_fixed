<?php
include "core.php";
head();

if ($logged == 'Yes') {
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit;
}

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

$error = 0;

// --- NOUVEL AJOUT : Initialisation du Rate Limiting ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['login_lockout_time'])) {
    $_SESSION['login_lockout_time'] = 0;
}
// --- FIN AJOUT ---

?>
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><i class="fas fa-user-plus"></i> Membership</div>
                <div class="card-body">

                    <div class="row">
						<div class="col-md-6 mb-4 border-end">
                        <h5 class="mb-3"><i class="fas fa-sign-in-alt"></i> Sign In</h5>
                                                    
                        <div class="mb-3">
                            <p class="text-center">Connect quickly with:</p>
                            <div class="d-grid gap-2">
                                <a href="social_callback.php?provider=Google" class="btn btn-danger">
                                    <i class="fab fa-google"></i> &nbsp; Sign in with Google
                                </a>
                                </div>
                            <hr>
                            <p class="text-center">Or with your account:</p>
                        </div>
<?php
// --- NOUVEL AJOUT : Vérification du blocage ---
$is_locked_out = false;
if ($_SESSION['login_lockout_time'] > time()) {
    $is_locked_out = true;
    $time_remaining = ceil(($_SESSION['login_lockout_time'] - time()) / 60);
    echo '
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> You failed too many times. Please try again in ' . $time_remaining . ' minute(s).
    </div>';
    $error = 1; // Pour désactiver le formulaire
}
// --- FIN AJOUT ---


if (isset($_POST['signin']) && !$is_locked_out) { // Ne traiter que si non bloqué
    
    // --- Validation CSRF ---
    validate_csrf_token();
    // --- FIN ---
    
    $username = $_POST['username'];
    $password_plain = $_POST['password']; // Mot de passe en clair
    
    // 1. Récupérer le hash du mot de passe pour cet utilisateur
    $stmt = mysqli_prepare($connect, "SELECT username, password FROM `users` WHERE `username`=?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user_row = mysqli_fetch_assoc($result);
        $hashed_password = $user_row['password'];

        // 2. Vérifier le mot de passe en clair contre le hash
        if (password_verify($password_plain, $hashed_password)) {
            // Le mot de passe est correct !
            
            // --- NOUVEL AJOUT : Réinitialiser le compteur ---
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_lockout_time'] = 0;
            // --- FIN AJOUT ---
            
            $_SESSION['sec-username'] = $username;
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
        } else {
            // Mot de passe incorrect
            
            // --- NOUVEL AJOUT : Logique d'échec Rate Limiting ---
            $_SESSION['login_attempts']++;
            $attempts_remaining = 5 - $_SESSION['login_attempts'];
            
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lockout_time'] = time() + 300; // Bloquer pour 5 minutes (300 secondes)
                echo '
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> You failed 5 times. Please try again in 5 minutes.
                </div>';
            } else {
                 echo '
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> The <strong>username</strong> or <strong>password</strong> is incorrect.<br>
                    You have ' . $attempts_remaining . ' attempt(s) left.
                </div>';
            }
            // --- FIN AJOUT ---
            
            $error = 1;
        }
    } else {
        // Utilisateur non trouvé (on le compte aussi comme un échec)
        
        // --- NOUVEL AJOUT : Logique d'échec Rate Limiting ---
        $_SESSION['login_attempts']++;
        $attempts_remaining = 5 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_lockout_time'] = time() + 300; // Bloquer pour 5 minutes
             echo '
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> You failed 5 times. Please try again in 5 minutes.
            </div>';
        } else {
            echo '
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> The <strong>username</strong> or <strong>password</strong> is incorrect.<br>
                You have ' . $attempts_remaining . ' attempt(s) left.
            </div>';
        }
        // --- FIN AJOUT ---
        
        $error = 1;
    }
}
?> 
			<form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="username" name="username" class="form-control" placeholder="Username" <?php
if ($error == 1) {
    echo 'autofocus';
}
?> required <?php if ($is_locked_out) echo 'disabled'; ?>>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Password" required <?php if ($is_locked_out) echo 'disabled'; ?>>
                </div>

                <button type="submit" name="signin" class="btn btn-primary col-12" <?php if ($is_locked_out) echo 'disabled'; ?>><i class="fas fa-sign-in-alt"></i>
    &nbsp;Sign In</button>

            </form> 
						</div>
						<div class="col-md-6">
							<h5 class="mb-3"><i class="fas fa-user-plus"></i> Registration</h5>
                <?php
if (isset($_POST['register'])) {
    
    // --- Validation CSRF ---
    validate_csrf_token();
    // --- FIN ---
    
    $username = $_POST['username'];
    // MODIFICATION : Utiliser password_hash()
    $password = $_POST['password'];
    $email    = $_POST['email'];
    $captcha  = '';
    
    // Validation du côté PHP
    $registration_error = false;
    
    if (isset($_POST['g-recaptcha-response'])) {
        $captcha = $_POST['g-recaptcha-response'];
    }

    if (empty($captcha)) {
        echo '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Please complete the reCAPTCHA.</div>';
        $registration_error = true;
    }

    if (!$registration_error) {
        $url          = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
        $response     = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        
        if (!$responseKeys["success"]) {
            echo '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Failed to verify reCAPTCHA.</div>';
            $registration_error = true;
        }
    }
    
    if (!$registration_error) {
        // Use prepared statement for username check
        $stmt = mysqli_prepare($connect, "SELECT username FROM `users` WHERE username=?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if (mysqli_num_rows($result) > 0) {
            echo '<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> The username is taken.</div>';
            $registration_error = true;
        } else {
            
            // Use prepared statement for email check
            $stmt = mysqli_prepare($connect, "SELECT email FROM `users` WHERE email=?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result2 = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            if (mysqli_num_rows($result2) > 0) {
                echo '<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> The E-Mail Address is taken</div>';
                $registration_error = true;
            } else {
                
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);

                // Use prepared statement for user insert
                $stmt = mysqli_prepare($connect, "INSERT INTO `users` (`username`, `password`, `email`) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $username, $password_hashed, $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Use prepared statement for newsletter insert
                $stmt = mysqli_prepare($connect, "INSERT INTO `newsletter` (`email`) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                $subject = 'Welcome at ' . $settings['sitename'] . '';
                $message_email = '<a href="' . $settings['site_url'] . '" title="Visit ' . $settings['sitename'] . '" target="_blank">
                                <h4>' . $settings['sitename'] . '</h4>
                            </a><br />

                            <h5>You have successfully registered at ' . $settings['sitename'] . '</h5><br /><br />

                            <b>Registration details:</b><br />
                            Username: <b>' . htmlspecialchars($username) . '</b>';
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $headers .= 'To: ' . $email . ' <' . $email . '>' . "\r\n";
                $headers .= 'From: ' . $settings['email'] . ' <' . $settings['email'] . '>' . "\r\n";
                @mail($email, $subject, $message_email, $headers);
                
                $_SESSION['sec-username'] = $username;
                echo '<meta http-equiv="refresh" content="0;url=profile">';
            }
        }
    }
}
?>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="username" name="username" class="form-control" placeholder="Username" required>
                </div>
				<div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="E-Mail Address" required>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
				<div class="g-recaptcha mb-3" data-sitekey="<?php
echo $settings['gcaptcha_sitekey'];
?>"></div>

                <button type="submit" name="register" class="btn btn-success col-12 mt-2"><i class="fas fa-sign-in-alt"></i>
    &nbsp;Sign Up</button>
            </form> 
						</div>
						</div>
                </div>
        </div>
    </div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>