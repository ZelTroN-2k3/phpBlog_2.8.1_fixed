<?php
include "header.php";

// 1. Vérification ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0; url=pages.php">';
    exit;
}

$id = (int)$_GET['id'];

// 2. Récupération des données
$stmt = mysqli_prepare($connect, "SELECT * FROM `pages` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    echo '<div class="content-header"><div class="container-fluid"><div class="alert alert-danger">Page not found.</div></div></div>';
    include "footer.php";
    exit;
}

// 3. Traitement du formulaire
if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $title   = $_POST['title'];
    // Régénérer le slug si le titre change (pour le SEO)
    $slug    = generateSeoURL($title); 
    $content = $_POST['content'];
    $active  = $_POST['active'];
    
    // Mise à jour
    $stmt = mysqli_prepare($connect, "UPDATE pages SET title=?, slug=?, content=?, active=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $title, $slug, $content, $active, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<div class="alert alert-success m-3">Page updated successfully! Redirecting...</div>';
    echo '<meta http-equiv="refresh" content="1; url=pages.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit"></i> Edit Page</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="pages.php">Pages</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                                <label>Page Title</label>
                                <input class="form-control form-control-lg" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" type="text" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Content</label>
                                <textarea class="form-control" id="summernote" name="content" rows="15" required><?php echo html_entity_decode($row['content']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-12">
                    
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Publishing</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control" required>
                                    <option value="Yes" <?php if ($row['active'] == 'Yes') echo 'selected'; ?>>Published</option>
                                    <option value="No" <?php if ($row['active'] == 'No') echo 'selected'; ?>>Draft</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Slug (Preview)</label>
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['slug']); ?>" disabled>
                                <small class="text-muted">Slug updates automatically with title.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Page
                            </button>
                            <a href="pages.php" class="btn btn-default btn-block">Cancel</a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>