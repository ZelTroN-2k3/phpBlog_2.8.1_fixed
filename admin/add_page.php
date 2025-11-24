<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- Validation CSRF ---
    validate_csrf_token();

    $title   = $_POST['title'];
    $slug    = generateSeoURL($title); 
    $content = $_POST['content'];
    $active  = $_POST['active']; 

    // Vérification si le titre existe déjà
    $stmt = mysqli_prepare($connect, "SELECT title FROM `pages` WHERE title=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    $queryvalid = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($queryvalid) > 0) {
        echo '
            <div class="alert alert-warning alert-dismissible m-3">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                Page with this name has already been added.
            </div>';
    } else {
        // Insertion
        $stmt = mysqli_prepare($connect, "INSERT INTO pages (title, slug, content, active) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $title, $slug, $content, $active);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo '<div class="alert alert-success m-3">Page created successfully! Redirecting...</div>';
        echo '<meta http-equiv="refresh" content="1;url=pages.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-alt"></i> Add New Page</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="pages.php">Pages</a></li>
                    <li class="breadcrumb-item active">Add Page</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="row">
                <div class="col-lg-9 col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Page Content</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title</label>
                                <input class="form-control form-control-lg" name="title" type="text" placeholder="Enter page title" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Content</label>
                                <textarea class="form-control" id="summernote" name="content" rows="15" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-12">
                    
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Publish</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control" required>
                                    <option value="Yes" selected>Published</option>
                                    <option value="No">Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="add" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Create Page
                            </button>
                            <a href="pages.php" class="btn btn-default btn-block">Cancel</a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</section>

<script>
$(document).ready(function() {
    // Summernote est activé automatiquement par footer.php s'il détecte #summernote
});
</script>

<?php include "footer.php"; ?>