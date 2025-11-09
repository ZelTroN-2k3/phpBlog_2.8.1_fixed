<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
        <div class="col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><i class="fas fa-envelope"></i> Contact</div>
                    <div class="card-body">

                    <h5 class="mb-3"><i class="fas fa-share-alt"></i> Social Profiles</h5>
                        <div class="list-group mb-4">
							<a class="list-group-item list-group-item-action list-group-item-light" href="mailto:<?php
echo htmlspecialchars($settings['email']);
?>" target="_blank"><i class="fa fa-envelope"></i><span>&nbsp; E-Mail: <strong><?php
echo htmlspecialchars($settings['email']);
?></strong></span></a>
<?php
if ($settings['facebook'] != '') {
?>
							<a class="list-group-item list-group-item-primary list-group-item-action" href="<?php
echo htmlspecialchars($settings['facebook']);
?>" target="_blank"><strong><i class="fab fa-facebook-square"></i>&nbsp; Facebook</strong></a>
<?php
}
if ($settings['instagram'] != '') {
?>
							<a class="list-group-item list-group-item-warning list-group-item-action" href="<?php
echo htmlspecialchars($settings['instagram']);
?>" target="_blank" style="background-color: #e8d098!important; border-color: #e8d098!important;"><strong><i class="fab fa-instagram"></i>&nbsp; Instagram</strong></a>
<?php
}
if ($settings['twitter'] != '') {
?>
							<a class="list-group-item list-group-item-info list-group-item-action" href="<?php
echo htmlspecialchars($settings['twitter']);
?>" target="_blank"><strong><i class="fab fa-twitter-square"></i>&nbsp; Twitter</strong></a>
<?php
}
if ($settings['youtube'] != '') {
?>	
							<a class="list-group-item list-group-item-danger list-group-item-action" href="<?php
echo htmlspecialchars($settings['youtube']);
?>" target="_blank"><strong><i class="fab fa-youtube-square"></i>&nbsp; YouTube</strong></a>
<?php
}
if ($settings['linkedin'] != '') {
?>	
							<a class="list-group-item list-group-item-primary list-group-item-action" href="<?php
echo htmlspecialchars($settings['linkedin']);
?>" target="_blank"><strong><i class="fab fa-linkedin"></i>&nbsp; LinkedIn</strong></a>
<?php
}
?>	        
                        </div>
            
                        <h5 class="mt-4 mb-3"><i class="far fa-paper-plane"></i> Leave Your Message</h5>
<?php
if (isset($_POST['send'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---
    
    if ($logged == 'No') {
        $name    = $_POST['name'];
        $email   = $_POST['email'];
    } else {
        $name = $rowu['username'];
        $email = $rowu['email'];
    }
    $content = $_POST['text'];
    
    $captcha = '';
    
    if (isset($_POST['g-recaptcha-response'])) {
        $captcha = $_POST['g-recaptcha-response'];
    }
    
    $message_status = '';
    
    if (!empty($captcha)) {
        $url          = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
        $response     = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        
        if ($responseKeys["success"]) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message_status = '<div class="alert alert-danger">The entered E-Mail Address is invalid.</div>';
            } else {
                // MODIFICATION : Mise à jour de la requête
                $stmt = mysqli_prepare($connect, "INSERT INTO messages (name, email, content, created_at) VALUES(?, ?, ?, NOW())");
                // MODIFICATION : Ajustement des paramètres
                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $content);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                $message_status = '<div class="alert alert-success">Your message has been sent successfully.</div>';
            }
        } else {
             $message_status = '<div class="alert alert-danger">Failed to verify reCAPTCHA.</div>';
        }
    } else {
        $message_status = '<div class="alert alert-danger">Please complete the reCAPTCHA.</div>';
    }
    
    echo $message_status;
}
?>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php
if ($logged == 'No') {
?>
                            <div class="form-group mb-3">
                                <label for="name"><i class="fa fa-user"></i> Name:</label>
                                <input type="text" name="name" id="name" value="" class="form-control" required />
                            </div>
									
                            <div class="form-group mb-3">
                                <label for="email"><i class="fa fa-envelope"></i> E-Mail Address:</label>
                                <input type="email" name="email" id="email" value="" class="form-control" required />
                            </div>
<?php
}
?>
                            <div class="form-group mb-3">
                                <label for="input-message"><i class="far fa-file-alt"></i> Message:</label>
                                <textarea name="text" id="input-message" rows="8" class="form-control" required></textarea>
                            </div>

                            <div class="text-center mb-3">
                                <div class="g-recaptcha" data-sitekey="<?php
echo $settings['gcaptcha_sitekey'];
?>"></div>
                            </div>

                            <input type="submit" name="send" class="btn btn-primary col-12" value="Send" />
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