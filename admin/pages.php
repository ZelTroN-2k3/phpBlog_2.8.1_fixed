<?php
include "header.php";

// --- NOUVELLE LOGIQUE POUR LES ACTIONS EN MASSE ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $page_ids = $_POST['page_ids'] ?? [];

    if (!empty($action) && !empty($page_ids)) {
        // Crée une chaîne de placeholders (?,?,?) pour la requête IN
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
            // D'abord, supprimer les entrées de menu associées
            $stmt_menu = mysqli_prepare($connect, "DELETE FROM menu WHERE path LIKE 'page?name=%' AND SUBSTRING_INDEX(path, 'name=', -1) IN (SELECT slug FROM pages WHERE id IN ($placeholders))");
            mysqli_stmt_bind_param($stmt_menu, $types, ...$page_ids);
            mysqli_stmt_execute($stmt_menu);
            mysqli_stmt_close($stmt_menu);

            // Ensuite, supprimer les pages
            $stmt_pages = mysqli_prepare($connect, "DELETE FROM pages WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_pages, $types, ...$page_ids);
            mysqli_stmt_execute($stmt_pages);
            mysqli_stmt_close($stmt_pages);
        }
        echo '<meta http-equiv="refresh" content="0; url=pages.php">';
        exit;
    }
}
// --- FIN DE LA LOGIQUE ---

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];

    // Use prepared statements to get the slug
    $stmt = mysqli_prepare($connect, "SELECT slug FROM `pages` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $slug = $row['slug'];
        $path = 'page?name=' . $slug;

        // Delete from menu table
        $stmt_menu = mysqli_prepare($connect, "DELETE FROM `menu` WHERE path=?");
        mysqli_stmt_bind_param($stmt_menu, "s", $path);
        mysqli_stmt_execute($stmt_menu);
        mysqli_stmt_close($stmt_menu);

        // Delete from pages table
        $stmt_page = mysqli_prepare($connect, "DELETE FROM `pages` WHERE id=?");
        mysqli_stmt_bind_param($stmt_page, "i", $id);
        mysqli_stmt_execute($stmt_page);
        mysqli_stmt_close($stmt_page);
    }
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
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
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `pages` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=pages.php">';
        exit;
    }
    $slug_old = $row['slug'];
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title   = $_POST['title'];
        $slug    = generateSeoURL($title, 0);
        $content = $_POST['content'];
        $active  = $_POST['active'];
        
        // Use prepared statement for validation
        $stmt = mysqli_prepare($connect, "SELECT id FROM `pages` WHERE title = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
        $queryvalid = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if (mysqli_num_rows($queryvalid) > 0) {
            echo '
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Error!</h5>
                    A page with this name already exists.
                </div>';
        } else {
            // Use prepared statement for UPDATE pages
            $stmt = mysqli_prepare($connect, "UPDATE pages SET title=?, slug=?, content=?, active=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $slug, $content, $active, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Gérer le menu en fonction du statut
            $menu_path_old = 'page?name=' . $slug_old;
            $menu_path_new = 'page?name=' . $slug;

            if ($active == 'Yes') {
                // Si la page devient publiée, on met à jour ou on crée l'entrée de menu
                $stmt_menu_check = mysqli_prepare($connect, "SELECT id FROM menu WHERE path=?");
                mysqli_stmt_bind_param($stmt_menu_check, "s", $menu_path_old);
                mysqli_stmt_execute($stmt_menu_check);
                $result_check = mysqli_stmt_get_result($stmt_menu_check);
                mysqli_stmt_close($stmt_menu_check);

                if (mysqli_num_rows($result_check) > 0) {
                    // L'élément de menu existe, on le met à jour
                    $stmt_menu_update = mysqli_prepare($connect, "UPDATE menu SET page=?, path=? WHERE path=?");
                    mysqli_stmt_bind_param($stmt_menu_update, "sss", $title, $menu_path_new, $menu_path_old);
                    mysqli_stmt_execute($stmt_menu_update);
                    mysqli_stmt_close($stmt_menu_update);
                } else {
                    // L'élément de menu n'existe pas, on le crée
                    $stmt_menu_insert = mysqli_prepare($connect, "INSERT INTO menu (page, path, fa_icon) VALUES (?, ?, 'fa-columns')");
                    mysqli_stmt_bind_param($stmt_menu_insert, "ss", $title, $menu_path_new);
                    mysqli_stmt_execute($stmt_menu_insert);
                    mysqli_stmt_close($stmt_menu_insert);
                }
            } else {
                // Si la page devient un brouillon, on supprime l'entrée de menu
                $stmt_menu_delete = mysqli_prepare($connect, "DELETE FROM menu WHERE path=?");
                mysqli_stmt_bind_param($stmt_menu_delete, "s", $menu_path_old);
                mysqli_stmt_execute($stmt_menu_delete);
                mysqli_stmt_close($stmt_menu_delete);
            }
            
            echo '<meta http-equiv="refresh" content="0; url=pages.php">';
            exit;
        }
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                  <h3 class="card-title">Edit Page</h3>
              </div>         
                  <form action="" method="post">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="form-group">
                            <label>Title</label>
                            <input name="title" type="text" class="form-control" value="<?php
                                  echo htmlspecialchars($row['title']); // Prevent XSS
?>" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" id="summernote" required><?php
                                  echo html_entity_decode($row['content']);
?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="active" required>
                                <option value="Yes" <?php if ($row['active'] == 'Yes') { echo 'selected'; } ?>>Published</option>
                                <option value="No" <?php if ($row['active'] == 'No') { echo 'selected'; } ?>>Draft</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <input type="submit" class="btn btn-primary" name="submit" value="Save" />
                        <a href="pages.php" class="btn btn-secondary">Cancel</a>
                    </div>
                  </form>
            </div>
<?php
}
?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="add_page.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Page</a>
                        </h3>
                    </div>
        <div class="card-body">
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <?php
            // Logique de filtrage
            $current_status = $_GET['status'] ?? 'all';
            $status_query = '';
            if ($current_status == 'published') {
                $status_query = " WHERE active = 'Yes'";
            } elseif ($current_status == 'draft') {
                $status_query = " WHERE active = 'No'";
            }
            ?>
            <div class="d-flex justify-content-start mb-3">
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
            <table id="dt-basic" class="table table-bordered table-hover" style="width:100%">
                <thead>
                <tr>
                    <th style="width: 10px;"><input type="checkbox" id="select-all"></th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM pages" . $status_query . " ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
  echo '
                <tr>
                    <td><input type="checkbox" name="page_ids[]" value="' . $row['id'] . '"></td>
	                <td>' . htmlspecialchars($row['title']) . '</td>
                    <td>';
    if($row['active'] == "Yes") {
		echo '<span class="badge bg-success">Published</span>';
	} else {
		echo '<span class="badge bg-warning">Draft</span>';
	}
	echo '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this page? The menu item will also be deleted.\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <select name="bulk_action" class="form-control" style="width: 200px; display: inline-block;">
                            <option value="">Bulk Actions</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Set as Draft</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" name="apply_bulk_action" class="btn btn-primary">Apply</button>
                    </div>
            </form>
        </div>
            </div>

    </div></section>
<script>
$(document).ready(function() {
    // Note: DataTables est initialisé dans footer.php. On le surcharge ici pour l'ordre si nécessaire.
    // L'ordre par défaut (colonne 0 descendante) convient ici.
    $('#select-all').on('click', function(){
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });
});
</script>
<?php
include "footer.php";
?>