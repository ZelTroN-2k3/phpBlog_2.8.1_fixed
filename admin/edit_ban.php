<?php
include "header.php";

$ban_id = null;
$ban_data = null;

if (isset($_GET['id'])) {
    $ban_id = (int)$_GET['id'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM bans WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $ban_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ban_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$ban_data) {
        echo '<div class="alert alert-danger">Ban not found.</div>';
        exit;
    }
} else {
    echo '<div class="alert alert-danger">No ban ID specified.</div>';
    exit;
}

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $type  = $_POST['ban_type'];
    $value = trim($_POST['ban_value']);
    $reason = $_POST['reason'];
    $active = $_POST['active'];

    if (empty($value)) {
        echo '<div class="alert alert-danger">Value is required.</div>';
    } else {
        $stmt = mysqli_prepare($connect, "UPDATE bans SET ban_type=?, ban_value=?, reason=?, active=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $type, $value, $reason, $active, $ban_id);
        
        if(mysqli_stmt_execute($stmt)) {
            echo '<meta http-equiv="refresh" content="0; url=bans.php">';
            exit;
        } else {
            echo '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-edit"></i> Edit Ban #<?php echo $ban_data['id']; ?></h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Ban Details</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ban Type</label>
                                <select name="ban_type" class="form-control">
                                    <option value="ip" <?php if($ban_data['ban_type'] == 'ip') echo 'selected'; ?>>IP Address</option>
                                    <option value="username" <?php if($ban_data['ban_type'] == 'username') echo 'selected'; ?>>Username</option>
                                    <option value="email" <?php if($ban_data['ban_type'] == 'email') echo 'selected'; ?>>E-Mail Address</option>
                                    <option value="user_agent" <?php if($ban_data['ban_type'] == 'user_agent') echo 'selected'; ?>>User-Agent (Bot signature)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Value to Ban</label>
                                <input type="text" name="ban_value" class="form-control" value="<?php echo htmlspecialchars($ban_data['ban_value']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason (Optional)</label>
                        <textarea name="reason" class="form-control"><?php echo htmlspecialchars($ban_data['reason']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="active" class="form-control">
                            <option value="Yes" <?php if($ban_data['active'] == 'Yes') echo 'selected'; ?>>Active</option>
                            <option value="No" <?php if($ban_data['active'] == 'No') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-info">Update Ban</button>
                    <a href="bans.php" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>