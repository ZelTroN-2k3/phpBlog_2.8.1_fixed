<?php
include "header.php";

// --- LOGIQUE : CONSERVATION DU STATUT ---
$status_url_param = ''; // Format &status=...
$status_url_query = ''; // Format ?status=...
$current_status = $_GET['status'] ?? 'all'; // 'all', 'published', 'draft'

$status_sql = ""; // Condition SQL
if ($current_status == 'published') {
    $status_sql = " WHERE active='Yes'";
} elseif ($current_status == 'draft') {
    $status_sql = " WHERE active='No'";
}

if ($current_status != 'all') {
    $status_param = htmlspecialchars($current_status);
    $status_url_param = '&status=' . $status_param;
    $status_url_query = '?status=' . $status_param;
}
// --- FIN LOGIQUE ---


// --- ✨ NOUVELLE LOGIQUE POUR LES ACTIONS EN MASSE (WIDGETS) ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $widget_ids = $_POST['widget_ids'] ?? [];

    if (!empty($action) && !empty($widget_ids)) {
        $placeholders = implode(',', array_fill(0, count($widget_ids), '?'));
        $types = str_repeat('i', count($widget_ids));

        if ($action == 'publish') {
            $stmt = mysqli_prepare($connect, "UPDATE widgets SET active = 'Yes' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$widget_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'draft') {
            $stmt = mysqli_prepare($connect, "UPDATE widgets SET active = 'No' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$widget_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'delete') {
            $stmt = mysqli_prepare($connect, "DELETE FROM widgets WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$widget_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
        exit;
    }
}
// --- FIN DE LA LOGIQUE EN MASSE ---


if (isset($_GET['up-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["up-id"];

    // Get previous widget's ID
    $stmt = mysqli_prepare($connect, "SELECT id FROM `widgets` WHERE id < ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rowpe = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($rowpe) {
        $prev_id = $rowpe['id'];
        $temp_id = 9999999;

        mysqli_begin_transaction($connect);
        try {
            $stmt1 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt1, "ii", $temp_id, $prev_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            $stmt2 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "ii", $prev_id, $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            $stmt3 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt3, "ii", $id, $temp_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            mysqli_commit($connect);
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($connect);
        }
    }
    
    // MODIFIÉ : Redirection avec statut
    echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
    exit;
}

if (isset($_GET['down-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["down-id"];

    // Get next widget's ID
    $stmt = mysqli_prepare($connect, "SELECT id FROM `widgets` WHERE id > ? ORDER BY id ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rowne = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($rowne) {
        $next_id = $rowne['id'];
        $temp_id = 9999998;

        mysqli_begin_transaction($connect);
        try {
            $stmt1 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt1, "ii", $temp_id, $next_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            $stmt2 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "ii", $next_id, $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            $stmt3 = mysqli_prepare($connect, "UPDATE widgets SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt3, "ii", $id, $temp_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            mysqli_commit($connect);
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($connect);
        }
    }
    
    // MODIFIÉ : Redirection avec statut
    echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
    exit;
}

if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    $stmt = mysqli_prepare($connect, "DELETE FROM `widgets` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // MODIFIÉ : Redirection avec statut
    echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-archive"></i> Widgets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Widgets</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

<?php
// --- VUE MODIFICATION ---
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    
    $stmt = mysqli_prepare($connect, "SELECT * FROM `widgets` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
        exit;
    }
    
    // --- NOUVELLE LOGIQUE DE SAUVEGARDE (ÉDITION) ---
    if (isset($_POST['edit'])) {
        validate_csrf_token();
        
        $title    = $_POST['title'];
        $position = $_POST['position'];
        $active   = $_POST['active'];
        $widget_type = $_POST['widget_type']; // Récupérer le type

        // Champs spécifiques (initialisés à NULL)
        $content     = null;
        $config_data = null;

        // Remplir les champs spécifiques selon le type
        switch ($widget_type) {
            case 'html':
                $content = $_POST['content'];
                break;
                
            case 'latest_posts':
                $limit = (int)$_POST['limit'];
                $config = ['count' => $limit]; // Créer un tableau de configuration
                $config_data = json_encode($config); // Encoder en JSON
                break;
                
            case 'search':
                // Ce type n'a besoin d'aucune configuration
                break;
        }

        // Requête de mise à jour
        $stmt_update = mysqli_prepare($connect, "UPDATE widgets SET title = ?, content = ?, config_data = ?, position = ?, active = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "sssssi", $title, $content, $config_data, $position, $active, $id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        echo '<meta http-equiv="refresh" content="0;url=widgets.php' . $status_url_query . '">';
        exit;
    }
    // --- FIN NOUVELLE LOGIQUE DE SAUVEGARDE ---
    
    // Logique pour le nom et l'icône
    $widget_name = '';
    $widget_icon = 'fas fa-puzzle-piece';

    switch ($row['widget_type']) {
        case 'html':
            $widget_name = 'HTML Personnalisé';
            $widget_icon = 'fas fa-code';
            break;
        case 'latest_posts':
            $widget_name = 'Articles Récents';
            $widget_icon = 'fas fa-list-ul';
            break;
        case 'search':
            $widget_name = 'Barre de Recherche';
            $widget_icon = 'fas fa-search';
            break;
    }
?>
            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                     <h3 class="card-title"><i class="<?php echo $widget_icon; ?>"></i> Edit Widget: <?php echo htmlspecialchars($row['title']); ?></h3>
                </div>
                <form action="widgets.php?edit-id=<?php echo $id; ?><?php echo $status_url_param; ?>" method="post">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="widget_type" value="<?php echo htmlspecialchars($row['widget_type']); ?>">
                        
                        <div class="alert alert-light">
                            <strong>Type de Widget :</strong> <?php echo $widget_name; ?> (Non modifiable)
                        </div>

                        <div class="form-group">
                            <label>Title</label>
                            <input class="form-control" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" type="text" required>
                        </div>

                        <?php 
                        // --- AFFICHAGE DES CHAMPS SPÉCIFIQUES ---
                        switch ($row['widget_type']):
                        
                            // CAS 1: HTML
                            case 'html': 
                        ?>
                                <div class="form-group">
                                    <label>Content</label>
                                    <textarea class="form-control" id="summernote" name="content" required><?php echo htmlspecialchars($row['content']); ?></textarea>
                                </div>
                        <?php 
                                break; 
                            
                            // CAS 2: ARTICLES RÉCENTS
                            case 'latest_posts': 
                                // 1. Lire la configuration JSON
                                $config = json_decode($row['config_data'], true);
                                $limit = $config['count'] ?? 5; // Valeur par défaut
                        ?>
                                <div class="form-group">
                                    <label>Nombre d'articles à afficher</label>
                                    <input class="form-control" name="limit" value="<?php echo (int)$limit; ?>" type="number" required style="width: 200px;">
                                </div>
                        <?php 
                                break;
                                
                            // CAS 3: RECHERCHE
                            case 'search': 
                        ?>
                                <div class="alert alert-info">
                                    Ce widget n'a pas besoin de configuration supplémentaire.
                                </div>
                        <?php 
                                break; 
                        
                        endswitch; 
                        // --- FIN DES CHAMPS SPÉCIFIQUES ---
                        ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Position:</label>
                                    <select class="form-control" name="position" required>
                                        <option value="Sidebar" <?php if ($row['position'] == 'Sidebar') { echo 'selected'; } ?>>Sidebar</option>
                                        <option value="Header" <?php if ($row['position'] == 'Header') { echo 'selected'; } ?>>Header</option>
                                        <option value="Footer" <?php if ($row['position'] == 'Footer') { echo 'selected'; } ?>>Footer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="active" required>
                                        <option value="Yes" <?php if ($row['active'] == 'Yes') echo 'selected'; ?>>Published</option>
                                        <option value="No" <?php if ($row['active'] == 'No') echo 'selected'; ?>>Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="card-footer">
                        <input type="submit" name="edit" class="btn btn-primary" value="Save" />
                        <a href="widgets.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
<?php
} else { // --- VUE LISTE (commence ici si pas d'edit-id) ---
?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="add_widget.php<?php echo $status_url_query; ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Widget</a>
                    </h3>
                </div>
                
                <div class="card-body pb-0">
                    <?php
                    // Pas besoin de redéfinir $current_status ou $status_url_param_in_loop, ils sont déjà définis en haut.
                    $status_url_param_in_loop = $status_url_param; // Juste pour clarifier
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
                <form action="widgets.php<?php echo $status_url_query; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="card-body">
                        <table id="dt-basic" class="table table-bordered table-hover" style="width:100%">
                            <thead>
                            <tr>
                                <th style="width: 10px;"><input type="checkbox" id="select-all"></th>
                                <th>Title</th>
                                <th>Type</th> <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
<?php
// ✨ MODIFIÉ : Requête SQL filtrée
$query_sql = "SELECT * FROM widgets" . $status_sql . " ORDER BY id ASC";
$query = mysqli_query($connect, $query_sql);
$widgets = [];
while($row = mysqli_fetch_assoc($query)) {
    $widgets[] = $row;
}

// Re-fetch avec le filtre pour trouver le last_id
$query_last = mysqli_query($connect, "SELECT id FROM widgets" . $status_sql . " ORDER BY id DESC LIMIT 1");
$row_last = mysqli_fetch_assoc($query_last);
$last_id = $row_last ? $row_last['id'] : null;

$first = true;

foreach ($widgets as $row) {
    
    // Badge de statut
    $status_badge = ($row['active'] == 'Yes') 
        ? '<span class="badge bg-success">Published</span>' 
        : '<span class="badge bg-warning">Draft</span>';
    
    // Nom du type pour affichage
    $type_name = htmlspecialchars($row['widget_type']);
    $type_icon = 'fa-puzzle-piece';
    switch($row['widget_type']) {
        case 'html': $type_name = 'HTML'; $type_icon = 'fa-code'; break;
        case 'latest_posts': $type_name = 'Articles Récents'; $type_icon = 'fa-list-ul'; break;
        case 'search': $type_name = 'Recherche'; $type_icon = 'fa-search'; break;
    }
    
    echo '
                            <tr>
                                <td><input type="checkbox" name="widget_ids[]" value="' . $row['id'] . '"></td>
                                <td>' . htmlspecialchars($row['title']) . '</td>
                                <td><span class="badge badge-info"><i class="fas ' . $type_icon . '"></i> ' . $type_name . '</span></td>
                                <td>' . htmlspecialchars($row['position']) . '</td>
                                <td>' . $status_badge . '</td>
                                <td>';
    // ✨ MODIFIÉ : Liens d'action avec conservation du statut
    if (!$first) {
        echo '<a href="?up-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" title="Move Up" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-up"></i></a> ';
    }
    if ($row['id'] != $last_id) {
        echo '<a href="?down-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" title="Move Down" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-down"></i></a> ';
    }
                                
echo '<a href="?edit-id=' . $row['id'] . $status_url_param_in_loop . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
                                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . $status_url_param_in_loop . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to remove this widget?\');"><i class="fa fa-trash"></i> Delete</a>
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
                            <select name="bulk_action" class="form-control" style="width: 200px; display: inline-block;">
                                <option value="">Bulk Actions</option>
                                <option value="publish">Publish</option>
                                <option value="draft">Draft</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" name="apply_bulk_action" class="btn btn-primary">Apply</button>
                      </div>
                </form>
                </div>
<?php
} // Fin du else (vue liste)
?>
        </div>
    </section>
<script>
$(document).ready(function() {
    var table = $('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
        "order": [[ 1, "asc" ]], // Ordre par Titre
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Désactiver le tri sur la colonne checkbox
        ]
    });

    $('#select-all').on('click', function(){
        // Correction pour cibler la table DataTable correcte
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });
});
</script>
<?php
include "footer.php";
?>