<?php
include "header.php";

// --- LOGIQUE SUPPRESSION ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];

    // 1. Récupérer le chemin pour supprimer le fichier physique
    $stmt = mysqli_prepare($connect, "SELECT path FROM `files` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $file_path = "../" . $row['path']; // Chemin relatif depuis le dossier admin
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }

        // 2. Supprimer de la BDD
        $stmt_delete = mysqli_prepare($connect, "DELETE FROM `files` WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }
    
    echo '<meta http-equiv="refresh" content="0; url=files.php">';
    exit;
}

// Fonction utilitaire pour formater la taille (Optionnel mais plus joli)
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder-open"></i> File Manager</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Files</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="upload_file.php" class="btn btn-success btn-sm">
                                <i class="fas fa-cloud-upload-alt"></i> Upload New File
                            </a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <table id="dt-files" class="table table-bordered table-hover table-striped" style="width:100%">
                            <thead>
                                <tr>
									<th style="width: 30px;"class="text-center">ID</th>
                                    <th style="width: 60px;" class="text-center">Preview</th>
                                    <th>Filename & Details</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Size</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM `files` ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
    
    $filename = htmlspecialchars($row['filename']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $path = htmlspecialchars($row['path']); // ex: uploads/files/image.jpg
    $full_url = $settings['site_url'] . '/' . $path;
    //$file_size = is_numeric($row['id']) ? $row['id'] : 0; // Note: Votre BDD n'a peut-être pas de colonne size, à adapter si besoin.
    // Vérifier si le fichier existe pour éviter les erreurs filesize et filetype sur des fichiers supprimés manuellement
    $full_path = '../' . $row['path'];
    $file_type = file_exists($full_path) ? filetype($full_path) : 'N/A';    
    $file_size = file_exists($full_path) ? byte_convert(filesize($full_path)) : 'N/A';

    // --- LOGIQUE D'ICÔNES ---
    $icon = '<i class="fas fa-file fa-2x text-secondary"></i>'; // Défaut
    $is_image = false;

    // Images
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
        $is_image = true;
        $icon = '<img src="../' . $path . '" width="50" height="50" style="object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
    }
    // Documents
    elseif (in_array($ext, ['pdf'])) {
        $icon = '<i class="fas fa-file-pdf fa-2x text-danger"></i>';
    }
    elseif (in_array($ext, ['doc', 'docx', 'odt', 'rtf', 'txt'])) {
        $icon = '<i class="fas fa-file-word fa-2x text-primary"></i>';
    }
    elseif (in_array($ext, ['xls', 'xlsx', 'ods', 'csv'])) {
        $icon = '<i class="fas fa-file-excel fa-2x text-success"></i>';
    }
    elseif (in_array($ext, ['ppt', 'pptx', 'odp'])) {
        $icon = '<i class="fas fa-file-powerpoint fa-2x text-warning"></i>';
    }
    // Archives
    elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
        $icon = '<i class="fas fa-file-archive fa-2x text-warning"></i>';
    }
    // Audio
    elseif (in_array($ext, ['mp3', 'wav', 'wma', 'aac', 'flac', 'm4a'])) {
        $icon = '<i class="fas fa-file-audio fa-2x text-info"></i>';
    }
    // Vidéo
    elseif (in_array($ext, ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm', 'ts'])) {
        $icon = '<i class="fas fa-file-video fa-2x text-purple"></i>';
    }

    echo '
        <tr>
			<td class="text-center align-middle">' . $row['id'] . '</td>
            <td class="text-center align-middle">' . $icon . '</td>
            <td class="align-middle">
                <strong>' . $filename . '</strong><br>
                <small class="text-muted"><i class="fas fa-link"></i> ' . $path . '</small>
            </td>
            <td class="text-center align-middle"><span class="badge badge-light border">' . strtoupper($ext) . '</span></td>
            <td class="text-center align-middle">' . htmlspecialchars($file_size) . '</td> <td class="text-center align-middle" data-sort="' . strtotime($row['created_at']) . '">' . date('d M Y', strtotime($row['created_at'])) . '</td>
            <td class="text-center align-middle">
                <a href="../' . $path . '" target="_blank" class="btn btn-info btn-sm" title="View/Download"><i class="fas fa-eye"></i></a>
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyLink(\'' . $full_url . '\')" title="Copy URL"><i class="fas fa-copy"></i></button>
                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this file permanently?\');" title="Delete"><i class="fas fa-trash"></i></a>
            </td>
        </tr>';
}
?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#dt-files').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 4, "desc" ]] // Trier par date
    });
});

// Fonction pour copier le lien
function copyLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        $(document).Toasts('create', {
            class: 'bg-success',
            title: 'Copied!',
            body: 'File URL copied to clipboard.',
            autohide: true,
            delay: 2000
        });
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
    });
}
</script>