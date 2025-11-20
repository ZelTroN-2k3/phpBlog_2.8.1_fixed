<?php
include "header.php";

// --- LOGIQUE D'UPLOAD DE L'IMAGE DE FOND ---
$bg_upload_dir = "../uploads/banned_bg/";
$bg_message = "";

// Récupérer l'image actuelle depuis la table settings (ID=1)
$q_curr_bg = mysqli_query($connect, "SELECT ban_bg_image FROM settings WHERE id = 1");
$current_bg_file = ($q_curr_bg && mysqli_num_rows($q_curr_bg) > 0) ? mysqli_fetch_assoc($q_curr_bg)['ban_bg_image'] : 'default.jpg';

// Si vide en BDD, mettre default
if (empty($current_bg_file)) { $current_bg_file = 'default.jpg'; }

if (isset($_POST['upload_bg'])) {
    validate_csrf_token();
    
    if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['bg_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($ext), $allowed)) {
            $bg_message = '<div class="alert alert-danger">Error: Format not allowed.</div>';
        } else {
            $new_filename = "ban_bg_" . time() . "." . $ext;
            $destination = $bg_upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $destination)) {
                
                // Mise à jour de la colonne ban_bg_image dans la table settings
                $stmt_upd_bg = mysqli_prepare($connect, "UPDATE settings SET ban_bg_image = ? WHERE id = 1");
                mysqli_stmt_bind_param($stmt_upd_bg, "s", $new_filename);
                mysqli_stmt_execute($stmt_upd_bg);
                mysqli_stmt_close($stmt_upd_bg);

                // Supprimer l'ancienne si différente de default.jpg
                if ($current_bg_file != 'default.jpg' && file_exists($bg_upload_dir . $current_bg_file)) {
                   @unlink($bg_upload_dir . $current_bg_file);
                }

                $bg_message = '<div class="alert alert-success">Image updated!</div>';
                $current_bg_file = $new_filename;
            } else {
                 $bg_message = '<div class="alert alert-danger">Error during upload.</div>';
            }
        }
    }
}
// --- FIN LOGIQUE UPLOAD ---

// 1. Suppression DÉFINITIVE d'un ban
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    $stmt = mysqli_prepare($connect, "DELETE FROM bans WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo '<meta http-equiv="refresh" content="0; url=bans.php">';
    exit;
}

// 2. Activer / Désactiver un ban
if (isset($_GET['toggle_active'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_active'];
    $current_status_q = mysqli_query($connect, "SELECT active FROM bans WHERE id = '$id'");
    $current_status = mysqli_fetch_assoc($current_status_q)['active'];
    $new_status = ($current_status == 'Yes') ? 'No' : 'Yes';

    $stmt = mysqli_prepare($connect, "UPDATE bans SET active = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo '<meta http-equiv="refresh" content="0; url=bans.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0"><i class="fas fa-user-slash"></i> Banned Users/IPs</h1></div>
            <div class="col-sm-6">
                <a href="add_ban.php" class="btn btn-danger float-right"><i class="fas fa-plus"></i> Add New Ban</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-md-12">
                <?php echo $bg_message; ?>
                <div class="card card-primary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-image"></i> Manage the background of the "Banned" page"</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <strong>Current Preview:</strong><br>
                                <img src="../uploads/banned_bg/<?php echo $current_bg_file; ?>" alt="Current Background" class="img-thumbnail mt-2" style="max-height: 150px;">
                            </div>
                            <div class="col-md-8">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="form-group">
                                        <label for="bg_image">Change the image (JPG, PNG, GIF)</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="bg_image" name="bg_image" accept="image/*" required>
                                                <label class="custom-file-label" for="bg_image">Choose a file</label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="upload_bg" class="btn btn-primary"><i class="fas fa-upload"></i> Upload new image</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-danger card-outline">
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($connect, "SELECT * FROM bans ORDER BY id DESC");
                        if(mysqli_num_rows($q) == 0) { echo '<tr><td colspan="6" class="text-center text-muted">No bans found.</td></tr>'; }
                        while ($row = mysqli_fetch_assoc($q)) {
                            $badge_color = 'secondary';
                            if($row['ban_type'] == 'ip') $badge_color = 'danger';
                            if($row['ban_type'] == 'username') $badge_color = 'warning';
                            if($row['ban_type'] == 'email') $badge_color = 'info';

                            $status_class = ($row['active'] == 'Yes') ? 'badge-success' : 'badge-secondary';
                            $toggle_text = ($row['active'] == 'Yes') ? 'Deactivate' : 'Activate';
                            $toggle_icon = ($row['active'] == 'Yes') ? 'fa-toggle-on' : 'fa-toggle-off';
                            $toggle_color = ($row['active'] == 'Yes') ? 'btn-warning' : 'btn-success';
                        ?>
                            <tr>
                                <td><span class="badge bg-<?php echo $badge_color; ?>"><?php echo strtoupper(str_replace('_', ' ', $row['ban_type'])); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['ban_value']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $row['active']; ?></span></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="?toggle_active=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm <?php echo $toggle_color; ?>" title="<?php echo $toggle_text; ?>">
                                        <i class="fas <?php echo $toggle_icon; ?>"></i>
                                    </a>
                                    <a href="edit_ban.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Edit Ban">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to permanently delete this ban?');" title="Delete Ban">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>    
</section>

<?php include "footer.php"; ?>