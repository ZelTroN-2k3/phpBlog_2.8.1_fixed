<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title = $_POST['title'];
    
    $stmt = mysqli_prepare($connect, "INSERT INTO albums (title) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-plus"></i> Add Album</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="gallery.php">Gallery</a></li>
                    <li class="breadcrumb-item"><a href="albums.php">Albums</a></li>
                    <li class="breadcrumb-item active">Add Album</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Album Details</h3>
            </div>
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" value="" type="text" required>
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