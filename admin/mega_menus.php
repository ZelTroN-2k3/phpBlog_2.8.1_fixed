<?php
include "header.php";

$message = '';

// --- SUPPRESSION ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    $stmt = mysqli_prepare($connect, "DELETE FROM mega_menus WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Menu deleted successfully.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- BASCULE ACTIF/INACTIF ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    $stmt = mysqli_prepare($connect, "UPDATE mega_menus SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Status updated.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LISTING ---
$menus = [];
$q = mysqli_query($connect, "SELECT * FROM mega_menus ORDER BY position_order ASC");
while ($row = mysqli_fetch_assoc($q)) {
    $menus[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-columns"></i> Mega Menus Manager</h1>
            </div>
            <div class="col-sm-6">
                <a href="add_mega_menu.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> New Mega Menu</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Your Mega Menus</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Name (Internal)</th>
                            <th>Menu Label</th>
                            <th>Icon</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($menus)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No Mega Menus found.</td></tr>
                        <?php else: ?>
                            <?php foreach($menus as $m): ?>
                                <tr>
                                    <td><span class="badge bg-light border"><?php echo $m['position_order']; ?></span></td>
                                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['trigger_text']); ?></td>
                                    <td><i class="fa <?php echo htmlspecialchars($m['trigger_icon']); ?>"></i></td>
                                    <td>
                                        <?php if($m['active'] == 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_mega_menu.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                        
                                        <a href="?toggle_id=<?php echo $m['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm <?php echo ($m['active'] == 'Yes' ? 'btn-warning' : 'btn-success'); ?>">
                                           <i class="fas <?php echo ($m['active'] == 'Yes' ? 'fa-eye-slash' : 'fa-eye'); ?>"></i>
                                        </a>
                                        
                                        <a href="?delete_id=<?php echo $m['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this menu?');">
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