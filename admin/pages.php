<?php
include "header.php";

// --- LOGIQUE ACTIONS EN MASSE ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $page_ids = $_POST['page_ids'] ?? [];

    if (!empty($action) && !empty($page_ids)) {
        $placeholders = implode(',', array_fill(0, count($page_ids), '?'));
        $types = str_repeat('i', count($page_ids));

        if ($action == 'publish') {
            $stmt = mysqli_prepare($connect, "UPDATE pages SET active = 'Yes' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$page_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'draft') {
            $stmt = mysqli_prepare($connect, "UPDATE pages SET active = 'No' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$page_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'delete') {
            // Suppression menu + pages
            $stmt_menu = mysqli_prepare($connect, "DELETE FROM menu WHERE path LIKE 'page?name=%' AND parent_id IN (SELECT id FROM pages WHERE id IN ($placeholders))"); 
            // Note: La logique de liaison menu/page peut varier, ici on simplifie la suppression des pages
            $stmt = mysqli_prepare($connect, "DELETE FROM pages WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$page_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo '<meta http-equiv="refresh" content="0; url=pages.php">';
        exit;
    }
}

// --- LOGIQUE SUPPRESSION INDIVIDUELLE ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    // Récupérer le slug pour nettoyer le menu si besoin
    $q = mysqli_query($connect, "SELECT slug FROM pages WHERE id='$id'");
    $r = mysqli_fetch_assoc($q);
    $slug = $r['slug'];

    // Supprimer l'entrée de menu correspondante (si elle existe)
    $stmt_menu = mysqli_prepare($connect, "DELETE FROM menu WHERE path = ?");
    $menu_path = "page?name=" . $slug;
    mysqli_stmt_bind_param($stmt_menu, "s", $menu_path);
    mysqli_stmt_execute($stmt_menu);
    mysqli_stmt_close($stmt_menu);

    // Supprimer la page
    $stmt = mysqli_prepare($connect, "DELETE FROM `pages` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=pages.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-alt"></i> Pages</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Pages</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-12">
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="add_page.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add New Page
                            </a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <table class="table table-bordered table-hover" id="dt-pages" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 10px;" class="text-center">
                                            <input type="checkbox" id="select-all">
                                        </th>
                                        <th>Title</th>
                                        <th>Slug (URL)</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 160px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM `pages` ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
    echo '
        <tr>
            <td class="text-center">
                <input type="checkbox" name="page_ids[]" value="' . $row['id'] . '">
            </td>
            <td>' . htmlspecialchars($row['title']) . '</td>
            <td><code class="text-muted">page?name=' . htmlspecialchars($row['slug']) . '</code></td>
            <td class="text-center">';
            
    if ($row['active'] == 'Yes') {
        echo '<span class="badge badge-success">Published</span>';
    } else {
        echo '<span class="badge badge-warning">Draft</span>';
    }
    
    echo '  </td>
            <td class="text-center">
                <a href="../page?name=' . htmlspecialchars($row['slug']) . '" target="_blank" class="btn btn-secondary btn-sm mr-1" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                
                <a href="edit_page.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm mr-1" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                
                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this page?\');" title="Delete">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
}
?>
                                </tbody>
                            </table>
                            
                            <div class="mt-3 p-2 bg-light border rounded">
                                <div class="d-inline-flex align-items-center">
                                    <span class="mr-2">With Selected:</span>
                                    <select name="bulk_action" class="form-control form-control-sm mr-2" style="width: auto;">
                                        <option value="">-- Choose Action --</option>
                                        <option value="publish">Set as Published</option>
                                        <option value="draft">Set as Draft</option>
                                        <option value="delete">Delete</option>
                                    </select>
                                    <button type="submit" name="apply_bulk_action" class="btn btn-primary btn-sm">Apply</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>

    </div>
</section>

<script>
$(document).ready(function() {
    // DataTables
    var table = $('#dt-pages').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 1, "asc" ]], // Tri par titre
        "columnDefs": [
            { "orderable": false, "targets": [0, 4] } // Désactive le tri sur checkbox et actions
        ]
    });

    // Gestion Select All
    $('#select-all').on('click', function(){
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });
});
</script>
<?php include "footer.php"; ?>