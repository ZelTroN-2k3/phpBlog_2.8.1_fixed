<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    // --- (Toute votre logique PHP de traitement du formulaire reste identique) ---
    $title       = $_POST['title'];
    $slug        = generateSeoURL($title);
    $active      = $_POST['active']; // Sera "Draft", "Yes" ou "No"
    $featured    = $_POST['featured'];
    $category_id = $_POST['category_id'];
    $content     = $_POST['content'];
    $publish_at  = $_POST['publish_at']; // Récupère la date
    
    $download_link = $_POST['download_link'];
    $github_link   = $_POST['github_link'];
    
    $author_id = null;
    $author    = $uname;
    
    // Use prepared statement to get author_id
    $stmt = mysqli_prepare($connect, "SELECT id FROM `users` WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $author);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($auth = mysqli_fetch_assoc($result)) {
        $author_id = $auth['id'];
    }
    mysqli_stmt_close($stmt);

    $image = '';
    
    if (@$_FILES['image']['name'] != '') {
        $target_dir    = "uploads/posts/";
        $target_file   = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["image"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            // --- MODIFICATION ---
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            // Chemin de destination SANS extension
            $destination_path = "../uploads/posts/image_$new_string"; 
            
            // Appeler la nouvelle fonction d'optimisation
            $optimized_path = optimize_and_save_image($_FILES["image"]["tmp_name"], $destination_path);
            
            if ($optimized_path) {
                // Enlever le préfixe '../' pour le stockage en BDD
                $image = str_replace('../', '', $optimized_path); 
            } else {
                $uploadOk = 0; // Marquer comme échec si l'optimisation rate
                echo '<div class="alert alert-danger">An error occurred while processing the image.</div>';
            }
            // --- FIN MODIFICATION ---
        }
    }
    
    if ($author_id && $uploadOk == 1) { // Assurez-vous que $uploadOk est vérifié
        // Insertion de l'article (sans les tags pour l'instant)
        $stmt = mysqli_prepare($connect, "INSERT INTO `posts` (category_id, title, slug, author_id, image, content, active, featured, download_link, github_link, publish_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "ississsssss", $category_id, $title, $slug, $author_id, $image, $content, $active, $featured, $download_link, $github_link, $publish_at);
        mysqli_stmt_execute($stmt);
        $post_id = mysqli_insert_id($connect); // Récupérer l'ID du nouvel article
        mysqli_stmt_close($stmt);

        // --- DÉBUT GESTION DES TAGS ---
        if ($post_id && !empty($_POST['tags'])) {
            $tags_json = $_POST['tags'];
            $tags_array = json_decode($tags_json);
            
            if (is_array($tags_array) && !empty($tags_array)) {
                
                $stmt_tag_find = mysqli_prepare($connect, "SELECT id FROM tags WHERE slug = ? LIMIT 1");
                $stmt_tag_insert = mysqli_prepare($connect, "INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt_post_tag_insert = mysqli_prepare($connect, "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                
                foreach ($tags_array as $tag_obj) {
                    $tag_name = $tag_obj->value;
                    $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name), '-'));
                    
                    if (empty($tag_slug)) continue;

                    // 1. Vérifier si le tag existe
                    mysqli_stmt_bind_param($stmt_tag_find, "s", $tag_slug);
                    mysqli_stmt_execute($stmt_tag_find);
                    $result_tag = mysqli_stmt_get_result($stmt_tag_find);
                    
                    if ($row_tag = mysqli_fetch_assoc($result_tag)) {
                        $tag_id = $row_tag['id'];
                    } else {
                        // 2. S'il n'existe pas, le créer
                        mysqli_stmt_bind_param($stmt_tag_insert, "ss", $tag_name, $tag_slug);
                        mysqli_stmt_execute($stmt_tag_insert);
                        $tag_id = mysqli_insert_id($connect);
                    }
                    
                    // 3. Lier le tag à l'article (ignorer si la liaison existe déjà)
                    mysqli_stmt_bind_param($stmt_post_tag_insert, "ii", $post_id, $tag_id);
                    @mysqli_stmt_execute($stmt_post_tag_insert);
                }
                
                mysqli_stmt_close($stmt_tag_find);
                mysqli_stmt_close($stmt_tag_insert);
                mysqli_stmt_close($stmt_post_tag_insert);
            }
        }
        // --- FIN GESTION DES TAGS ---

        // ... (partie newsletter) ...
        if ($post_id && $active == 'Yes') {
            $from     = $settings['email'];
            $sitename = $settings['sitename'];
            
            $run2 = mysqli_query($connect, "SELECT * FROM `newsletter`");
            while ($row = mysqli_fetch_assoc($run2)) {

                $to = $row['email'];
                $subject = $title;
                $message = '
<html>
<body>
  <b><h1>' . $settings['sitename'] . '</h1><b/>
  <h2>New post: <b><a href="' . $settings['site_url'] . '/post?name=' . $slug . '" title="Read more">' . $title . '</a></b></h2><br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
                
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $headers .= 'From: ' . $from . '';
                
                @mail($to, $subject, $message, $headers);
            }
        }
    }
    
    echo '<meta http-equiv="refresh" content="0;url=posts.php">';
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit"></i> Add Post</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="posts.php">Posts</a></li>
                    <li class="breadcrumb-item active">Add Post</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Create a new post</h3>
            </div>
            <div class="card-body">
                <form name="post_form" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <p>
						<label>Title</label>
						<input class="form-control" name="title" id="title" value="" type="text" oninput="countText()" required>
						<i>For best SEO keep title under 50 characters.</i>
						<label for="characters">Characters: </label>
						<span id="characters">0</span><br>
					</p>
					<p>
						<label>Image</label>
						<input type="file" name="image" class="form-control" />
					</p>
					
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="active" class="form-control" required>
                                    <option value="Draft" selected>Draft (draft)</option>
                                    <option value="Yes">Published (public)</option>
                                    <option value="No">Inactive (hidden)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Featured</label>
                                <select name="featured" class="form-control" required>
                                    <option value="Yes">Yes</option>
                                    <option value="No" selected>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
						<label>Publication Date</label>
						<input type="datetime-local" class="form-control" name="publish_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
						<i>By default, it's now. Change to schedule publication.</i>
					</div>                    
					<div class="form-group">
						<label>Category</label>
						<select name="category_id" class="form-control" required>
                        <?php
                        $crun = mysqli_query($connect, "SELECT * FROM `categories`");
                        while ($rw = mysqli_fetch_assoc($crun)) {
                            echo '
                                    <option value="' . $rw['id'] . '">' . $rw['category'] . '</option>
                                            ';
                        }
                        ?>
						</select>
					</div>
					
					<div class="form-group">
						<label>Tags</label>
						<input name="tags" class="form-control" value="" placeholder="php, javascript, css">
						<i>Separate tags with a comma or Enter.</i>
					</div>
					<div class="form-group">
						<label>Download link (.rar, .zip)</label>
						<div class="input-group">
                            <div class="input-group-prepend">
							    <span class="input-group-text"><i class="fas fa-file-archive"></i></span>
                            </div>
							<input class="form-control" name="download_link" value="" type="url" placeholder="https://.../file.zip">
						</div>
					</div>
					<div class="form-group">
						<label>GitHub link</label>
						<div class="input-group">
                            <div class="input-group-prepend">
							    <span class="input-group-text"><i class="fab fa-github"></i></span>
                            </div>
							<input class="form-control" name="github_link" value="" type="url" placeholder="https://github.com/user/repo">
						</div>
					</div>
					<div class="form-group">
						<label>Content</label>
						<textarea class="form-control" id="summernote" rows="8" name="content" required></textarea>
					</div>
								
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                      
            </div>
        </div>

    </div></section>
<script>
$(document).ready(function() {
	// L'activation de Summernote est dans footer.php

	// --- DÉBUT INITIALISATION TAGIFY ---
	var input = document.querySelector('input[name=tags]');
	new Tagify(input, {
		duplicate: false, 
		delimiters: ",", 
		addTagOnBlur: true 
	});
	// --- FIN INITIALISATION TAGIFY ---
});
</script>
<?php
include "footer.php";
?>