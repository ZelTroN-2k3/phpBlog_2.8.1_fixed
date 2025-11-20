<?php
include "header.php";

// --- SUPPRESSION ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    
    // Récupérer l'image pour la supprimer
    $q = mysqli_query($connect, "SELECT image_url FROM ads WHERE id=$id");
    $r = mysqli_fetch_assoc($q);
    if ($r && !empty($r['image_url']) && file_exists("../" . $r['image_url'])) {
        unlink("../" . $r['image_url']);
    }

    $stmt = mysqli_prepare($connect, "DELETE FROM ads WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=ads.php">';
    exit;
}

// --- TOGGLE STATUS ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    $stmt = mysqli_prepare($connect, "UPDATE ads SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo '<meta http-equiv="refresh" content="0; url=ads.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0"><i class="fas fa-ad"></i> Advertising Management</h1></div>
            <div class="col-sm-6">
                <a href="add_ads.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> New Ad</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:120px">Preview</th>
                            <th>Name & Link</th>
                            <th>Format</th>
                            <th>Clics</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($connect, "SELECT * FROM ads ORDER BY id DESC");
                        if(mysqli_num_rows($q) == 0) { echo '<tr><td colspan="6" class="text-center text-muted">No advertising.</td></tr>'; }
                        while ($row = mysqli_fetch_assoc($q)) {
                        ?>
                            <tr>
                                <td>
                                    <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" style="max-width: 100px; max-height: 60px; border:1px solid #ddd;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-link"></i> <?php echo htmlspecialchars($row['link_url']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($row['ad_size']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark" style="font-size:1em;"><?php echo number_format($row['clicks']); ?></span>
                                </td>
                                <td>
                                    <?php echo ($row['active'] == 'Yes') ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="edit_ads.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="?toggle_id=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-warning" title="Activate/Deactivate">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    
                                    <a href="?delete_id=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this ad?');" title="Delete">
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