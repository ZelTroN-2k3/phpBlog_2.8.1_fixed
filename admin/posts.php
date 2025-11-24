<?php
include "header.php";

// --- LOGIQUE ADMIN : APPROBATION / REJET ---
if ($user['role'] == 'Admin') {
    // Approuver un article
    if (isset($_GET['approve-id'])) {
        validate_csrf_token_get();
        $post_id = (int)$_GET['approve-id'];
        $stmt = mysqli_prepare($connect, "UPDATE posts SET active='Yes' WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo '<meta http-equiv="refresh" content="0; url=posts.php">';
        exit;
    }

    // Rejeter un article
    if (isset($_GET['reject-id'])) {
        validate_csrf_token_get();
        $post_id = (int)$_GET['reject-id'];
        $stmt = mysqli_prepare($connect, "DELETE FROM posts WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo '<meta http-equiv="refresh" content="0; url=posts.php">';
        exit;
    }
}

// --- LOGIQUE SUPPRESSION (Pour tous) ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    // 1. Supprimer les commentaires liés
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 2. Supprimer les liaisons de tags
    $stmt_tags = mysqli_prepare($connect, "DELETE FROM `post_tags` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt_tags, "i", $id);
    mysqli_stmt_execute($stmt_tags);
    mysqli_stmt_close($stmt_tags);

    // 3. Supprimer l'article
    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=posts.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list"></i> All Posts</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Posts</li>
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
                            <a href="add_post.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add New Post
                            </a>
                        </h3>
                        
                        <div class="card-tools">
                            <div class="btn-group">
                                <a href="posts.php" class="btn btn-sm btn-default">All</a>
                                <a href="posts.php?status=published" class="btn btn-sm btn-default text-success">Published</a>
                                <a href="posts.php?status=draft" class="btn btn-sm btn-default text-warning">Drafts</a>
                                <a href="posts.php?status=pending" class="btn btn-sm btn-default text-info">Pending</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body"> 
                        <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:50px;">Image</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date</th>
                                    <th>Status</th> 
                                    <th>Category</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
// --- FILTRES SQL ---
$where_clause = "";
if (isset($_GET['status'])) {
    $status_code = $_GET['status'];
    if ($status_code == 'draft') { $where_clause = "WHERE p.active = 'Draft'"; } 
    elseif ($status_code == 'pending') { $where_clause = "WHERE p.active = 'Pending'"; } 
    elseif ($status_code == 'published') { $where_clause = "WHERE p.active = 'Yes'"; } 
    elseif ($status_code == 'trash') { $where_clause = "WHERE p.active = 'No'"; }
}

// Requête optimisée
$query = "
    SELECT 
        p.*, 
        c.category AS category_name, 
        u.username AS author_name,
        u.avatar AS author_avatar
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    $where_clause
    ORDER BY p.id DESC
";
$sql = mysqli_query($connect, $query);

while ($row = mysqli_fetch_assoc($sql)) {
    // Gestion affichage Featured
    $featured = "";
    if($row['featured'] == "Yes") {
        $featured = '<span class="badge badge-primary ml-1" title="Featured Post"><i class="fas fa-star"></i></span>';
    }

    // Gestion Avatar Auteur
    $author_avatar = !empty($row['author_avatar']) ? $row['author_avatar'] : 'assets/img/avatar.png';
    
    echo '
    <tr>
        <td class="text-center">';
    if ($row['image'] != '') {
        echo '<img src="../' . htmlspecialchars($row['image']) . '" width="50" height="50" style="object-fit: cover; border-radius: 4px;" />';
    } else {
        echo '<span class="text-muted"><i class="fas fa-image fa-2x"></i></span>';
    }
    echo '</td>
        <td>' . htmlspecialchars($row['title']) . ' ' . $featured . '</td>
        <td>
            <img src="../' . htmlspecialchars($author_avatar) . '" width="40" height="40" class="img-circle elevation-1" alt="User"> 
            ' . htmlspecialchars($row['author_name'] ?? 'N/A') . '
        </td>
        <td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'], strtotime($row['created_at'])) . '</td>
        
        <td>';
    if($row['active'] == "Yes") {
        echo '<span class="badge badge-success">Published</span>';
    } else if ($row['active'] == 'Pending') {
        echo '<span class="badge badge-info">Pending</span>';
    } else {
        echo '<span class="badge badge-warning">Draft</span>';
    }
    echo '</td>
        <td>' . htmlspecialchars($row['category_name'] ?? 'Uncategorized') . '</td>
        
        <td class="text-center">
            <a href="../post?name=' . htmlspecialchars($row['slug']) . '" target="_blank" class="btn btn-secondary btn-sm mr-1" title="View on site">
                <i class="fas fa-eye"></i>
            </a>';

            // Boutons Admin
            if ($user['role'] == 'Admin' && $row['active'] == 'Pending') {
                echo '<a href="?approve-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-success btn-sm mr-1" title="Approve"><i class="fa fa-check"></i></a>';
                echo '<a href="?reject-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-warning btn-sm mr-1" onclick="return confirm(\'Reject this post?\');" title="Reject"><i class="fa fa-times"></i></a>';
            }
            
    // Notez que je n'ouvre PAS de nouveau <td> ici, je continue dans le même
    echo '
            <a href="edit_post.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm mr-1" title="Edit"><i class="fa fa-edit"></i></a>
            <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this post?\');" title="Delete"><i class="fa fa-trash"></i></a>    
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
    $('#dt-basic').DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "order": [[ 3, "desc" ]] // Trier par date par défaut
    });
});
</script>

<?php
include "footer.php";
?>