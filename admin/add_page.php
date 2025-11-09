<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title   = $_POST['title'];
    $slug    = generateSeoURL($title, 0);
    $content = $_POST['content'];
    $active  = $_POST['active']; // Nouveau champ

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($connect, "SELECT title FROM `pages` WHERE title=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    $queryvalid = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $validator = mysqli_num_rows($queryvalid);
    if ($validator > 0) {
        echo '
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                Page with this name has already been added.
            </div>';
    } else {
        $stmt = mysqli_prepare($connect, "INSERT INTO pages (title, slug, content, active) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $title, $slug, $content, $active);
        mysqli_stmt_execute($stmt);
        $page_id = mysqli_insert_id($connect); // Get the ID of the new page
        mysqli_stmt_close($stmt);

        // N'ajouter au menu que si la page est publiée
        if ($page_id && $active == 'Yes') {
            $menu_page = $title;
            $menu_path = 'page?name=' . $slug;
            $menu_icon = 'fa-columns';

            $stmt = mysqli_prepare($connect, "INSERT INTO menu (page, path, fa_icon) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $menu_page, $menu_path, $menu_icon);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        echo '<meta http-equiv="refresh" content="0;url=pages.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-alt"></i> Add Page</h1>
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

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Page Details</h3>
            </div>         
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" value="" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" id="summernote" name="content" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="active" class="form-control" required>
                            <option value="Yes" selected>Published</option>
                            <option value="No">Draft</option>
                        </select>
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