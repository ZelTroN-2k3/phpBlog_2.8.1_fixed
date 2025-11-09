<?php
// --- MODIFICATION : Logique de traitement déplacée avant l'inclusion de header.php ---
include_once "../core.php"; // MODIFIÉ : include_once pour éviter la rédéclaration

// --- LOGIQUE DE SUPPRESSION (DELETE) ---
if (isset($_GET['delete-id'])) {
    // Valider le jeton CSRF (vient de header.php, mais nous l'avons manuellement dans core.php)
    validate_csrf_token_get();
    
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE category_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($connect, "DELETE FROM `categories` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Remplacement de <meta> par header()
    header("Location: categories.php");
    exit;
}

// --- LOGIQUE DE MODIFICATION (POST) ---
if (isset($_POST['submit_edit'])) {
    // Valider le jeton CSRF
    validate_csrf_token();
    
    $id = (int)$_POST['edit_id']; // Récupérer l'ID depuis le champ caché
    $category = $_POST['category'];
    $slug     = generateSeoURL($category, 0);
    
    // Use prepared statement for validation
    $stmt = mysqli_prepare($connect, "SELECT id FROM `categories` WHERE category = ? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $category, $id);
    mysqli_stmt_execute($stmt);
    $queryvalid = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($queryvalid) > 0) {
        // Rediriger avec une erreur si la catégorie existe
        header("Location: categories.php?edit-id=$id&error=exists");
        exit;
    } else {
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE categories SET category=?, slug=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssi", $category, $slug, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Remplacement de <meta> par header()
        header("Location: categories.php");
        exit;
    }
}
// --- FIN DE LA MODIFICATION ---


// Inclure le header.php maintenant que toute la logique est terminée
include "header.php";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list-ol"></i> Categories</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Categories</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
	  
<?php
// --- LOGIQUE D'AFFICHAGE DE L'ÉDITEUR (GET) ---
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `categories` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        // La redirection <meta> est acceptable ici car header.php a déjà été inclus
        echo '<meta http-equiv="refresh" content="0; url=categories.php">';
        exit;
    }
    
    // Afficher l'erreur si elle existe dans l'URL
    if (isset($_GET['error']) && $_GET['error'] == 'exists') {
        echo '
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> Category with this name has already been added.
            </div>';
    }
?>
            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                    <h3 class="card-title">Edit Category</h3>
                </div>
                <div class="card-body">
                    <form action="categories.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="edit_id" value="<?php echo $id; ?>">
                        
                        <div class="form-group">
                            <label>Category</label>
                            <input class="form-control" name="category" type="text" value="<?php
        echo htmlspecialchars($row['category']); // Prevent XSS
    ?>" required>
                        </div>
                        <input type="submit" class="btn btn-primary col-12" name="submit_edit" value="Save" />
                    </form>
                </div>
            </div>
<?php
}
?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="add_category.php" class="btn btn-primary"><i class="fa fa-edit"></i> Add Category</a>
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                        <thead>
                        <tr>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql    = "SELECT * FROM categories ORDER BY category ASC";
$result = mysqli_query($connect, $sql);
while ($row = mysqli_fetch_assoc($result)) {
        echo '
                <tr>
	                <td>' . $row['category'] . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this category?\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div></section>
<?php
include "footer.php";
?>