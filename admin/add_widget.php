<?php
include "header.php";

// --- NOUVELLE LOGIQUE : CONSERVATION DU STATUT ---
$status_url_param = ''; // Format &status=...
$status_url_query = ''; // Format ?status=...
$current_status = $_GET['status'] ?? 'all'; // Récupérer le statut pour la présélection

if ($current_status != 'all') {
    $status_param = htmlspecialchars($current_status);
    $status_url_param = '&status=' . $status_param;
    $status_url_query = '?status=' . $status_param;
}
// --- FIN LOGIQUE ---

// --- NOUVELLE LOGIQUE DE SAUVEGARDE (Étape 1) ---
if (isset($_POST['add'])) {
    
    validate_csrf_token();

    // Champs communs
    $title       = $_POST['title'];
    $position    = $_POST['position'];
    $active      = $_POST['active'];
    $widget_type = $_POST['widget_type']; // Le type de widget (caché dans le formulaire)

    // Champs spécifiques (initialisés à NULL)
    $content     = null;
    $config_data = null;

    // Remplir les champs spécifiques selon le type
    switch ($widget_type) {
        case 'html':
            $content = $_POST['content'];
            break;
            
        case 'latest_posts':
            $limit = (int)$_POST['limit'];
            $config = ['count' => $limit]; // Créer un tableau de configuration
            $config_data = json_encode($config); // Encoder en JSON
            break;
            
        case 'search':
            // Ce type n'a besoin d'aucune configuration
            break;

        case 'quiz_leaderboard':
            // Ce type n'a besoin d'aucune configuration
            break;
    }

    // Insérer dans la base de données
    $stmt = mysqli_prepare($connect, "INSERT INTO widgets (title, widget_type, content, config_data, position, active) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $title, $widget_type, $content, $config_data, $position, $active);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // --- MODIFIÉ : Redirection avec statut ---
    echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
    exit;
}
// --- FIN DE LA LOGIQUE DE SAUVEGARDE ---


// Déterminer ce qu'il faut afficher (Sélection ou Formulaire)
$type = $_GET['type'] ?? null;
$widget_name = ''; // Nom pour le titre
$widget_icon = 'fas fa-puzzle-piece'; // Icône par défaut

if ($type) {
    switch ($type) {
        case 'html':
            $widget_name = 'HTML Personnalisé';
            $widget_icon = 'fas fa-code';
            break;
        case 'latest_posts':
            $widget_name = 'Articles Récents';
            $widget_icon = 'fas fa-list-ul';
            break;
        case 'search':
            $widget_name = 'Barre de Recherche';
            $widget_icon = 'fas fa-search';
            break;
        case 'quiz_leaderboard':
            $widget_name = 'Quiz Leaderboard (Top 10)';
            $widget_icon = 'fas fa-trophy';
            break;
            default:
            // Si le type est inconnu, rediriger vers la sélection
            header('Location: add_widget.php');
            exit;
    }
}

?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-archive"></i> 
                    <?php 
                    // Changer le titre de la page
                    if ($type) {
                        echo 'Ajouter un Widget : ' . htmlspecialchars($widget_name);
                    } else {
                        echo 'Ajouter un Widget';
                    }
                    ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="widgets.php<?php echo $status_url_query; ?>">Widgets</a></li>
                    <li class="breadcrumb-item active">
                        <?php 
                        // Changer le breadcrumb
                        if ($type) {
                            echo htmlspecialchars($widget_name);
                        } else {
                            echo 'Choisir le type';
                        }
                        ?>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
    
        <?php 
        // --- NOUVELLE LOGIQUE D'AFFICHAGE (Étape 2) ---
        
        // MODE 1 : SÉLECTION DU TYPE (si ?type= n'est pas dans l'URL)
        if (!$type): 
        ?>
        
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Choisir le type de Widget à créer</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="add_widget.php?type=html<?php echo $status_url_param; ?>" class="btn btn-app btn-block" style="height: 120px;">
                            <i class="fas fa-code" style="font-size: 3rem;"></i>
                            <span class="mt-2" style="font-size: 1.1rem;">HTML Personnalisé</span>
                            <p class="small text-muted mb-0">Insérer du texte, des images, ou du HTML.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="add_widget.php?type=latest_posts<?php echo $status_url_param; ?>" class="btn btn-app btn-block" style="height: 120px;">
                            <i class="fas fa-list-ul" style="font-size: 3rem;"></i> 
                            <span class="mt-2" style="font-size: 1.1rem;">Articles Récents</span>
                            <p class="small text-muted mb-0">Affiche une liste des derniers articles.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="add_widget.php?type=search<?php echo $status_url_param; ?>" class="btn btn-app btn-block" style="height: 120px;">
                            <i class="fas fa-search" style="font-size: 3rem;"></i> 
                            <span class="mt-2" style="font-size: 1.1rem;">Recherche</span>
                            <p class="small text-muted mb-0">Affiche le formulaire de recherche.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="add_widget.php?type=quiz_leaderboard<?php echo $status_url_param; ?>" class="btn btn-app btn-block" style="height: 120px;">
                            <i class="fas fa-trophy" style="font-size: 3rem;"></i> 
                            <span class="mt-2" style="font-size: 1.1rem;">Quiz Leaderboard</span>
                            <p class="small text-muted mb-0">Affiche le top 10 des joueurs.</p>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="widgets.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </div>

        <?php 
        // MODE 2 : AFFICHAGE DU FORMULAIRE SPÉCIFIQUE
        else: 
        ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="<?php echo $widget_icon; ?>"></i> Détails du Widget</h3>
            </div>
            <form action="add_widget.php<?php echo $status_url_query; ?>" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="widget_type" value="<?php echo htmlspecialchars($type); ?>">
                    
                    <div class="form-group">
                        <label>Titre</label>
                        <input class="form-control" name="title" value="" type="text" required>
                    </div>

                    <?php 
                    // --- AFFICHAGE DES CHAMPS SPÉCIFIQUES ---
                    switch ($type):
                    
                        // CAS 1: HTML
                        case 'html': 
                    ?>
                            <div class="form-group">
                                <label>Contenu</label>
                                <textarea class="form-control" id="summernote" name="content" required></textarea>
                            </div>
                    <?php 
                            break; 
                        
                        // CAS 2: ARTICLES RÉCENTS
                        case 'latest_posts': 
                    ?>
                            <div class="form-group">
                                <label>Nombre d'articles à afficher</label>
                                <input class="form-control" name="limit" value="5" type="number" required style="width: 200px;">
                            </div>
                    <?php 
                            break;
                            
                        // CAS 3: RECHERCHE
                        case 'search': 
                    ?>
                            <div class="alert alert-info">
                                Ce widget n'a pas besoin de configuration supplémentaire.
                            </div>
                    <?php 
                            break; 
                    
                            // CAS 4: QUIZ LEADERBOARD
                            case 'quiz_leaderboard':
                    ?>
                            <div class="alert alert-info">
                                Ce widget n'a pas besoin de configuration supplémentaire. Il affichera le classement global des 10 meilleurs joueurs.
                            </div>
                    <?php 
                            break; 
                    endswitch; 

                    // --- FIN DES CHAMPS SPÉCIFIQUES ---
                    ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position:</label>
                                <select class="form-control" name="position" required>
                                    <option value="Sidebar" selected>Sidebar</option>
                                    <option value="Header">Header</option>
                                    <option value="Footer">Footer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Statut</label>
                                <select class="form-control" name="active" required>
                                    <option value="Yes" <?php if ($current_status == 'published' || $current_status == 'all') echo 'selected'; ?>>Publié</option>
                                    <option value="No" <?php if ($current_status == 'draft') echo 'selected'; ?>>Brouillon</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="card-footer">
                    <input type="submit" name="add" class="btn btn-primary" value="Ajouter" />
                    <a href="widgets.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>                          
        </div>
        
        <?php 
        // Fin du "else" (MODE 2)
        endif; 
        ?>

    </div></section>
<?php
include "footer.php";
?>