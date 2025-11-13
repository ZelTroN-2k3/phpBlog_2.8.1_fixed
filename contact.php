<?php
include "core.php";
head();

// Initialisation des variables
$error = "";
$success = "";

// --- TRAITEMENT DU FORMULAIRE ---
if (isset($_POST['send'])) {
    
    // 1. Vérification CSRF (Sécurité)
    validate_csrf_token();

    // 2. Nettoyage et Validation des Entrées
    $name    = strip_tags(trim($_POST['name']));
    $email   = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = strip_tags(trim($_POST['subject']));
    $message = strip_tags(trim($_POST['message']));

    // 3. Vérifications de base
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> All fields are required.</div>';
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '<div class="alert alert-danger"><i class="fas fa-at"></i> Invalid email address.</div>';
    } 
    else {
        // 4. Vérification ReCAPTCHA (Anti-Robot)
        $captcha_valid = false;
        if (!empty($settings['gcaptcha_secretkey']) && !empty($_POST['g-recaptcha-response'])) {
            $secretKey = $settings['gcaptcha_secretkey'];
            $responseKey = $_POST['g-recaptcha-response'];
            $userIP = $_SERVER['REMOTE_ADDR'];
            
            $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$responseKey&remoteip=$userIP";
            $response = file_get_contents($url);
            $responseKeys = json_decode($response, true);
            
            if ($responseKeys["success"]) {
                $captcha_valid = true;
            }
        } elseif (empty($settings['gcaptcha_secretkey'])) {
            // Si pas de clé configurée, on laisse passer (mode développement)
            $captcha_valid = true; 
        }

        if (!$captcha_valid) {
            $error = '<div class="alert alert-warning"><i class="fas fa-robot"></i> Captcha validation failed. Are you a robot?</div>';
        } 
        else {
            // 5. Enregistrement en Base de Données (Pour l'admin)
            $stmt = mysqli_prepare($connect, "INSERT INTO messages (name, email, content, viewed, created_at) VALUES (?, ?, ?, 'No', NOW())");
            
            // On combine sujet + message pour le stockage
            $full_content = "Sujet: $subject\n\n$message";
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $full_content);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 6. Envoi de l'Email (Fonction mail() sécurisée)
            $to      = $settings['email']; // L'email défini dans les paramètres du site
            $headers = "From: " . $settings['sitename'] . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Priority: 3\r\n";
            $headers .= "X-Mailer: PHP" . phpversion() . "\r\n";

            $email_subject = "[" . $settings['sitename'] . "] New message: $subject";
            $email_body    = "You have received a new message from the contact form.\n\n";
            $email_body   .= "Nom : $name\n";
            $email_body   .= "Email : $email\n";
            $email_body   .= "Sujet : $subject\n\n";
            $email_body   .= "Message :\n$message\n\n";
            $email_body   .= "--------------------------------------------------\n";
            $email_body   .= "This message has been logged in your administration panel.";

            // Tentative d'envoi
            if (@mail($to, $email_subject, $email_body, $headers)) {
                $success = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Your message has been sent successfully. We will get back to you as soon as possible.</div>';
                
                // Vider les champs après succès
                $name = $email = $subject = $message = ""; 
            } else {
                // Fallback : Si mail() échoue (ex: localhost), on confirme quand même l'enregistrement BDD
                $success = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Your message has been saved (Mail server error, but saved in the database).</div>';
            }
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-envelope"></i> Contact Us</h4>
                </div>
                <div class="card-body">
                    
                    <p class="text-muted mb-4">
                        Have a question, suggestion, or partnership? Please fill out the form below.
                    </p>

                    <?php echo $error; ?>
                    <?php echo $success; ?>

                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>

                        <?php if (!empty($settings['gcaptcha_sitekey'])): ?>
                            <div class="mb-3 d-flex justify-content-center">
                                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($settings['gcaptcha_sitekey']); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" name="send" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle text-primary"></i> Informations</h5>
                    <hr>
                    <p><strong>Email :</strong><br> <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>"><?php echo htmlspecialchars($settings['email']); ?></a></p>
                    
                    <?php if($settings['facebook'] || $settings['twitter'] || $settings['instagram']): ?>
                    <p><strong>Réseaux Sociaux :</strong></p>
                    <div class="d-flex gap-2">
                        <?php if($settings['facebook']): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" class="btn btn-outline-primary btn-sm"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if($settings['twitter']): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter']); ?>" class="btn btn-outline-info btn-sm"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if($settings['instagram']): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" class="btn btn-outline-danger btn-sm"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
footer();
?>