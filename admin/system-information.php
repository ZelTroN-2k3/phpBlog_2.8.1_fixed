<?php
include "header.php";

// L'administrateur seul peut voir cette page
if ($user['role'] != "Admin") {
    // Rediriger vers le tableau de bord
    header('Location: dashboard.php');
    exit;
}

// --- Variable de version ---
// $phpblog_version = "x.x.x"; // Définie dans config.php

// ------------------------------------------------------------
// --- LOGIQUE POUR LES INFORMATIONS SYSTÈME ---
// ------------------------------------------------------------

// --- Infos Serveur ---
$server_domain = $_SERVER['SERVER_NAME'];
$server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']); 
$server_os = php_uname('s');
$server_software = $_SERVER['SERVER_SOFTWARE'];
$server_port = $_SERVER['SERVER_PORT'];
$http_protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'N/A';
$gateway_interface = $_SERVER['GATEWAY_INTERFACE'] ?? 'N/A';

// --- Infos PHP ---
$php_version = phpversion();
$db_version_query = mysqli_query($connect, "SELECT VERSION() as version");
$db_version = mysqli_fetch_assoc($db_version_query)['version'];

// Directives PHP
$max_upload = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$php_memory_limit = ini_get('memory_limit');
$php_max_execution_time = ini_get('max_execution_time');

// Statut 'display_errors' (Important pour la sécurité)
$display_errors = ini_get('display_errors');
$display_errors_badge = ($display_errors == 1 || strtolower($display_errors) == 'on') 
    ? '<span class="badge bg-danger">On (Risque de sécurité)</span>' 
    : '<span class="badge bg-success">Off (Sécurisé)</span>';

// Statut 'file_uploads'
$file_uploads = ini_get('file_uploads');
$file_uploads_badge = ($file_uploads == 1 || strtolower($file_uploads) == 'on')
    ? '<span class="badge bg-success">On</span>'
    : '<span class="badge bg-danger">Off</span>';

// --- Extensions PHP ---
$curl_status = extension_loaded('curl');
$gd_status = extension_loaded('gd');
$mbstring_status = extension_loaded('mbstring');
$mysqli_status = extension_loaded('mysqli');
$json_status = extension_loaded('json');
$openssl_status = extension_loaded('openssl');
$openssl_version = $openssl_status ? OPENSSL_VERSION_TEXT : 'N/A';

// --- Permissions ---

// Fonction d'aide pour les dossiers
function check_dir_permission($path) {
    if (!file_exists($path)) return '<span class="badge bg-danger">N\'existe pas</span>';
    if (is_writable($path)) {
        return '<span class="badge bg-success">Accessible en écriture</span>';
    } else {
        return '<span class="badge bg-danger">Non accessible</span>';
    }
}

// Fonction d'aide pour config.php (logique inversée)
function check_config_permission($path) {
     if (!file_exists($path)) return '<span class="badge bg-danger">N\'existe pas</span>';
     if (is_writable($path)) {
        return '<span class="badge bg-danger">Inscriptible (Risque)</span>';
    } else {
        return '<span class="badge bg-success">Non Inscriptible (Sécurisé)</span>';
    }
}

// Chemins (relatifs à ce fichier admin/system-information.php)
$perm_backup = check_dir_permission('../backup-database/');
$perm_uploads = check_dir_permission('../uploads/');
$perm_cache = check_dir_permission('../cache/');
$perm_config = check_config_permission('../config.php');

// ------------------------------------------------------------
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-server"></i> Informations Système</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Informations Système</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
        
            <div class="col-md-6">
                
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-server"></i> Serveur & Logiciels</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Domaine
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($server_domain); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Addresse IP
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($server_ip); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Système d'exploitation
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($server_os); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Logiciel Serveur
                                <span class="badge bg-secondary" style="font-size: 0.8em;"><?php echo htmlspecialchars(short_text($server_software, 25)); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Protocole HTTP
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($http_protocol); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Interface Gateway
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($gateway_interface); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Port
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($server_port); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version MySQL
                                <span class="badge bg-info"><?php echo short_text($db_version, 15); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card card-outline card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fab fa-php"></i> Extensions PHP</h3>
                    </div>
                    <div class="card-body p-0">
                         <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                MySQLi (Base de données)
                                <?php if ($mysqli_status): ?>
                                    <span class="badge bg-success">Activée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée (ERREUR)</span>
                                <?php endif; ?>
                            </li>
                             <li class="list-group-item d-flex justify-content-between align-items-center">
                                cURL (Flux RSS)
                                <?php if ($curl_status): ?>
                                    <span class="badge bg-success">Activée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                GD (Images)
                                <?php if ($gd_status): ?>
                                    <span class="badge bg-success">Activée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                mbstring (Caractères)
                                <?php if ($mbstring_status): ?>
                                    <span class="badge bg-success">Activée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                JSON (Données)
                                <?php if ($json_status): ?>
                                    <span class="badge bg-success">Activée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée (ERREUR)</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Open SSL
                                <?php if ($openssl_status): ?>
                                    <span class="badge bg-success">Activée (<?php echo htmlspecialchars(short_text($openssl_version, 15)); ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Désactivée</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
            
            <div class="col-md-6">
                
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs"></i> Configuration PHP</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version PHP
                                <span class="badge bg-info"><?php echo $php_version; ?></span>
                            </li>
                             <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version phpBlog
                                <span class="badge bg-primary rounded-pill"><?php echo $phpblog_version; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Thème Actif
                                <span class="badge bg-primary"><?php echo htmlspecialchars($settings['theme']); ?></span>
                            </li>
                             <li class="list-group-item d-flex justify-content-between align-items-center">
                                Afficher les erreurs (display_errors)
                                <?php echo $display_errors_badge; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Upload de fichiers (file_uploads)
                                <?php echo $file_uploads_badge; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Limite POST (post_max_size)
                                <span class="badge bg-warning text-dark"><?php echo $post_max_size; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Limite Upload (upload_max_filesize)
                                <span class="badge bg-warning text-dark"><?php echo $max_upload; ?></span>
                            </li>
                             <li class="list-group-item d-flex justify-content-between align-items-center">
                                Limite Mémoire PHP
                                <span class="badge bg-warning text-dark"><?php echo $php_memory_limit; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Limite Temps d'exécution
                                <span class="badge bg-warning text-dark"><?php echo $php_max_execution_time; ?>s</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card card-outline card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-folder-open"></i> Permissions Fichiers & Dossiers</h3>
                    </div>
                    <div class="card-body p-0">
                         <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Dossier <code>/backup-database/</code>
                                <?php echo $perm_backup; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Dossier <code>/uploads/</code>
                                <?php echo $perm_uploads; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Dossier <code>/cache/</code> (HTMLPurifier)
                                <?php echo $perm_cache; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Fichier <code>/config.php</code>
                                <?php echo $perm_config; ?>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>