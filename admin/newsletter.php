<?php
// --- CORRECTION DU BUG CSV : Déplacer la logique d'exportation avant l'inclusion de header.php ---

// 1. Vérifier la requête d'exportation CSV AVANT de charger le header (qui contient l'HTML)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    
    // Inclure le fichier minimal pour obtenir la connexion à la base de données ($connect) et les $settings.
    $core_path = dirname(__DIR__) . '/core.php'; 
    if (file_exists($core_path)) {
        require_once $core_path;
    } else {
        die("Error: Core file not found for export operation.");
    }
    
    if (empty($connect)) {
        header('Location: newsletter.php?error=db_error');
        exit;
    }
    
    // --- Logique de Génération CSV ---
    $query = mysqli_query($connect, "SELECT email FROM newsletter ORDER BY email ASC");
    
    if (mysqli_num_rows($query) > 0) {
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email Address'));
        
        while ($row = mysqli_fetch_assoc($query)) {
            fputcsv($output, array($row['email'])); 
        }
        
        fclose($output);
        exit; 
    } else {
        header('Location: newsletter.php?info=no_subscribers_to_export');
        exit;
    }
}
// --- FIN DU BLOC CSV EXPORT ---


// 2. Inclure le header pour l'affichage normal de la page
include "header.php"; 

// START Logique de la page d'administration normale

// --- NOUVEAU: Initialiser les variables d'aperçu et de formulaire ---
$preview_html = '';
$form_data = [
    'template' => 'simple',
    'title' => '',
    'content' => '',
    'featured_post_id' => 0,
    'promo_btn_text' => '',
    'promo_btn_url' => ''
];

// Gérer les messages
$display_message = '';
if (isset($_SESSION['message'])) {
    $display_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_GET['error']) && $_GET['error'] == 'db_error') {
     $display_message = '<div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            A database error occurred during the export attempt.
        </div>';
} elseif (isset($_GET['info']) && $_GET['info'] == 'no_subscribers_to_export') {
     $display_message = '<div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            There are no subscribers to export.
        </div>';
}

// --- GESTION DES ACTIONS POST SÉCURISÉES ---

// --- 1. AJOUTER UN ABONNÉ ---
if (isset($_POST['add_subscriber'])) {
    validate_csrf_token();
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = '<div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            The email address <b>' . htmlspecialchars($email) . '</b> is invalid.
        </div>';
    } else {
        $stmt_check = mysqli_prepare($connect, "SELECT id FROM newsletter WHERE email = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            $_SESSION['message'] = '<div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                The email address <b>' . htmlspecialchars($email) . '</b> is already subscribed.
            </div>';
        } else {
            $stmt_add = mysqli_prepare($connect, "INSERT INTO newsletter (email) VALUES (?)");
            mysqli_stmt_bind_param($stmt_add, "s", $email);
            mysqli_stmt_execute($stmt_add);
            mysqli_stmt_close($stmt_add);
            
            $_SESSION['message'] = '<div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                The email address <b>' . htmlspecialchars($email) . '</b> has been added.
            </div>';
        }
        mysqli_stmt_close($stmt_check);
    }
    
    echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
}

// --- 2. DÉSABONNEMENT UNIQUE ---
if (isset($_POST['action']) && $_POST['action'] === 'unsubscribe_from_list') {
    validate_csrf_token(); 
    
    $unsubscribe_id = (int)$_POST['unsubscribe_id'];
    $email_for_message = $_POST['email_for_message']; 

    $stmt = mysqli_prepare($connect, "DELETE FROM `newsletter` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $unsubscribe_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $_SESSION['message'] = '<div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        The email address <b>' . htmlspecialchars($email_for_message) . '</b> has been unsubscribed.
    </div>';

    echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
}

// --- 3. ACTIONS EN MASSE ---
if (isset($_POST['apply_bulk_action'])) {
    validate_csrf_token();
    $action = $_POST['bulk_action'];
    $subscriber_ids = $_POST['subscriber_ids'] ?? [];

    if ($action == 'delete' && !empty($subscriber_ids)) {
        $placeholders = implode(',', array_fill(0, count($subscriber_ids), '?'));
        $types = str_repeat('i', count($subscriber_ids));
        
        $stmt = mysqli_prepare($connect, "DELETE FROM newsletter WHERE id IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, $types, ...$subscriber_ids);
        mysqli_stmt_execute($stmt);
        $count = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['message'] = '<div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <b>' . $count . '</b> subscriber(s) have been deleted.
        </div>';
    }
    
    echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
}
// --- FIN GESTION DES ACTIONS POST SÉCURISÉES ---

// --- 4. LOGIQUE D'ENVOI OU APERÇU ---
if (isset($_POST['send_mass_message']) || isset($_POST['preview_message'])) {
    validate_csrf_token();
    
    // 1. Récupérer toutes les données du formulaire
    $form_data = [
        'title'            => $_POST['title'] ?? '',
        'content'          => $_POST['content'] ?? '',
        'template'         => $_POST['template'] ?? 'simple',
        'featured_post_id' => (int)($_POST['featured_post_id'] ?? 0),
        'promo_btn_text'   => $_POST['promo_btn_text'] ?? '',
        'promo_btn_url'    => $_POST['promo_btn_url'] ?? ''
    ];

    $from             = $settings['email'];
    $sitename         = $settings['sitename'];
    $unsubscribe_link = $settings['site_url'] . '/unsubscribe.php';
    $message_body     = '';

    // 2. Construire le $message_body en fonction du template
    switch ($form_data['template']) {
        
        // --- TEMPLATE 2: ARTICLE À LA UNE ---
        case 'featured_post':
            $post_html = '';
            if ($form_data['featured_post_id'] > 0) {
                $stmt_post = mysqli_prepare($connect, "SELECT title, slug, image, content FROM posts WHERE id = ?");
                mysqli_stmt_bind_param($stmt_post, "i", $form_data['featured_post_id']);
                mysqli_stmt_execute($stmt_post);
                $result_post = mysqli_stmt_get_result($stmt_post);
                $post = mysqli_fetch_assoc($result_post);
                mysqli_stmt_close($stmt_post);
                
                if ($post) {
                    $post_url = $settings['site_url'] . '/post?name=' . $post['slug'];
                    $post_excerpt = short_text(strip_tags(html_entity_decode($post['content'])), 150);
                    
                    $post_html = '
                    <hr>
                    <h2 style="font-size: 20px; margin-bottom: 10px;">À la une: ' . htmlspecialchars($post['title']) . '</h2>
                    ' . ($post['image'] ? '<a href="' . $post_url . '"><img src="' . htmlspecialchars($post['image']) . '" alt="Image" style="width:100%; max-width: 500px; height:auto; border-radius: 5px;"></a>' : '') . '
                    <p style="font-size: 16px; margin-top: 15px;">' . $post_excerpt . '</p>
                    <a href="' . $post_url . '" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">Lire la suite</a>
                    <hr style="margin-top: 25px;">
                    ';
                }
            }
            
            $message_body = '
                <h1 style="font-size: 24px;">' . htmlspecialchars($form_data['title']) . '</h1>
                ' . $post_html . '
                <div style="margin-top: 20px;">' . $form_data['content'] . '</div>'; // Content est déjà "purifié" par Summernote/HTMLPurifier si nécessaire
            break;
        
        // --- TEMPLATE 3: PROMOTIONNEL ---
        case 'promo':
            $button_html = '';
            if (!empty($form_data['promo_btn_text']) && !empty($form_data['promo_btn_url'])) {
                $button_html = '
                <div style="margin-top: 25px; text-align: center;">
                    <a href="' . htmlspecialchars($form_data['promo_btn_url']) . '" style="display: inline-block; padding: 12px 25px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 18px; font-weight: bold;">' . htmlspecialchars($form_data['promo_btn_text']) . '</a>
                </div>';
            }
            
            $message_body = '
                <div style="background-color: #f4f4f4; padding: 20px; border-radius: 5px; text-align: center;">
                    <h1 style="font-size: 24px;">' . htmlspecialchars($form_data['title']) . '</h1>
                    <p style="font-size: 16px; margin-top: 15px;">' . $form_data['content'] . '</p>
                    ' . $button_html . '
                </div>';
            break;
            
        // --- TEMPLATE 1: SIMPLE (DÉFAUT) ---
        case 'simple':
        default:
            $message_body = '
                <h1 style="font-size: 24px;">' . htmlspecialchars($form_data['title']) . '</h1>
                <br />
                ' . $form_data['content'] . ' 
            ';
            break;
    }

    // 3. Assembler le message final (Wrapper HTML)
    $message = '
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
        <div style="background-color: #007bff; color: #ffffff; padding: 20px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;"><a href="' . $settings['site_url'] . '/" style="color: #ffffff; text-decoration: none;">' . $sitename . '</a></h1>
        </div>
        <div style="padding: 30px;">
        ' . $message_body . ' 
        </div>
        <div style="background-color: #f9f9f9; color: #777; padding: 20px; text-align: center; border-top: 1px solid #ddd;">
            <p style="font-size: 12px; margin: 0;">&copy; ' . date('Y') . ' ' . $sitename . '. Tous droits réservés.</p>
            <p style="font-size: 12px; margin: 5px 0 0 0;">
                <a href="' . $unsubscribe_link . '" style="color: #007bff; text-decoration: none;">Se désabonner</a>
            </p>
        </div>
    </div>
</body>
</html>
';

    // 4. Action : Envoyer ou Prévisualiser
    if (isset($_POST['preview_message'])) {
        // --- ACTION: APERÇU ---
        $preview_html = $message; // Stocker l'HTML pour l'afficher plus bas
        $display_message = '
        <div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            Ceci est un aperçu. L\'email n\'a pas été envoyé.
        </div>';

    } elseif (isset($_POST['send_mass_message'])) {
        // --- ACTION: ENVOYER ---
        $emails = [];
        $run2 = mysqli_query($connect, "SELECT email FROM `newsletter`");
        while ($row = mysqli_fetch_assoc($run2)) {
            $emails[] = $row['email'];
        }

        if (!empty($emails)) {
            $bcc_string = implode(',', $emails);
            $to = $from;
            $subject = $form_data['title'];
            
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: ' . $sitename . ' <' . $from . '>' . "\r\n";
            $headers .= 'Bcc: ' . $bcc_string . "\r\n"; 
            
            @mail($to, $subject, $message, $headers);
            
            $display_message = '
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                Votre message global (Modèle: ' . $form_data['template'] . ') a été envoyé avec succès à <b>' . count($emails) . '</b> abonné(s).
            </div>';
            
            // Réinitialiser le formulaire après un envoi réussi
            $form_data = [
                'template' => 'simple', 'title' => '', 'content' => '',
                'featured_post_id' => 0, 'promo_btn_text' => '', 'promo_btn_url' => ''
            ];

        } else {
             $display_message = '
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                Il n\'y a aucun abonné à qui envoyer un message.
            </div>';
        }
    }
}
// --- FIN DE L'ENVOI DE MASSE ---

// --- NOUVEAU : Récupérer les 10 derniers articles pour le sélecteur ---
$latest_posts = [];
$posts_query = mysqli_query($connect, "SELECT id, title FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT 10");
while ($post_row = mysqli_fetch_assoc($posts_query)) {
    $latest_posts[] = $post_row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="far fa-envelope"></i> Newsletter</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Newsletter</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <?php echo $display_message; // Afficher les messages de succès/erreur ?>

        <div class="row">
            <div class="col-md-7">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Send mass message</h3>
                    </div>        
                    <form action="newsletter.php" method="post">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label>Modèle d'email</label>
                            <select class="form-control" name="template" id="template-select" required>
                                <option value="simple" <?php if($form_data['template'] == 'simple') echo 'selected'; ?>>Simple (Par défaut)</option>
                                <option value="featured_post" <?php if($form_data['template'] == 'featured_post') echo 'selected'; ?>>Article à la Une</option>
                                <option value="promo" <?php if($form_data['template'] == 'promo') echo 'selected'; ?>>Promotionnel (Avec bouton)</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="featured-post-group" style="display:none; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <label>Choisir un article à mettre en avant</label>
                            <select class="form-control" name="featured_post_id">
                                <option value="0">-- Aucun --</option>
                                <?php
                                foreach ($latest_posts as $post) {
                                    $selected = ($form_data['featured_post_id'] == $post['id']) ? 'selected' : '';
                                    echo '<option value="' . $post['id'] . '" ' . $selected . '>' . htmlspecialchars($post['title']) . '</option>';
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">Le titre, l'image et l'extrait de cet article seront ajoutés au-dessus de votre contenu.</small>
                        </div>
                        
                        <div id="promo-group" style="display:none; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <div class="form-group">
                                <label>Texte du Bouton (Optionnel)</label>
                                <input class="form-control" name="promo_btn_text" placeholder="Ex: Voir l'offre, Lire la suite..." value="<?php echo htmlspecialchars($form_data['promo_btn_text']); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL du Bouton (Optionnel)</label>
                                <input class="form-control" name="promo_btn_url" placeholder="https://..." type="url" value="<?php echo htmlspecialchars($form_data['promo_btn_url']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Sujet (Titre de l'email)</label>
                            <input class="form-control" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>Contenu Principal</label>
                            <textarea class="form-control" id="summernote" name="content" required><?php echo htmlspecialchars($form_data['content']); ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <input type="submit" name="send_mass_message" class="btn btn-primary" value="Envoyer à tous" />
                        <input type="submit" name="preview_message" class="btn btn-secondary" value="Aperçu" />
                    </div>
                    </form>
                </div>
                
                <?php if (!empty($preview_html)): ?>
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Aperçu de l'Email</h3>
                    </div>
                    <div class="card-body p-0">
                        <iframe srcdoc="<?php echo htmlspecialchars($preview_html); ?>" style="width: 100%; height: 600px; border: 0;"></iframe>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <div class="col-md-5">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Add Subscriber</h3>
                    </div>
                    <form action="newsletter.php" method="post">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input class="form-control" name="email" type="email" placeholder="Enter email" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="submit" name="add_subscriber" class="btn btn-success" value="Add" />
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Subscribers List</h3>
                        <div class="card-tools">
                            <a href="?export=csv" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
                        </div>
                    </div>        
                    
                    <form action="newsletter.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="card-body">
                            <table class="table table-bordered table-hover" id="dt-basic" width="100%">
                                <thead>
                                    <tr>
                                        <th style="width: 10px;"><input type="checkbox" id="select-all"></th>
                                        <th>E-Mail</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
$query = mysqli_query($connect, "SELECT id, email FROM newsletter ORDER BY email ASC");
while ($row = mysqli_fetch_assoc($query)) {
    echo '
                                    <tr>
                                        <td><input type="checkbox" name="subscriber_ids[]" value="' . $row['id'] . '"></td>
                                        <td>' . htmlspecialchars($row['email']) . '</td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to unsubscribe this email?\');">
                                                <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                                                <input type="hidden" name="unsubscribe_id" value="' . $row['id'] . '">
                                                <input type="hidden" name="email_for_message" value="' . htmlspecialchars($row['email']) . '">
                                                <button type="submit" name="action" value="unsubscribe_from_list" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-bell-slash"></i> Unsubscribe
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
';
}
?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <select name="bulk_action" class="form-control" style="width: 200px; display: inline-block;">
                                <option value="">Bulk Actions</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" name="apply_bulk_action" class="btn btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div></section>
<script>
$(document).ready(function() {
    // Activation de DataTables
    var table = $('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
        "order": [[ 1, "asc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": 0 }
        ]
    });
    
    // Logique pour "Select All"
    $('#select-all').on('click', function(){
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // --- NOUVEAU : Logique pour les champs conditionnels du template ---
    function toggleTemplateFields() {
        var type = $('#template-select').val();
        
        if (type === 'featured_post') {
            $('#featured-post-group').slideDown();
            $('#promo-group').slideUp();
        } else if (type === 'promo') {
            $('#featured-post-group').slideUp();
            $('#promo-group').slideDown();
        } else {
            $('#featured-post-group').slideUp();
            $('#promo-group').slideUp();
        }
    }
    
    // Lier l'événement 'change'
    $('#template-select').on('change', toggleTemplateFields);
    
    // Appeler une fois au chargement pour s'assurer que les bons champs sont affichés
    // (surtout après un rechargement de page pour un aperçu)
    toggleTemplateFields();
});
</script>
<?php
include "footer.php";
?>