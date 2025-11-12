<?php
include "header.php"; // Inclut votre header admin (connexion, BDD, $settings, etc.)

$message = ''; // Pour les messages de succès ou d'erreur

// --- LOGIQUE 1 : AJOUTER UN NOUVEAU FLUX ---
if (isset($_POST['add_feed'])) {
    validate_csrf_token(); // Vérifie le jeton de sécurité

    $feed_url = $_POST['feed_url'];
    $user_id = (int)$_POST['user_id'];
    $category_id = (int)$_POST['category_id'];
    $is_active = (isset($_POST['is_active'])) ? 1 : 0;

    // Validation simple
    if (empty($feed_url) || $user_id == 0 || $category_id == 0) {
        $message = '<div class="alert alert-danger">Please complete all fields.</div>';
    } elseif (!filter_var($feed_url, FILTER_VALIDATE_URL)) {
        $message = '<div class="alert alert-danger">The feed URL is not valid.</div>';
    } else {
        // Insérer dans la base de données
        $stmt = mysqli_prepare($connect, "INSERT INTO rss_imports (feed_url, import_as_user_id, import_as_category_id, is_active) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siii", $feed_url, $user_id, $category_id, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert alert-success">RSS feed added successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: Unable to add this feed. It may already exist.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

// --- LOGIQUE 2 : SUPPRIMER UN FLUX ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get(); // Vérifie le jeton de sécurité de l'URL
    
    $id_to_delete = (int)$_GET['delete_id'];
    
    $stmt = mysqli_prepare($connect, "DELETE FROM rss_imports WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">RSS feed deleted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting the feed.</div>';
    }
    mysqli_stmt_close($stmt);
}


// --- LOGIQUE 3 : RÉCUPÉRER LES DONNÉES POUR LES LISTES ---

// Récupérer les flux existants pour les afficher
$rss_feeds = [];
// --- CORRECTION BUG "name" vs "category" ---
// J'ai remplacé c.name par c.category
$result_feeds_sql = "
    SELECT r.*, u.username, c.category as category_name 
    FROM rss_imports r
    LEFT JOIN users u ON r.import_as_user_id = u.id
    LEFT JOIN categories c ON r.import_as_category_id = c.id
    ORDER BY r.id DESC
";
$result_feeds = mysqli_query($connect, $result_feeds_sql);
if ($result_feeds) {
    while ($row = mysqli_fetch_assoc($result_feeds)) {
        $rss_feeds[] = $row;
    }
}

// Récupérer les Admins pour le menu déroulant "Auteur"
$admins = [];
$result_admins = mysqli_query($connect, "SELECT id, username FROM users WHERE role = 'Admin'");
if ($result_admins) {
    while ($row = mysqli_fetch_assoc($result_admins)) {
        $admins[] = $row;
    }
}

// Récupérer les Catégories pour le menu déroulant "Catégorie"
$categories = [];
// --- CORRECTION BUG "name" vs "category" ---
// J'ai remplacé "SELECT id, name" par "SELECT id, category"
$result_categories = mysqli_query($connect, "SELECT id, category FROM categories ORDER BY category ASC");
if ($result_categories) {
    while ($row = mysqli_fetch_assoc($result_categories)) {
        $categories[] = $row;
    }
} else {
    // Debug : que se passe-t-il si la requête échoue
    $message .= '<div class="alert alert-danger">Erreur de requête SQL Catégories : ' . mysqli_error($connect) . '</div>';
}

?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-rss"></i> RSS Feed Import</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">RSS Feed Import</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php echo $message; // Affiche les messages de succès ou d'erreur ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add a New RSS Feed</h3>
                    </div>
                    <form action="rss_imports.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>RSS Feed URL</label>
                                        <input type="url" name="feed_url" class="form-control" placeholder="https://www.example.com/feed.xml" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Publish As</label>
                                        <select name="user_id" class="form-control" required>
                                            <option value="">-- Choose an author --</option>
                                            <?php foreach ($admins as $admin): ?>
                                                <option value="<?php echo (int)$admin['id']; ?>"><?php echo htmlspecialchars($admin['username']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Publish In Category</label>
                                        <select name="category_id" class="form-control" required>
                                            <option value="">-- Choose a category --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['category']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                            <label class="form-check-label" for="is_active">Activate this feed (will import automatically)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="add_feed" class="btn btn-primary"><i class="fas fa-plus"></i> Add Feed</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Current RSS Feeds</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Feed URL</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Last Checked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rss_feeds)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No RSS feeds configured at the moment.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rss_feeds as $feed): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($feed['feed_url']); ?></td>
                                            <td><?php echo htmlspecialchars($feed['username']); ?></td>
                                            <td><?php echo htmlspecialchars($feed['category_name'] ?? 'Catégorie supprimée?'); ?></td>
                                            <td>
                                                <?php if ($feed['is_active']): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $feed['last_import_time'] ? $feed['last_import_time'] : 'Jamais'; ?></td>
                                            <td>
                                                <a href="?delete_id=<?php echo (int)$feed['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce flux ?');">
                                                   <i class="fas fa-trash"></i>
                                                </a>
                                                
                                                <a href="run_rss_import.php?id=<?php echo (int)$feed['id']; ?>" 
                                                   class="btn btn-info btn-sm" 
                                                   target="_blank">
                                                   <i class="fas fa-download"></i> Import
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>