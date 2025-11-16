<?php
include "header.php";

$message = '';

// --- SUPPRESSION ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    
    // Récupérer l'image pour la supprimer du serveur
    $q = mysqli_query($connect, "SELECT avatar FROM testimonials WHERE id=$id");
    $r = mysqli_fetch_assoc($q);
    if ($r && !empty($r['avatar']) && file_exists("../" . $r['avatar'])) {
        unlink("../" . $r['avatar']);
    }

    $stmt = mysqli_prepare($connect, "DELETE FROM testimonials WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Testimonial deleted.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- TOGGLE STATUS ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    $stmt = mysqli_prepare($connect, "UPDATE testimonials SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Status updated.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LISTING ---
$items = [];
$q = mysqli_query($connect, "SELECT * FROM testimonials ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-quote-left"></i> Testimonials</h1>
            </div>
            <div class="col-sm-6">
                <a href="add_testimonial.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Add New</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <div class="card card-primary card-outline">
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Content Preview</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($items)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No testimonials yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($items as $t): 
                                $avatar = !empty($t['avatar']) ? '../'.$t['avatar'] : '../assets/img/avatar.png';
                            ?>
                                <tr>
                                    <td><img src="<?php echo $avatar; ?>" class="img-circle" width="40" height="40" style="object-fit:cover;"></td>
                                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                                    <td><small><em><?php echo emoticons(htmlspecialchars(substr(strip_tags($t['content']), 0, 50))) . '...'; ?></em></small></td>
                                    <td>
                                        <?php if($t['active'] == 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif($t['active'] == 'Pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_testimonial.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                        
                                        <a href="?toggle_id=<?php echo $t['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm <?php echo ($t['active'] == 'Yes' ? 'btn-warning' : 'btn-success'); ?>">
                                           <i class="fas <?php echo ($t['active'] == 'Yes' ? 'fa-eye-slash' : 'fa-eye'); ?>"></i>
                                        </a>
                                        
                                        <a href="?delete_id=<?php echo $t['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this testimonial?');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>