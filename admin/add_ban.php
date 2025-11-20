<?php
include "header.php";

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $type  = $_POST['ban_type'];
    $value = trim($_POST['ban_value']);
    $reason = $_POST['reason'];

    if (empty($value)) {
        echo '<div class="alert alert-danger">Value is required.</div>';
    } else {
        // Sécurité : Empêcher de se bannir soi-même (IP ou Username)
        $my_ip = $_SERVER['REMOTE_ADDR'];
        $my_username = $user['username']; // $user vient de header.php
        
        if (($type == 'ip' && $value == $my_ip) || ($type == 'username' && $value == $my_username)) {
             echo '<div class="alert alert-danger">Safety Warning: You cannot ban yourself!</div>';
        } else {
            $stmt = mysqli_prepare($connect, "INSERT INTO bans (ban_type, ban_value, reason) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $type, $value, $reason);
            
            if(mysqli_stmt_execute($stmt)) {
                echo '<meta http-equiv="refresh" content="0; url=bans.php">';
                exit;
            } else {
                echo '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-ban"></i> Ban a User/IP</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card card-danger">
                <div class="card-header"><h3 class="card-title">Ban Details</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ban Type</label>
                                <select name="ban_type" class="form-control">
                                    <option value="ip">IP Address</option>
                                    <option value="username">Username</option>
                                    <option value="email">E-Mail Address</option>
                                    <option value="user_agent">User-Agent (Bot signature)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Value to Ban</label>
                                <input type="text" name="ban_value" class="form-control" placeholder="Ex: 192.168.1.5 or Spammer123" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason (Optional)</label>
                        <textarea name="reason" class="form-control" placeholder="Why are you banning this? (Displayed to the user)"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-danger">Ban Now</button>
                    <a href="bans.php" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>