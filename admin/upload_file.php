<?php
include "header.php";

if (isset($_POST['upload'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $file     = $_FILES['file'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $name     = $_FILES['file']['name'];
    
    $format = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_formats = ["png", "gif", "jpeg", "jpg", "bmp", "doc", "docx", "pdf", "txt", "rar", "zip", "odt", "rtf", "csv", "ods", "xls", "xlsx", "odp", "ppt", "pptx", "mp3", "flac", "wav", "wma", "aac", "m4a", "mov", "avi", "mkv", "mp4", "wmv", "webm", "ts", "webp"];
        $allowed_mime_types = [
            'png' => 'image/png', 'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
            'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
            'rar' => 'application/x-rar-compressed', 'zip' => 'application/zip', 'odt' => 'application/vnd.oasis.opendocument.text', 'rtf' => 'application/rtf',
            'csv' => 'text/csv', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet', 'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'odp' => 'application/vnd.oasis.opendocument.presentation', 'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'mp3' => 'audio/mpeg', 'flac' => 'audio/x-flac', 'wav' => 'audio/wav', 'wma' => 'audio/x-ms-wma', 'aac' => 'audio/aac', 'm4a' => 'audio/mp4',
            'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska', 'mp4' => 'video/mp4', 'wmv' => 'video/x-ms-wmv', 'webm' => 'video/webm', 'ts' => 'video/mp2t'
        ];
    
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
    
        $upload_successful = false;
    
        // Vérification de la taille du fichier (par exemple, 10 Mo max)
        if ($_FILES['file']['size'] > 10485760) { // 10 * 1024 * 1024
            echo '<div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    The file is too large. The limit is 10 MB.
                  </div>';
        } else if (!in_array($format, $allowed_formats) || (isset($allowed_mime_types[$format]) && $allowed_mime_types[$format] !== $mime_type)) {
            echo '<div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    The file type (.$format / $mime_type) is invalid or does not match its content.
                  </div>';
        } else {        $string     = "0123456789wsderfgtyhjuk";
        $new_string = str_shuffle($string);
        // Le chemin est relatif au dossier du projet (un niveau au-dessus de /admin)
        $location   = "uploads/other/file_$new_string.$format"; 
        
        // move_uploaded_file a besoin du chemin absolu ou relatif à l'exécution
        if (move_uploaded_file($tmp_name, '../' . $location)) { 
            $stmt = mysqli_prepare($connect, "INSERT INTO `files` (filename, path, created_at) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "ss", $name, $location);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $upload_successful = true;
        } else {
             echo '<div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    Failed to move the uploaded file. Check folder permissions.
                  </div>';
        }
    }
    
    if ($upload_successful) {
        echo '<meta http-equiv="refresh" content="0; url=files.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-upload"></i> Upload File</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="files.php">Files</a></li>
                    <li class="breadcrumb-item active">Upload File</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        
        <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">Upload File to Server</h3>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>File</label>
                        <input type="file" name="file" class="form-control" required />
                        <small class="text-muted">Allowed types: png, gif, jpg, jpeg, bmp, doc, docx, pdf, txt, rar, zip, odt, rtf, csv, ods, xls, xlsx, odp, ppt, pptx, mp3, flac, wav, wma, aac, m4a, mov, avi, mkv, mp4, wmv, webm, ts, webp.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="upload" class="btn btn-primary" value="Upload" />
                </div>
            </form>
        </div>

    </div></section>
<?php
include "footer.php";
?>