<?php
include "header.php";

$message = '';

// --- SUPPRESSION D'UN SONDAGE ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    
    // 1. Supprimer les votes enregistrés (historique IP)
    $stmt1 = mysqli_prepare($connect, "DELETE FROM poll_voters WHERE poll_id = ?");
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // 2. Supprimer les options de réponse
    $stmt2 = mysqli_prepare($connect, "DELETE FROM poll_options WHERE poll_id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    // 3. Supprimer le sondage lui-même
    $stmt3 = mysqli_prepare($connect, "DELETE FROM polls WHERE id = ?");
    mysqli_stmt_bind_param($stmt3, "i", $id);
    
    if(mysqli_stmt_execute($stmt3)) {
        $message = '<div class="alert alert-success">Poll deleted successfully.</div>';
    }
    mysqli_stmt_close($stmt3);
}

// --- RÉINITIALISER LES VOTES (RESET) ---
if (isset($_GET['reset_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['reset_id'];
    
    // 1. Vider la table des votants pour ce sondage
    $stmt1 = mysqli_prepare($connect, "DELETE FROM poll_voters WHERE poll_id = ?");
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // 2. Remettre les compteurs à 0 dans poll_options
    $stmt2 = mysqli_prepare($connect, "UPDATE poll_options SET votes = 0 WHERE poll_id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    
    if(mysqli_stmt_execute($stmt2)) {
        $message = '<div class="alert alert-success">Votes reset successfully. (Note: You must clear your browser cookies to vote again).</div>';
    }
    mysqli_stmt_close($stmt2);
}

// --- BASCULE ACTIF/INACTIF ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    // Astuce SQL : On inverse la valeur 'Yes'/'No'
    $stmt = mysqli_prepare($connect, "UPDATE polls SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Status updated.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- RÉCUPÉRATION DES SONDAGES ---
$polls = [];
$q = mysqli_query($connect, "SELECT * FROM polls ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($q)) {
    // On compte le total des votes pour ce sondage
    $pid = $row['id'];
    $q_votes = mysqli_query($connect, "SELECT SUM(votes) as total FROM poll_options WHERE poll_id = $pid");
    $r_votes = mysqli_fetch_assoc($q_votes);
    $row['total_votes'] = (int)$r_votes['total'];
    
    $polls[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-poll"></i> Polls Manager</h1>
            </div>
            <div class="col-sm-6">
                <a href="add_poll.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> New Poll</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">List of Polls</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Question</th>
                            <th>Total Votes</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($polls)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No polls created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($polls as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['question']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo $p['total_votes']; ?> votes</span></td>
                                    <td>
                                        <?php if($p['active'] == 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_poll.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>                                        
                                        <a href="?toggle_id=<?php echo $p['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm <?php echo ($p['active'] == 'Yes' ? 'btn-warning' : 'btn-success'); ?>" title="Toggle Active/Inactive">
                                           <i class="fas <?php echo ($p['active'] == 'Yes' ? 'fa-eye-slash' : 'fa-eye'); ?>"></i>
                                        </a>
                                        
                                        <a href="?delete_id=<?php echo $p['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure? All votes for this poll will be deleted.');" title="Delete">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="?reset_id=<?php echo $p['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           onclick="return confirm('Are you sure? This will reset all votes for this poll.');" title="Reset Votes">
                                           <i class="fas fa-redo"></i>
                                        </a>    
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>