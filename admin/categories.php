<?php
include "header.php";

// --- LOGIQUE SUPPRESSION ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    // Récupérer l'image pour la supprimer du serveur
    $stmt_img = mysqli_prepare($connect, "SELECT image FROM categories WHERE id=?");
    mysqli_stmt_bind_param($stmt_img, "i", $id);
    mysqli_stmt_execute($stmt_img);
    $res_img = mysqli_stmt_get_result($stmt_img);
    
    if ($r = mysqli_fetch_assoc($res_img)) {
        if (!empty($r['image']) && file_exists("../" . $r['image'])) {
            unlink("../" . $r['image']);
        }
    }
    mysqli_stmt_close($stmt_img);

    // Supprimer les articles liés (Attention : c'est destructif !)
    $stmt_posts = mysqli_prepare($connect, "DELETE FROM `posts` WHERE category_id=?");
    mysqli_stmt_bind_param($stmt_posts, "i", $id);
    mysqli_stmt_execute($stmt_posts);
    mysqli_stmt_close($stmt_posts);

    // Supprimer la catégorie
    $stmt = mysqli_prepare($connect, "DELETE FROM `categories` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=categories.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder"></i> Categories</h1>
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
        <div class="row">
            <div class="col-12">
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="add_category.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add Category
                            </a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <table class="table table-bordered table-hover" id="dt-categories" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 100px;" class="text-center">Image</th>
                                    <th>Name & Description</th>
                                    <th>Slug</th>
                                    <th class="text-center">Posts</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM `categories` ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
    
    // Compter les articles dans cette catégorie
    $cat_id = $row['id'];
    $count_query = mysqli_query($connect, "SELECT COUNT(*) as total FROM posts WHERE category_id='$cat_id'");
    $count_data = mysqli_fetch_assoc($count_query);
    $article_count = $count_data['total'];
    
    // Badge couleur selon nombre d'articles
    $badge_color = ($article_count > 0) ? 'badge-info' : 'badge-secondary';
    
    // Image
    if ($row['image'] != '') {
        $img_display = '<img src="../' . htmlspecialchars($row['image']) . '" width="50" height="30" style="object-fit: cover; border-radius: 3px;">';
    } else {
        $img_display = '<span class="text-muted"><i class="fas fa-image"></i></span>';
    }

    echo '
        <tr>
            <td>' . $row['id'] . '</td>
            <td class="text-center">' . $img_display . '</td>
            <td>
                <b>' . htmlspecialchars($row['category']) . '</b><br>
                <small class="text-muted">' . htmlspecialchars(substr($row['description'], 0, 60)) . (strlen($row['description']) > 60 ? '...' : '') . '</small>
            </td>
            <td><code class="text-muted">' . htmlspecialchars($row['slug']) . '</code></td>
            <td class="text-center"><span class="badge ' . $badge_color . '">' . $article_count . '</span></td>
            
            <td class="text-center">
                <a href="../category?name=' . htmlspecialchars($row['slug']) . '" target="_blank" class="btn btn-secondary btn-sm mr-1" title="View on site">
                    <i class="fas fa-eye"></i>
                </a>
                
                <a href="edit_category.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm mr-1" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                
                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'WARNING: Deleting this category will DELETE ALL ' . $article_count . ' POSTS inside it. Are you sure?\');" title="Delete">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
}
?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#dt-categories').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 0, "desc" ]]
    });
});
</script>

<?php include "footer.php"; ?>