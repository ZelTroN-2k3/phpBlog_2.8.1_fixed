<?php
include "header.php";

// --- LOGIQUE DE STATUT (pour les redirections) ---
$status_url_param = ''; // Format &status=...
$status_url_query = ''; // Format ?status=...
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $status_param = htmlspecialchars($_GET['status']);
    $status_url_param = '&status=' . $status_param;
    $status_url_query = '?status=' . $status_param;
}

// --- ✨ NOUVELLE LOGIQUE POUR LES ACTIONS EN MASSE (MENU) ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $menu_ids = $_POST['menu_ids'] ?? [];

    if (!empty($action) && !empty($menu_ids)) {
        $placeholders = implode(',', array_fill(0, count($menu_ids), '?'));
        $types = str_repeat('i', count($menu_ids));

        if ($action == 'publish') {
            $stmt = mysqli_prepare($connect, "UPDATE menu SET active = 'Yes' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$menu_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'draft') {
            $stmt = mysqli_prepare($connect, "UPDATE menu SET active = 'No' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$menu_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        // Note : L'action "delete" est gérée par le GET pour l'instant, 
        // mais pourrait être ajoutée ici si nécessaire.
        
        echo '<meta http-equiv="refresh" content="0; url=menu_editor.php' . $status_url_query . '">';
        exit;
    }
}
// --- FIN DE LA LOGIQUE EN MASSE ---


if (isset($_GET['up-id'])) {
    // ... (votre logique 'up-id' reste la même) ...
    // Assurez-vous que la redirection finale inclut $status_url_query
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php' . $status_url_query . '">';
    exit;
}

if (isset($_GET['down-id'])) {
    // ... (votre logique 'down-id' reste la même) ...
    // Assurez-vous que la redirection finale inclut $status_url_query
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php' . $status_url_query . '">';
    exit;
}

if (isset($_GET['delete-id'])) {
    // ... (votre logique 'delete-id' reste la même) ...
    // Assurez-vous que la redirection finale inclut $status_url_query
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php' . $status_url_query . '">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-bars"></i> Menu Editor</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Menu Editor</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    $stmt = mysqli_prepare($connect, "SELECT * FROM `menu` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=menu_editor.php' . $status_url_query . '">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        validate_csrf_token();
        
        $page    = $_POST['page'];
        $path    = $_POST['path'];
        $fa_icon = $_POST['fa_icon'];
        $active  = $_POST['active']; // ✨ NOUVEAU
        
        // ✨ MODIFIÉ : Ajout de 'active'
        $stmt = mysqli_prepare($connect, "UPDATE menu SET page=?, path=?, fa_icon=?, active=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $page, $path, $fa_icon, $active, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0;url=menu_editor.php' . $status_url_query . '">';
        exit;
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                <h3 class="card-title">Edit Menu Item</h3>
              </div>         
                <form action="" method="post"> <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                  	    <label>Page Title</label>
                  	    <input name="page" class="form-control" type="text" value="<?php echo htmlspecialchars($row['page']); ?>" required>
                    </div>
                    <div class="form-group">
                  	    <label>Path (Link)</label>
                  	    <input name="path" class="form-control" type="text" value="<?php echo htmlspecialchars($row['path']); ?>" required>
                    </div>
                    <div class="form-group">
                  	    <label>Font Awesome 5 Icon</label>
                  	    <input name="fa_icon" class="form-control" type="text" value="<?php echo htmlspecialchars($row['fa_icon']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="active" required>
                            <option value="Yes" <?php if ($row['active'] == 'Yes') echo 'selected'; ?>>Published</option>
                            <option value="No" <?php if ($row['active'] == 'No') echo 'selected'; ?>>Draft</option>
                        </select>
                    </div>
                    
                </div>
                <div class="card-footer">
                    <input type="submit" class="btn btn-success" name="submit" value="Save" />
                    <a href="menu_editor.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Cancel</a>
                </div>
                </form>
            </div>
<?php
}
?>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                    <a href="add_menu.php<?php echo $status_url_query; ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Menu Item</a>
                </h3>
              </div>
              
            <div class="card-body pb-0">
            <?php
            // ✨ MODIFIÉ : Logique de filtrage simplifiée, basée sur menu.active
            $current_status = $_GET['status'] ?? 'all';
            $status_query_sql = '';
            $status_url_param_in_loop = ''; 

            if ($current_status == 'published') {
                $status_query_sql = " WHERE active = 'Yes'";
                $status_url_param_in_loop = '&status=published';
            } elseif ($current_status == 'draft') {
                $status_query_sql = " WHERE active = 'No'";
                $status_url_param_in_loop = '&status=draft';
            }
            ?>
            <div class="d-flex justify-content-start mb-0">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_status == 'all') echo 'active'; ?>" href="?status=all">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_status == 'published') echo 'active'; ?>" href="?status=published">Published</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_status == 'draft') echo 'active'; ?>" href="?status=draft">Draft</a>
                    </li>
                </ul>
            </div>
            </div>
            
            <form action="menu_editor.php<?php echo $status_url_query; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="card-body p-0"> 
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th style="width: 10px;"><input type="checkbox" id="select-all"></th>
                            <th style="width: 50px;">Order</th>
                            <th>Page</th>
                            <th>Path</th>
                            <th>Statut</th>
                            <th style="width: 250px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
// ✨ MODIFIÉ : Requête simplifiée (plus de JOIN)
$query_sql = "SELECT * FROM menu" . $status_query_sql . " ORDER BY id ASC";
$query = mysqli_query($connect, $query_sql);

// La requête pour $last_id doit aussi être filtrée
$queryli_sql = "SELECT id FROM menu" . $status_query_sql . " ORDER BY id DESC LIMIT 1";
$queryli  = mysqli_query($connect, $queryli_sql);
$rowli    = mysqli_fetch_assoc($queryli);
$last_id  = $rowli ? $rowli['id'] : null;

$first = true;
while ($row = mysqli_fetch_assoc($query)) {
    
    echo '
            <tr>
                <td><input type="checkbox" name="menu_ids[]" value="' . $row['id'] . '"></td>
                <td>' . $row['id'] . '</td>
                <td><i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . '</td>
                <td>' . htmlspecialchars($row['path']) . '</td>
                
                <td>';
    if($row['active'] == "Yes") {
		echo '<span class="badge bg-success">Published</span>';
	} else {
		echo '<span class="badge bg-warning">Draft</span>';
	}
    echo '</td>
                <td>
';
// Liens modifiés pour inclure $status_url_param_in_loop
if ($first == false) {
echo '
                    <a href="?up-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" title="Move Up" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-up"></i></a>
';
}
if ($row['id'] != $last_id) {
echo '
                    <a href="?down-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" title="Move Down" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-down"></i></a>
';
}
echo '
                    <a href="?edit-id=' . $row['id'] . $status_url_param_in_loop . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
                    <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to remove this menu item?\');"><i class="fa fa-trash"></i> Delete</a>
                </td>
            </tr>
';
$first = false;
}
?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="mt-0">
                        <select name="bulk_action" class="form-control" style="width: 200px; display: inline-block;">
                            <option value="">Bulk Actions</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Draft</option>
                            </select>
                        <button type="submit" name="apply_bulk_action" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
            </div>

    </div></section>
<script>
$(document).ready(function() {
    // Gérer la case "Tout sélectionner"
    $('#select-all').on('click', function(){
        // 'table' est supposé être défini globalement si DataTables est utilisé,
        // sinon, nous ciblons simplement le tableau parent.
        var table = $(this).closest('table');
        $('input[type="checkbox"]', table).prop('checked', this.checked);
    });
});
</script>
<?php
include "footer.php";
?>