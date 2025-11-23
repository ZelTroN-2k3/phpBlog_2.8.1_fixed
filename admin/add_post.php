<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- Validation CSRF ---
    validate_csrf_token();

    $title       = $_POST['title'];
    $slug        = generateSeoURL($title);
    $active      = $_POST['active']; 
    $featured    = $_POST['featured'];
    $category_id = $_POST['category_id'];
    $content     = $_POST['content'];
    $publish_at  = $_POST['publish_at']; 
    
    $download_link = $_POST['download_link'];
    $github_link   = $_POST['github_link'];
    
    $author_id = null;
    $author    = $uname;
    
    // Récupération ID auteur
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
        
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        if ($_FILES["image"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            
            $upload_dir = "../uploads/posts/";
            $destination_path_no_ext = $upload_dir . "image_$new_string";

            $optimized_full_path = optimize_and_save_image($_FILES["image"]["tmp_name"], $destination_path_no_ext);
            
            if ($optimized_full_path) {
                $image = ltrim($optimized_full_path, './'); 
                $image = str_replace('../', '', $image);
            } else {
                $uploadOk = 0; 
                echo '<div class="alert alert-danger">An error occurred while processing the image.</div>';
            }
        }
    }
    
    if ($author_id && $uploadOk == 1) { 
        $stmt = mysqli_prepare($connect, "INSERT INTO `posts` (category_id, title, slug, author_id, image, content, active, featured, download_link, github_link, publish_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "ississsssss", $category_id, $title, $slug, $author_id, $image, $content, $active, $featured, $download_link, $github_link, $publish_at);
        mysqli_stmt_execute($stmt);
        $post_id = mysqli_insert_id($connect); 
        mysqli_stmt_close($stmt);

        // --- GESTION DES TAGS ---
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

        // --- Newsletter ---
        if ($post_id && $active == 'Yes') {
            $from     = $settings['email'];
            $sitename = $settings['sitename'];
            
            $run2 = mysqli_query($connect, "SELECT * FROM `newsletter`");
            while ($row = mysqli_fetch_assoc($run2)) {
                $to = $row['email'];
                $subject = $title;
                $message = '<html><body><b><h1>' . $settings['sitename'] . '</h1><b/><h2>New post: <b><a href="' . $settings['site_url'] . '/post?name=' . $slug . '" title="Read more">' . $title . '</a></b></h2><br />' . html_entity_decode($content) . '<hr /><i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i></body></html>';
                $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=utf-8' . "\r\n" . 'From: ' . $from . '';
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
        <form name="post_form" action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="row">
                
                <div class="col-lg-9 col-md-12">
                    
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Content</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title</label>
                                <input class="form-control form-control-lg" name="title" id="title" value="" type="text" oninput="countText()" placeholder="Enter post title" required>
                                <small class="text-muted"><i>For best SEO keep title under 50 characters. Current: <span id="characters">0</span></i></small>
                            </div>
                            
                            <div class="form-group">
                                <label>Content</label>
                                <textarea class="form-control" id="summernote" rows="15" name="content" required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-link"></i> Attachments & Links</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Download link (.rar, .zip)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-file-archive"></i></span>
                                            </div>
                                            <input class="form-control" name="download_link" value="" type="url" placeholder="https://.../file.zip">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>GitHub link</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fab fa-github"></i></span>
                                            </div>
                                            <input class="form-control" name="github_link" value="" type="url" placeholder="https://github.com/user/repo">
                                        </div>
                                    </div>
                                </div>
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
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Yes">Published</option>
                                    <option value="No">Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Publication Date</label>
                                <input type="datetime-local" class="form-control" name="publish_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                             <div class="form-group">
                                <label>Featured Post?</label>
                                <select name="featured" class="form-control" required>
                                    <option value="Yes">Yes</option>
                                    <option value="No" selected>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="submit" name="add" class="btn btn-primary btn-block" value="Publish Post" />
                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Organization</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control" required>
                                <?php
                                $crun = mysqli_query($connect, "SELECT * FROM `categories`");
                                while ($rw = mysqli_fetch_assoc($crun)) {
                                    echo '<option value="' . $rw['id'] . '">' . $rw['category'] . '</option>';
                                }
                                ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tags</label>
                                <input name="tags" class="form-control" value="" placeholder="Add tags...">
                                <small class="text-muted">Separate with enter or comma.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Featured Image</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-file">
                                    <input type="file" name="image" class="custom-file-input" id="postImage">
                                    <label class="custom-file-label" for="postImage">Choose file</label>
                                </div>
                                <small class="text-muted">Max size: 10MB. JPG/PNG/WEBP</small>
                            </div>
                        </div>
                    </div>

                </div> </div> </form>
    </div>
</section>

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

    // Petit script pour afficher le nom du fichier dans l'input file bootstrap
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
});
</script>

<?php
include "footer.php";
?>