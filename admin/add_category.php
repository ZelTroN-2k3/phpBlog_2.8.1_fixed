<?php
include "header.php";

$error_message = ''; // Initialiser la variable de message

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $category = $_POST['category'];
    $slug     = generateSeoURL($category, 0);

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE category=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $queryvalid = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $validator = mysqli_num_rows($queryvalid);
    if ($validator > 0) {
        // Stocker le message d'erreur au lieu de l'afficher directement
        $error_message = '
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Error!</h5>
                A category with that name already exists.
            </div>';
    } else {
        $stmt = mysqli_prepare($connect, "INSERT INTO categories (category, slug) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $category, $slug);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // --- MODIFICATION : Remplacement de <meta> par header() ---
        header("Location: categories.php");
        exit;
        // --- FIN MODIFICATION ---
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-plus"></i> Add Category</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="posts.php">Posts</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active">Add Category</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        
        <?php 
        // Afficher le message d'erreur ici s'il existe
        if (!empty($error_message)) {
            echo $error_message;
        }
        ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Category Details</h3>
            </div>
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="category" value="" type="text" required>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="add" class="btn btn-primary" value="Add" />
                </div>
            </form>                           
        </div>

    </div></section>
<?php
include "footer.php";
?>