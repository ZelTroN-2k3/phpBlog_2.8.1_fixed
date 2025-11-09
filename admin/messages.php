<?php
include "header.php";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Assurer que c'est un entier
    
    // --- VALIDATION CSRF AJOUTÉE ---
    validate_csrf_token_get(); 
    // --- FIN AJOUT ---
    
    // Utiliser une requête préparée pour la suppression
    $stmt = mysqli_prepare($connect, "DELETE FROM messages WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=messages.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-envelope"></i> Messages</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Messages</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">List of Received Messages</h3>
            </div>         
            <div class="card-body">
                <table class="table table-bordered table-hover" id="dt-basic" width="100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM messages ORDER by id DESC");
while ($row = mysqli_fetch_assoc($query)) {
    echo '
                        <tr>
                            <td>' . htmlspecialchars($row['name']) . ' ';
	if($row['viewed'] == 'No') {
		echo '<span class="badge bg-primary">Unread</span>';
	}
	echo '
                            </td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</td>
                            <td>
                                <a class="btn btn-success btn-sm" href="read_message.php?id=' . $row['id'] . '">
                                    <i class="fa fa-eye"></i> View
                                </a>
                                <a class="btn btn-danger btn-sm" href="?id=' . $row['id'] . '&token=' . $csrf_token . '" onclick="return confirm(\'Are you sure you want to delete this message?\');">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
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
    // Activation de DataTables avec ordre par défaut descendant (colonne 2: Date)
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 2, "desc" ]] 
	});
});
</script>
<?php
include "footer.php";
?>