<?php
include "header.php";

if (isset($_GET['delete_bgrimg'])) {
	validate_csrf_token_get();
    
    // --- MODIFICATION : S'assurer que le chemin est correct pour unlink ---
    $bgr_img_path = '../' . $settings['background_image'];
    if (file_exists($bgr_img_path) && is_file($bgr_img_path) && $settings['background_image'] != "") {
        unlink($bgr_img_path);
    }
    // --- FIN MODIFICATION ---
	
    $settings['background_image'] = '';
	
	file_put_contents('../config_settings.php', '<?php $settings = ' . var_export($settings, true) . '; ?>');
	echo '<meta http-equiv="refresh" content="0;url=settings.php">';
    exit;
}


if (isset($_POST['save'])) {

    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

	if (@$_FILES['background_image']['name'] != '') {
        // --- MODIFICATION : Correction du chemin de destination ---
        $target_dir    = "../uploads/other/"; // Doit être relatif à 'admin', donc '../uploads/'
        // --- FIN MODIFICATION ---
        
        // Générer un nom de fichier unique pour éviter les conflits
        $imageFileType = strtolower(pathinfo($_FILES["background_image"]["name"], PATHINFO_EXTENSION));
        $new_file_name = "bg_" . time() . '_' . rand(1000, 9999) . '.' . $imageFileType;
        $target_file   = $target_dir . $new_file_name;
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = @getimagesize($_FILES["background_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                File is not an image.
            </div>';
            $uploadOk = 0;
        }
        
        // Check file size (ex: 5MB)
        if ($_FILES["background_image"]["size"] > 5000000) {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                Sorry, your file is too large (Max 5MB).
            </div>';
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                Sorry, only JPG, JPEG, PNG & GIF files are allowed.
            </div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["background_image"]["tmp_name"], $target_file)) {
                // --- MODIFICATION : Enregistrer le chemin relatif depuis la racine du site ---
                $settings['background_image'] = "uploads/other/" . $new_file_name;
                // --- FIN MODIFICATION ---
            } else {
                echo '
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                    Sorry, there was an error uploading your file.
                </div>';
            }
        }
    }
    
    // Met à jour les autres paramètres
    $settings['sitename']        = $_POST['sitename'];
    $settings['description']     = $_POST['description'];
    $settings['site_url']        = rtrim($_POST['site_url'], '/'); // Assurer qu'il n'y a pas de slash final
    $settings['email']           = $_POST['email'];
    $settings['date_format']     = $_POST['date_format'];
    $settings['posts_per_page']  = (int) $_POST['posts_per_page'];
    $settings['sidebar_position'] = $_POST['sidebar_position'];
    $settings['layout']          = $_POST['layout'];
    $settings['theme']           = $_POST['theme']; // Thème
    $settings['latestposts_bar'] = $_POST['latestposts_bar'];
    $settings['rtl']             = $_POST['rtl'];
    $settings['posts_per_row']   = $_POST['posts_per_row'];
    $settings['facebook']        = $_POST['facebook'];
    $settings['instagram']       = $_POST['instagram'];
    $settings['twitter']         = $_POST['twitter'];
    $settings['youtube']         = $_POST['youtube'];
    $settings['linkedin']        = $_POST['linkedin'];
    $settings['gcaptcha_sitekey']  = $_POST['gcaptcha_sitekey'];
    $settings['gcaptcha_secretkey'] = $_POST['gcaptcha_secretkey'];
    
    // MODIFICATION : Utiliser base64_encode pour le code personnalisé
    $settings['head_customcode'] = base64_encode($_POST['head_customcode']);
    
    // Sauvegarder les paramètres dans le fichier
    if (file_put_contents('../config_settings.php', '<?php $settings = ' . var_export($settings, true) . '; ?>')) {
        echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Success!</h5>
            Settings successfully saved.
        </div>';
    } else {
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Alert!</h5>
            Error saving settings. Check file permissions for config_settings.php.
        </div>';
    }
    
    // Rafraîchir pour voir les changements
    echo '<meta http-equiv="refresh" content="2;url=settings.php">';
}
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-align-left text-primary"></i> Site Description</label>
                                        <textarea name="description" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['description']); ?></textarea>
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
        </div> </div></section>

<?php
include "footer.php";
?>