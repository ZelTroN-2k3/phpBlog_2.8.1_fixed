<?php
include "header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($id)) {
    echo '<meta http-equiv="refresh" content="0; url=messages.php">';
    exit;
}

// Use prepared statement for UPDATE (Marquer comme lu)
$stmt_update = mysqli_prepare($connect, "UPDATE `messages` SET viewed='Yes' WHERE id=?");
mysqli_stmt_bind_param($stmt_update, "i", $id);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);

// Use prepared statement for SELECT
$stmt_select = mysqli_prepare($connect, "SELECT * FROM `messages` WHERE id=?");
mysqli_stmt_bind_param($stmt_select, "i", $id);
mysqli_stmt_execute($stmt_select);
$runq = mysqli_stmt_get_result($stmt_select);
$row = mysqli_fetch_assoc($runq);
mysqli_stmt_close($stmt_select);


if (!$row) {
    echo '<meta http-equiv="refresh" content="0; url=messages.php">';
    exit;
}
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-envelope"></i> Read Message</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="messages.php">Messages</a></li>
                    <li class="breadcrumb-item active">Message #<?php echo $id; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Message Details</h3>
            </div>
            <div class="card-body">
                
                <a href="messages.php" class="btn btn-default mb-3">
                    <i class="fa fa-arrow-left"></i> Back to Messages
                </a>
                
                <p>
                    <strong><i class="fa fa-user"></i> Sender:</strong> 
                    <span class="text-primary"><?php echo htmlspecialchars($row['name']); ?></span>
                </p>
                <p>
                    <strong><i class="fa fa-envelope"></i> E-Mail Address:</strong> 
                    <span class="text-primary"><?php echo htmlspecialchars($row['email']); ?></span>
                </p>
                <p>
                    <strong><i class="fa fa-calendar-alt"></i> Date:</strong> 
                    <?php echo date($settings['date_format'] . ' H:i', strtotime($row['created_at'])); ?>
                </p>
                <hr>
                <p>
                    <strong><i class="fa fa-file"></i> Message:</strong>
                </p>
                <div class="p-3 mb-3 bg-light rounded border">
                    <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                </div>
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="btn btn-primary col-12" target="_blank">
                            <i class="fa fa-reply"></i> Reply
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="messages.php?id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger col-12" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                    </div>
                </div>

            </div>
        </div>
        
    </div></section>
<?php
include "footer.php";
?>