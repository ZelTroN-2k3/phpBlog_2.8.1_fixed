<?php
// 1. INCLURE LE NOYAU D'ABORD
include_once '../core.php'; 

// 2. VÉRIFICATION DE SÉCURITÉ
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND role='Admin'");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($suser) <= 0) {
        header("Location: " . $settings['site_url']); exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    header("Location: ../login"); exit;
}
// --- FIN SÉCURITÉ ---

// --- Vérification de l'ID ---
if (!isset($_GET['id']) && !isset($_POST['page_id'])) {
    header("Location: footer_pages.php"); exit;
}
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['page_id'];
$message = '';

// --- Logique de Traitement (POST) ---
if (isset($_POST['edit_page'])) {
    validate_csrf_token(); 

    $title = $_POST['title'];
    $purifier = get_purifier();
    $content = $purifier->purify($_POST['content']);
    $active = $_POST['active'];

    if (empty($title)) {
        $message = '<div class="alert alert-danger">Le titre ne peut pas être vide.</div>';
    } else {
        $stmt_update = mysqli_prepare($connect, "UPDATE footer_pages SET title = ?, content = ?, active = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "sssi", $title, $content, $active, $page_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header("Location: footer_pages.php"); // Redirection
            exit;
        } else {
            $message = '<div class="alert alert-danger">Erreur lors de la mise à jour.</div>';
        }
    }
}

// --- Logique d'Affichage (GET) ---
$stmt_get = mysqli_prepare($connect, "SELECT * FROM footer_pages WHERE id = ?");
mysqli_stmt_bind_param($stmt_get, "i", $page_id);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$page_data = mysqli_fetch_assoc($result_get);
mysqli_stmt_close($stmt_get);

if (!$page_data) {
    include 'header.php';
    echo '<section class="content"><div class="alert alert-danger">Page non trouvée.</div></section>';
    include 'footer.php';
    exit;
}

// 3. INCLURE LE HEADER HTML
include 'header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Modifier la Page Footer</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="footer_pages.php">Pages Footer</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Édition : <?php echo htmlspecialchars($page_data['title']); ?></h3>
                    </div>
                    
                    <?php echo $message; ?>

                    <form method="POST" action="edit_footer_page.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                        <input type="hidden" name="edit_page" value="1">
                        
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Titre</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($page_data['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="summernote">Contenu</label>
                                <textarea id="summernote" name="content" class="form-control" style="height: 300px;"><?php echo htmlspecialchars($page_data['content']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="active">Statut</label>
                                <select class="form-control" id="active" name="active">
                                    <option value="Yes" <?php if ($page_data['active'] == 'Yes') echo 'selected'; ?>>Publié (Active)</option>
                                    <option value="No" <?php if ($page_data['active'] == 'No') echo 'selected'; ?>>Brouillon (Inactive)</option>
                                </select>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include 'footer.php';
?>

<script>
$(document).ready(function() {
    $('#summernote').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});
</script>