<?php
// 1. INCLURE LE NOYAU D'ABORD
include_once '../core.php'; 

// 2. VÉRIFICATION DE SÉCURITÉ
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND role='Admin'");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($suser) <= 0) {
        header("Location: " . $settings['site_url']); exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    header("Location: ../login"); exit;
}
// --- FIN SÉCURITÉ ---

// --- Logique de Basculement de Statut (Toggle) ---
$csrf_token = $_SESSION['csrf_token']; 

// Activer
if (isset($_GET['activate-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['activate-id'];
    $stmt_activate = mysqli_prepare($connect, "UPDATE footer_pages SET active = 'Yes' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_activate, "i", $id);
    mysqli_stmt_execute($stmt_activate);
    mysqli_stmt_close($stmt_activate);
    header("Location: footer_pages.php");
    exit;
}

// Désactiver
if (isset($_GET['deactivate-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['deactivate-id'];
    $stmt_deactivate = mysqli_prepare($connect, "UPDATE footer_pages SET active = 'No' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_deactivate, "i", $id);
    mysqli_stmt_execute($stmt_deactivate);
    mysqli_stmt_close($stmt_deactivate);
    header("Location: footer_pages.php");
    exit;
}

// 3. INCLURE LE HEADER HTML
include 'header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Footer Pages</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Footer Pages</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Manage the content of the 5 fixed Footer pages</h3>
                    </div>
                    <div class="card-body">
                        <table id="footer-pages-table" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Page Title</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_pages = mysqli_query($connect, "SELECT * FROM footer_pages ORDER BY id ASC");
                                while ($row = mysqli_fetch_assoc($query_pages)) {
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td>
                                            <?php if ($row['active'] == 'Yes') : ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else : ?>
                                                <span class="badge badge-warning">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['active'] == 'Yes') : ?>
                                                <a href="footer_pages.php?deactivate-id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-warning btn-sm" title="DDeactivate">
                                                    <i class="fas fa-toggle-off"></i>
                                                </a>
                                            <?php else : ?>
                                                <a href="footer_pages.php?activate-id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-success btn-sm" title="Activate">
                                                    <i class="fas fa-toggle-on"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="edit_footer_page.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
        </div>
    </div>
</section>

<?php
include 'footer.php';
?>

<script>
$(function () {
    $('#footer-pages-table').DataTable({
        "paging": false,
        "lengthChange": false,
        "searching": false,
        "ordering": false,
        "info": false,
        "autoWidth": false,
        "responsive": true
    });
});
</script>