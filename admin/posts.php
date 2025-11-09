<?php
include "header.php";

// --- NOUVELLE LOGIQUE POUR LES ACTIONS EN MASSE ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $post_ids = $_POST['post_ids'] ?? [];

    if (!empty($action) && !empty($post_ids)) {
        // Crée une chaîne de placeholders (?,?,?) pour la requête IN
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        $types = str_repeat('i', count($post_ids));

        if ($action == 'publish') {
            $stmt = mysqli_prepare($connect, "UPDATE posts SET active = 'Yes' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$post_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'draft') {
            // "Draft" est le statut pour les brouillons
            $stmt = mysqli_prepare($connect, "UPDATE posts SET active = 'Draft' WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, $types, ...$post_ids);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($action == 'delete') {
            // Supprimer toutes les données associées (commentaires, tags, favoris, likes)
            
            $stmt_comments = mysqli_prepare($connect, "DELETE FROM comments WHERE post_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_comments, $types, ...$post_ids);
            mysqli_stmt_execute($stmt_comments);
            mysqli_stmt_close($stmt_comments);
            
            $stmt_tags = mysqli_prepare($connect, "DELETE FROM post_tags WHERE post_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_tags, $types, ...$post_ids);
            mysqli_stmt_execute($stmt_tags);
            mysqli_stmt_close($stmt_tags);

            $stmt_likes = mysqli_prepare($connect, "DELETE FROM post_likes WHERE post_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_likes, $types, ...$post_ids);
            mysqli_stmt_execute($stmt_likes);
            mysqli_stmt_close($stmt_likes);
            
            $stmt_favs = mysqli_prepare($connect, "DELETE FROM user_favorites WHERE post_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_favs, $types, ...$post_ids);
            mysqli_stmt_execute($stmt_favs);
            mysqli_stmt_close($stmt_favs);

            // Finalement, supprimer les articles
            $stmt_posts = mysqli_prepare($connect, "DELETE FROM posts WHERE id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_posts, $types, ...$post_ids);
            mysqli_stmt_execute($stmt_posts);
            mysqli_stmt_close($stmt_posts);
        }
        echo '<meta http-equiv="refresh" content="0; url=posts.php">';
        exit;
    }
}
// --- FIN DE LA LOGIQUE EN MASSE ---


// --- LOGIQUE D'APPROBATION/REJET ---
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

    // Rejeter (supprimer) un article
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
// --- FIN DE LA NOUVELLE LOGIQUE ---

if (isset($_GET['delete-id'])) {
    // La logique PHP de suppression reste la même
    $id     = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // MODIFICATION : Supprimer aussi les liaisons de tags
    $stmt_tags = mysqli_prepare($connect, "DELETE FROM `post_tags` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt_tags, "i", $id);
    mysqli_stmt_execute($stmt_tags);
    mysqli_stmt_close($stmt_tags);
    // FIN MODIFICATION

    // --- AJOUT : Supprimer les likes et favoris associés ---
    $stmt_likes = mysqli_prepare($connect, "DELETE FROM `post_likes` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt_likes, "i", $id);
    mysqli_stmt_execute($stmt_likes);
    mysqli_stmt_close($stmt_likes);
    
    $stmt_favs = mysqli_prepare($connect, "DELETE FROM `user_favorites` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt_favs, "i", $id);
    mysqli_stmt_execute($stmt_favs);
    mysqli_stmt_close($stmt_favs);
    // --- FIN AJOUT ---

    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=posts.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list"></i> Posts</h1>
            </div>
            <div class_name="col-sm-6">
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
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // ... (Toute la logique PHP de récupération des données reste la même) ...
    $stmt = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=posts.php">';
        exit;
    }
    
    $tags_value = '';
    $stmt_get_tags = mysqli_prepare($connect, "
        SELECT t.name 
        FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
    ");
    mysqli_stmt_bind_param($stmt_get_tags, "i", $id);
    mysqli_stmt_execute($stmt_get_tags);
    $result_tags = mysqli_stmt_get_result($stmt_get_tags);
    $existing_tags = [];
    while ($row_tag = mysqli_fetch_assoc($result_tags)) {
        $existing_tags[] = $row_tag['name'];
    }
    mysqli_stmt_close($stmt_get_tags);
    $tags_value = implode(',', $existing_tags);
    
    
    if (isset($_POST['submit'])) {
        // ... (Toute la logique PHP de soumission du formulaire reste la même) ...
        validate_csrf_token();
        
        $title       = $_POST['title'];
        $slug        = generateSeoURL($title);
        $image       = $row['image'];
        $active      = $_POST['active']; 
        $featured    = $_POST['featured'];
        $category_id = $_POST['category_id'];
        $content     = htmlspecialchars($_POST['content']);
        $download_link = $_POST['download_link'];
        $github_link   = $_POST['github_link'];
        $publish_at  = $_POST['publish_at'];

        if (@$_FILES['image']['name'] != '') {
            // ... (les vérifications getimagesize et size restent les mêmes) ...
            if ($uploadOk == 1) {
                // --- MODIFICATION ---
                $string     = "0123456789wsderfgtyhjuk";
                $new_string = str_shuffle($string);
                // Chemin de destination SANS extension
                $destination_path = "../uploads/posts/image_$new_string"; 
                
                $optimized_path = optimize_and_save_image($_FILES["image"]["tmp_name"], $destination_path);
                
                if ($optimized_path) {
                    // Enlever le préfixe '../' pour le stockage en BDD
                    $image = str_replace('../', '', $optimized_path);
                } else {
                    $uploadOk = 0; 
                    echo '<div class="alert alert-danger">An error occurred while processing the image.</div>';
                }
                // --- FIN MODIFICATION ---
            }
        }
        
        if ($uploadOk == 1) { // S'assurer que uploadOk est vérifié
        
        $stmt = mysqli_prepare($connect, "UPDATE posts SET title=?, slug=?, image=?, active=?, featured=?, category_id=?, content=?, download_link=?, github_link=?, publish_at=?, created_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssissssi", $title, $slug, $image, $active, $featured, $category_id, $content, $download_link, $github_link, $publish_at, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        }
        
        // --- GESTION DES TAGS (MISE À JOUR) ---
        $post_id = $id; 
        $new_tag_slugs = []; 
        
        if (!empty($_POST['tags'])) {
            $tags_json = $_POST['tags'];
            $tags_array = json_decode($tags_json);
            
            if (is_array($tags_array) && !empty($tags_array)) {
                
                $stmt_tag_find = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE slug = ? LIMIT 1");
                $stmt_tag_insert = mysqli_prepare($connect, "INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt_post_tag_insert = mysqli_prepare($connect, "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                
                foreach ($tags_array as $tag_obj) {
                    $tag_name = $tag_obj->value;
                    $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name), '-'));
                    if (empty($tag_slug)) continue;
                    $new_tag_slugs[] = $tag_slug; 
                    mysqli_stmt_bind_param($stmt_tag_find, "s", $tag_slug);
                    mysqli_stmt_execute($stmt_tag_find);
                    $result_tag = mysqli_stmt_get_result($stmt_tag_find);
                    
                    if ($row_tag = mysqli_fetch_assoc($result_tag)) {
                        $tag_id = $row_tag['id'];
                    } else {
                        mysqli_stmt_bind_param($stmt_tag_insert, "ss", $tag_name, $tag_slug);
                        mysqli_stmt_execute($stmt_tag_insert);
                        $tag_id = mysqli_insert_id($connect);
                    }
                    
                    mysqli_stmt_bind_param($stmt_post_tag_insert, "ii", $post_id, $tag_id);
                    @mysqli_stmt_execute($stmt_post_tag_insert);
                }
                
                mysqli_stmt_close($stmt_tag_find);
                mysqli_stmt_close($stmt_tag_insert);
                mysqli_stmt_close($stmt_post_tag_insert);
            }
        }
        
        if (!empty($existing_tags)) {
            $stmt_get_tag_id_slug = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE name = ?");
            $stmt_delete_link = mysqli_prepare($connect, "DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?");

            foreach ($existing_tags as $old_tag_name) {
                mysqli_stmt_bind_param($stmt_get_tag_id_slug, "s", $old_tag_name);
                mysqli_stmt_execute($stmt_get_tag_id_slug);
                $result_old_tag = mysqli_stmt_get_result($stmt_get_tag_id_slug);
                
                if ($row_old_tag = mysqli_fetch_assoc($result_old_tag)) {
                    $old_tag_slug = $row_old_tag['slug'];
                    $old_tag_id = $row_old_tag['id'];
                    if (!in_array($old_tag_slug, $new_tag_slugs)) {
                        mysqli_stmt_bind_param($stmt_delete_link, "ii", $post_id, $old_tag_id);
                        mysqli_stmt_execute($stmt_delete_link);
                    }
                }
            }
            mysqli_stmt_close($stmt_get_tag_id_slug);
            mysqli_stmt_close($stmt_delete_link);
        }
        // --- FIN GESTION DES TAGS ---

        echo '<meta http-equiv="refresh" content="0;url=posts.php">';
    }
?>
    <div class="card card-primary card-outline mb-3">
        <div class="card-header">
            <h3 class="card-title">Edit Post</h3>
        </div>
		<div class="card-body">
			<form name="post_form" action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <p>
					<label>Title</label>
					<input class="form-control" name="title" id="title" type="text" value="<?php
echo htmlspecialchars($row['title']);
?>" oninput="countText()" required>
					<i>For best SEO keep title under 50 characters.</i>
					<label for="characters">Characters: </label>
					<span id="characters"><?php
echo strlen($row['title']);
?></span><br>
				</p>
				<p>
					<label>Image</label><br />
                    <?php
                    if ($row['image'] != '') {
                        echo '<img src="../' . $row['image'] . '" width="50px" height="50px" class="mb-2" /><br />';
                    }
                    ?>
					<input type="file" name="image" class="form-control" />
				</p>
				
                <p>
				<label>Status</label><br />
				<select name="active" class="form-control" required>
					<option value="Draft" <?php if (trim($row['active']) == "Draft") { echo 'selected'; } ?>>Draft</option>
                    <option value="Yes" <?php if (trim($row['active']) == "Yes") { echo 'selected'; } ?>>Published</option>
					<option value="No" <?php if (trim($row['active']) == "No") { echo 'selected'; } ?>>Inactive</option>
                    <option value="Pending" <?php if (trim($row['active']) == "Pending") { echo 'selected'; } ?>>Pending</option>
				</select>
				</p>
                <p>
					<label>Featured</label><br />
					<select name="featured" class="form-control" required>
						<option value="Yes" <?php if ($row['featured'] == "Yes") { echo 'selected'; } ?>>Yes</option>
						<option value="No" <?php if ($row['featured'] == "No") { echo 'selected'; } ?>>No</option>
					</select>
				</p>
                <p>
                    <label>Publication Date</label>
                    <input type="datetime-local" class="form-control" name="publish_at" value="<?php echo date('Y-m-d\TH:i', strtotime($row['publish_at'])); ?>" required>
                </p>                
				<p>
					<label>Category</label><br />
					<select name="category_id" class="form-control" required>
                    <?php
                    $crun = mysqli_query($connect, "SELECT * FROM `categories`");
                    while ($rw = mysqli_fetch_assoc($crun)) {
                        $selected = "";
                        if ($row['category_id'] == $rw['id']) {
                            $selected = "selected";
                        }
                        echo '<option value="' . $rw['id'] . '" ' . $selected . '>' . $rw['category'] . '</option>';
                    }
                    ?>
					</select>
				</p>
				
				<p>
					<label>Tags</label>
					<input name="tags" class="form-control" value="<?php echo htmlspecialchars($tags_value); ?>" placeholder="php, javascript, css">
					<i>Separate tags with a comma or Enter.</i>
				</p>
				<p>
					<label>Download link (.rar, .zip)</label>
					<div class="input-group">
                        <div class="input-group-prepend">
						    <span class="input-group-text"><i class="fas fa-file-archive"></i></span>
                        </div>
						<input class="form-control" name="download_link" value="<?php echo htmlspecialchars($row['download_link']); ?>" type="url" placeholder="https://.../file.zip">
					</div>
				</p>
				<p>
					<label>GitHub link</label>
					<div class="input-group">
                        <div class="input-group-prepend">
						    <span class="input-group-text"><i class="fab fa-github"></i></span>
                        </div>
						<input class="form-control" name="github_link" value="<?php echo htmlspecialchars($row['github_link']); ?>" type="url" placeholder="https://github.com/user/repo">
					</div>
				</p>
				<p>
					<label>Content</label>
					<textarea name="content" id="summernote" rows="8" required><?php
echo html_entity_decode($row['content']);
?></textarea>
				</p>

				<input type="submit" class="btn btn-primary col-12" name="submit" value="Save" /><br />
			</form>
		</div>
	</div>
<?php
}
?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <a href="add_post.php" class="btn btn-primary"><i class="fa fa-edit"></i> Add Post</a>
            </h3>
        </div>
        
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="card-body"> 
                <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 10px;"><input type="checkbox" id="select-all"></th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Statut</th> 
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
// Requête optimisée avec JOIN pour récupérer toutes les informations nécessaires
$query = "
    SELECT 
        p.*, 
        c.category AS category_name, 
        u.username AS author_name,
        u.avatar AS author_avatar
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.id DESC
";
$sql = mysqli_query($connect, $query);

while ($row = mysqli_fetch_assoc($sql)) {
    $featured = "";
	if($row['featured'] == "Yes") {
		$featured = '<span class="badge bg-primary">Featured</span>';
	}

    // Définir un avatar par défaut si celui de l'auteur est vide
    $author_avatar = !empty($row['author_avatar']) ? htmlspecialchars($row['author_avatar']) : 'assets/img/avatar.png';
    
    echo '
					<tr>
                        <td><input type="checkbox" name="post_ids[]" value="' . $row['id'] . '"></td>
						<td>';
    if ($row['image'] != '') {
        echo '
	                <center><img src="../' . htmlspecialchars($row['image']) . '" width="45px" height="45px" /></center>
					';
    }
    echo '</td>
						<td>' . htmlspecialchars($row['title']) . ' ' . $featured . '</td>
						<td><img src="../' . $author_avatar . '" width="45px" height="45px" class="img-circle elevation-2" alt="Author Avatar"> ' . htmlspecialchars($row['author_name'] ?? 'N/A') . '</td>
						<td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</td>
						
                        <td>';
    if($row['active'] == "Yes") {
		echo '<span class="badge bg-success">Published</span>';
	} else if ($row['active'] == 'Pending') {
        echo '<span class="badge bg-info">Pending</span>';
    } else if ($row['active'] == 'Draft') {
		echo '<span class="badge bg-warning">Draft</span>';
	} else {
        echo '<span class="badge bg-danger">' . htmlspecialchars($row['active']) . '</span>';
    }
	echo '</td>
                        <td>' . htmlspecialchars($row['category_name'] ?? 'N/A') . '</td>
					<td>';
    if ($user['role'] == 'Admin' && $row['active'] == 'Pending') {
        echo '<a href="?approve-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Approve</a> ';
        echo '<a href="?reject-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-warning btn-sm" onclick="return confirm(\'Are you sure you want to reject this post?\');"><i class="fa fa-times"></i> Reject</a> ';
    }
    echo '<a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this post?\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>

	';
}
?>
				</tbody>
			</table>
		</div>
        
        <div class="card-footer">
            <div class="mt-0">
                <select name="bulk_action" class="form-control" style="width: 200px; display: inline-block;">
                    <option value="">Mass actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Set as Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" name="apply_bulk_action" class="btn btn-primary">Apply</button>
            </div>
        </div>
        </form> </div>

    </div></section>
<script>
$(document).ready(function() {
	
    // MODIFICATION : Initialiser DataTable dans une variable et ajouter columnDefs
	var table = $('#dt-basic').DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 4, "desc" ]], // Ordonner par date (maintenant 5ème colonne, index 4)
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Désactiver le tri sur la colonne 0 (checkbox)
        ]
	});
	
    // MODIFICATION : Ajout de la logique "select-all"
    $('#select-all').on('click', function(){
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });
	
	// L'activation de Summernote est dans footer.php

	// --- DÉBUT INITIALISATION TAGIFY ---
	var input = document.querySelector('input[name=tags]');
	if(input) { // S'assurer que l'input existe (formulaire d'édition/ajout)
		new Tagify(input, {
			duplicate: false, 
			delimiters: ",", 
			addTagOnBlur: true 
		});
	}
	// --- FIN INITIALISATION TAGIFY ---
} );
</script>
<?php
include "footer.php";
?>