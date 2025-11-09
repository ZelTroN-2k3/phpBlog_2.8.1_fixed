<?php
// --- CORRECTION DU BUG CSV : Déplacer la logique d'exportation avant l'inclusion de header.php ---

// 1. Vérifier la requête d'exportation CSV AVANT de charger le header (qui contient l'HTML)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    
    // Inclure le fichier minimal pour obtenir la connexion à la base de données ($connect) et les $settings.
    // L'hypothèse est que le fichier de base (core.php) se trouve un niveau au-dessus du dossier admin/.
    $core_path = dirname(__DIR__) . '/core.php'; 
    if (file_exists($core_path)) {
        // Utiliser require_once pour charger les variables essentielles sans démarrer l'affichage HTML
        require_once $core_path;
    } else {
        die("Error: Core file not found for export operation.");
    }
    
    // Vérification minimale de la connexion
    if (empty($connect)) {
        header('Location: newsletter.php?error=db_error');
        exit;
    }
    
    // --- Logique de Génération CSV ---
    $query = mysqli_query($connect, "SELECT email FROM newsletter ORDER BY email ASC");
    
    if (mysqli_num_rows($query) > 0) {
        
        // 🚨 CRITICAL: Définir les en-têtes pour le téléchargement
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Ajouter l'en-tête CSV
        fputcsv($output, array('Email Address'));
        
        // Ajouter les lignes de données
        while ($row = mysqli_fetch_assoc($query)) {
            // Assurer que seules les données nécessaires sont écrites
            fputcsv($output, array($row['email'])); 
        }
        
        fclose($output);
        exit; // 🚨 CRITICAL: Arrêter le script après l'envoi du fichier
    } else {
        // Rediriger si aucun abonné n'est trouvé
        header('Location: newsletter.php?info=no_subscribers_to_export');
        exit;
    }
}
// --- FIN DU BLOC CSV EXPORT ---


// 2. Inclure le header pour l'affichage normal de la page
include "header.php"; 

// START Logique de la page d'administration normale

// Gérer les messages après une éventuelle redirection (comme un non-export ou une erreur)
$display_message = '';
if (isset($_SESSION['message'])) {
    $display_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Gérer les messages d'erreur d'exportation
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

// --- GESTION DES ACTIONS POST SÉCURISÉES (Désabonnement depuis la liste) ---
if (isset($_POST['action']) && $_POST['action'] === 'unsubscribe_from_list') {
    // Valider le jeton CSRF pour les requêtes POST
    validate_csrf_token(); 
    
    $unsubscribe_email = $_POST['unsubscribe_email'];

    // Utiliser une requête préparée pour DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `newsletter` WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $unsubscribe_email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Stocker le message de succès dans une variable de session pour l'affichage après la redirection
    $_SESSION['message'] = '<div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        The email address <b>' . htmlspecialchars($unsubscribe_email) . '</b> has been unsubscribed.
    </div>';

    // Rediriger pour nettoyer l'URL et afficher la confirmation
    echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
}
// --- FIN GESTION DES ACTIONS POST SÉCURISÉES ---

// Ancien bloc de désabonnement par GET (redirection simple pour nettoyer l'URL)
if (isset($_GET['unsubscribe'])) {
	echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
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

        <?php echo $display_message; // Afficher le message de désabonnement réussi/erreur export ?>

        <div class="card card-primary card-outline">
			<div class="card-header">
                <h3 class="card-title">Send mass message</h3>
            </div>         
			<form action="" method="post">
			<div class="card-body">
<?php
if (isset($_POST['send_mass_message'])) {
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---
    
    // Suppression de htmlspecialchars du $content pour préserver le formatage HTML de Summernote dans l'email
    $title    = addslashes($_POST['title']);
    $content  = $_POST['content']; 
    
    $from     = $settings['email'];
    $sitename = $settings['sitename'];
	
    $run2 = mysqli_query($connect, "SELECT * FROM `newsletter`");
    while ($row = mysqli_fetch_assoc($run2)) {
		
        $to = $row['email'];
		
        $subject = $title;
        
        $message = '
<html>
<body>
  <b><h1><a href="' . $settings['site_url'] . '/" title="Visit the website">' . $settings['sitename'] . '</a></h1><b/>
  <br />

  ' . $content . ' 
  
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
    
    echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            Your global message has been sent successfully.
        </div>';
}
?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
					<label>Title</label>
					<input class="form-control" name="title" value="<?php if (isset($_POST['title'])) echo htmlspecialchars($_POST['title']); ?>" type="text" required>
				</div>
				<div class="form-group">
					<label>Content</label>
					<textarea class="form-control" id="summernote" name="content" required></textarea>
				</div>
            </div>
            <div class="card-footer">
				<input type="submit" name="send_mass_message" class="btn btn-primary" value="Send" />
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
            <div class="card-body">
                <table class="table table-bordered table-hover" id="dt-basic" width="100%">
                    <thead>
                        <tr>
                            <th>E-Mail</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM newsletter ORDER BY email ASC");
while ($row = mysqli_fetch_assoc($query)) {
    echo '
                        <tr>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to unsubscribe from this email?\');">
                                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                                    <input type="hidden" name="unsubscribe_email" value="' . htmlspecialchars($row['email']) . '">
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
        </div>

    </div></section>
<script>
$(document).ready(function() {
    // Activation de DataTables avec ordre par défaut ascendant (colonne 0: Email)
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 0, "asc" ]] 
	});
    // Note : Summernote est initialisé dans footer.php via une vérification
});
</script>
<?php
include "footer.php";
?>