<?php
include "header.php";

// L'administrateur seul peut voir cette page
if ($user['role'] != "Admin") {
    // Rediriger vers le tableau de bord
    header('Location: dashboard.php');
    exit;
}

// Variable de version (comme dans config.php)
// $phpblog_version = "2.9.4"; 

// ------------------------------------------------------------
// --- LOGIQUE POUR LES INFORMATIONS SYSTÈME ---
// ------------------------------------------------------------

// Infos de base
$php_version = phpversion();
$db_version_query = mysqli_query($connect, "SELECT VERSION() as version");
$db_version = mysqli_fetch_assoc($db_version_query)['version'];
$max_upload = ini_get('upload_max_filesize');

// Infos Serveur
$server_domain = $_SERVER['SERVER_NAME'];
// Utilise SERVER_ADDR si disponible, sinon tente un gethostbyname
$server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']); 
$server_os = php_uname('s');
$server_software = $_SERVER['SERVER_SOFTWARE'];
$server_port = $_SERVER['SERVER_PORT'];

// Infos PHP étendues
$php_memory_limit = ini_get('memory_limit');
$php_max_execution_time = ini_get('max_execution_time');

// Vérifier les extensions requises (selon le README)
$curl_status = extension_loaded('curl');
$gd_status = extension_loaded('gd');
$mbstring_status = extension_loaded('mbstring');
// ------------------------------------------------------------
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-server"></i> System Information</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">System Information</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-server"></i> System Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server Domain
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($server_domain); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server IP
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($server_ip); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server OS
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($server_os); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server Software
                                        <span class="badge bg-secondary" style="font-size: 0.8em;"><?php echo htmlspecialchars(short_text($server_software, 25)); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server Port
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($server_port); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-top px-0 pt-2">
                                        PHP Version
                                        <span class="badge bg-info"><?php echo $php_version; ?></span>
                                    </li>
                                     <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        PHP Memory Limit
                                        <span class="badge bg-warning text-dark"><?php echo $php_memory_limit; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        PHP Max Execution Time
                                        <span class="badge bg-warning text-dark"><?php echo $php_max_execution_time; ?>s</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        phpBlog Version
                                        <span class="badge bg-primary rounded-pill"><?php echo $phpblog_version; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Active Theme
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($settings['theme']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        MySQL Version
                                        <span class="badge bg-info"><?php echo short_text($db_version, 15); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Max Upload Size
                                        <span class="badge bg-warning text-dark"><?php echo $max_upload; ?></span>
                                    </li>
                                    
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-top px-0 pt-2">
                                        cURL Extension (RSS)
                                        <?php if ($curl_status): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disabled</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        GD Extension (Images)
                                        <?php if ($gd_status): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disabled</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        mbstring Extension
                                        <?php if ($mbstring_status): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disabled</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>