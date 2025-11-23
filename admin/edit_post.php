<?php
include "header.php";

// 1. Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0; url=posts.php">';
    exit;
}

$id = (int)$_GET['id'];

// 2. Récupération des données de l'article
$stmt = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    echo '<div class="alert alert-danger">Post not found.</div>';
    exit;
}

// 3. Récupération des Tags existants pour l'affichage
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
$existing_tags_array = [];
while ($row_tag = mysqli_fetch_assoc($result_tags)) {
    $existing_tags_array[] = $row_tag['name'];
}
mysqli_stmt_close($stmt_get_tags);
$tags_value = implode(',', $existing_tags_array);

// 4. Traitement du Formulaire
if (isset($_POST['submit'])) {
    validate_csrf_token();

    $title       = $_POST['title'];
    $slug        = generateSeoURL($title); // On régénère le slug si le titre change (optionnel)
    $active      = $_POST['active']; 
    $featured    = $_POST['featured'];
    $category_id = $_POST['category_id'];
    $content     = $_POST['content'];
    $publish_at  = $_POST['publish_at'];
    $download_link = $_POST['download_link'];
    $github_link   = $_POST['github_link'];

    // Gestion Image
    $image = $row['image']; // Par défaut, on garde l'ancienne
    if (@$_FILES['image']['name'] != '') {
        $target_dir    = "uploads/posts/";
        $target_file   = $target_dir . basename($_FILES["image"]["name"]);
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        
        if ($check !== false && $_FILES["image"]["size"] < 10000000) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            
            $upload_dir = "../uploads/posts/";
            $destination_path_no_ext = $upload_dir . "image_$new_string";

            // Optimisation (fonction supposée existante dans core.php/header.php)
            $optimized_full_path = optimize_and_save_image($_FILES["image"]["tmp_name"], $destination_path_no_ext);
            
            if ($optimized_full_path) {
                $image = ltrim($optimized_full_path, './'); 
                $image = str_replace('../', '', $image);
            }
        }
    }

    // Mise à jour SQL
    $stmt = mysqli_prepare($connect, "UPDATE posts SET title=?, slug=?, image=?, active=?, featured=?, category_id=?, content=?, download_link=?, github_link=?, publish_at=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssssissssi", $title, $slug, $image, $active, $featured, $category_id, $content, $download_link, $github_link, $publish_at, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // --- LOGIQUE TAGS OPTIMISÉE (Anti-Doublon & Nettoyage) ---
    $post_id = $id;
    $new_tag_slugs = []; 
    
    if (!empty($_POST['tags'])) {
        $tags_json = $_POST['tags'];
        $tags_array = json_decode($tags_json);
        
        if (is_array($tags_array)) {
            $stmt_tag_find = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE slug = ? LIMIT 1");
            $stmt_tag_insert = mysqli_prepare($connect, "INSERT INTO tags (name, slug) VALUES (?, ?)");
            $stmt_check_link = mysqli_prepare($connect, "SELECT id FROM post_tags WHERE post_id = ? AND tag_id = ?");
            $stmt_post_tag_insert = mysqli_prepare($connect, "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
            
            foreach ($tags_array as $tag_obj) {
                $tag_name = $tag_obj->value;
                $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name), '-'));
                if (empty($tag_slug)) continue;
                
                $new_tag_slugs[] = $tag_slug; 
                
                // 1. Trouver ou Créer le tag
                mysqli_stmt_bind_param($stmt_tag_find, "s", $tag_slug);
                mysqli_stmt_execute($stmt_tag_find);
                $result_tag = mysqli_stmt_get_result($stmt_tag_find);
                
                if ($row_tag_found = mysqli_fetch_assoc($result_tag)) {
                    $tag_id = $row_tag_found['id'];
                } else {
                    mysqli_stmt_bind_param($stmt_tag_insert, "ss", $tag_name, $tag_slug);
                    mysqli_stmt_execute($stmt_tag_insert);
                    $tag_id = mysqli_insert_id($connect);
                }
                
                // 2. Créer le lien UNIQUEMENT s'il n'existe pas
                mysqli_stmt_bind_param($stmt_check_link, "ii", $post_id, $tag_id);
                mysqli_stmt_execute($stmt_check_link);
                mysqli_stmt_store_result($stmt_check_link);
                
                if (mysqli_stmt_num_rows($stmt_check_link) == 0) {
                    mysqli_stmt_bind_param($stmt_post_tag_insert, "ii", $post_id, $tag_id);
                    mysqli_stmt_execute($stmt_post_tag_insert);
                }
            }
            mysqli_stmt_close($stmt_tag_find);
            mysqli_stmt_close($stmt_tag_insert);
            mysqli_stmt_close($stmt_check_link);
            mysqli_stmt_close($stmt_post_tag_insert);
        }
    }

    // Suppression des anciens tags (Nettoyage)
    if (!empty($existing_tags_array)) {
        $stmt_get_tag_id = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE name = ?");
        $stmt_unlink = mysqli_prepare($connect, "DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?");
        
        // Pour vérifier si le tag est orphelin après déliaison
        $stmt_check_orphan = mysqli_prepare($connect, "SELECT id FROM post_tags WHERE tag_id = ? LIMIT 1");
        $stmt_del_orphan   = mysqli_prepare($connect, "DELETE FROM tags WHERE id = ?");

        foreach ($existing_tags_array as $old_name) {
            mysqli_stmt_bind_param($stmt_get_tag_id, "s", $old_name);
            mysqli_stmt_execute($stmt_get_tag_id);
            $res_old = mysqli_stmt_get_result($stmt_get_tag_id);
            
            if ($r_old = mysqli_fetch_assoc($res_old)) {
                // Si le vieux tag n'est pas dans la nouvelle liste, on le délie
                if (!in_array($r_old['slug'], $new_tag_slugs)) {
                    $old_tag_id = $r_old['id'];
                    
                    // 1. Délier
                    mysqli_stmt_bind_param($stmt_unlink, "ii", $post_id, $old_tag_id);
                    mysqli_stmt_execute($stmt_unlink);
                    
                    // 2. Vérifier si orphelin
                    mysqli_stmt_bind_param($stmt_check_orphan, "i", $old_tag_id);
                    mysqli_stmt_execute($stmt_check_orphan);
                    mysqli_stmt_store_result($stmt_check_orphan);
                    
                    // 3. Supprimer si orphelin
                    if (mysqli_stmt_num_rows($stmt_check_orphan) == 0) {
                        mysqli_stmt_bind_param($stmt_del_orphan, "i", $old_tag_id);
                        mysqli_stmt_execute($stmt_del_orphan);
                    }
                }
            }
        }
        mysqli_stmt_close($stmt_get_tag_id);
        mysqli_stmt_close($stmt_unlink);
        mysqli_stmt_close($stmt_check_orphan);
        mysqli_stmt_close($stmt_del_orphan);
    }
    // --- FIN LOGIQUE TAGS ---

    echo '<div class="alert alert-success">Post updated successfully! Redirecting...</div>';
    echo '<meta http-equiv="refresh" content="1;url=posts.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit"></i> Edit Post</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="posts.php">Posts</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form name="edit_post_form" action="" method="post" enctype="multipart/form-data">
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
                                <input class="form-control form-control-lg" name="title" id="title" type="text" value="<?php echo htmlspecialchars($row['title']); ?>" oninput="countText()" required>
                                <small class="text-muted"><i>Characters: <span id="characters"><?php echo strlen($row['title']); ?></span>/50 recommended</i></small>
                            </div>
                            
                            <div class="form-group">
                                <label>Content</label>
                                <textarea class="form-control" id="summernote" rows="15" name="content" required><?php echo html_entity_decode($row['content']); ?></textarea>
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
                                        <label>Download link</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-file-archive"></i></span>
                                            </div>
                                            <input class="form-control" name="download_link" value="<?php echo htmlspecialchars($row['download_link']); ?>" type="url" placeholder="https://...">
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
                                            <input class="form-control" name="github_link" value="<?php echo htmlspecialchars($row['github_link']); ?>" type="url" placeholder="https://...">
                                        </div>
                                    </div>
                                </div>
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
                                    <option value="Draft" <?php if ($row['active'] == "Draft") echo 'selected'; ?>>Draft</option>
                                    <option value="Yes" <?php if ($row['active'] == "Yes") echo 'selected'; ?>>Published</option>
                                    <option value="No" <?php if ($row['active'] == "No") echo 'selected'; ?>>Inactive</option>
                                    <option value="Pending" <?php if ($row['active'] == "Pending") echo 'selected'; ?>>Pending</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date</label>
                                <input type="datetime-local" class="form-control" name="publish_at" value="<?php echo date('Y-m-d\TH:i', strtotime($row['publish_at'])); ?>" required>
                            </div>
                             <div class="form-group">
                                <label>Featured</label>
                                <select name="featured" class="form-control" required>
                                    <option value="Yes" <?php if ($row['featured'] == "Yes") echo 'selected'; ?>>Yes</option>
                                    <option value="No" <?php if ($row['featured'] == "No") echo 'selected'; ?>>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="submit" class="btn btn-primary btn-block" name="submit" value="Update Post" />
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
                                    $selected = ($row['category_id'] == $rw['id']) ? "selected" : "";
                                    echo '<option value="' . $rw['id'] . '" ' . $selected . '>' . $rw['category'] . '</option>';
                                }
                                ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tags</label>
                                <input name="tags" class="form-control" value="<?php echo htmlspecialchars($tags_value); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Featured Image</h3>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($row['image'] != ''): ?>
                                <img src="../<?php echo htmlspecialchars($row['image']); ?>" class="img-fluid mb-2" style="max-height: 150px; border-radius: 5px;">
                            <?php endif; ?>
                            
                            <div class="custom-file text-left">
                                <input type="file" name="image" class="custom-file-input" id="postImage">
                                <label class="custom-file-label" for="postImage">Change file</label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</section>

<script>
$(document).ready(function() {
    // Activation Tagify
    var input = document.querySelector('input[name=tags]');
    new Tagify(input, {
        duplicate: false, 
        delimiters: ",", 
        addTagOnBlur: true 
    });

    // Nom fichier image
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
});
</script>

<?php include "footer.php"; ?>