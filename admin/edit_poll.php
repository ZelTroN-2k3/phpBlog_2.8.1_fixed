<?php
include "header.php";

// 1. VÉRIFICATION ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0; url=polls.php">';
    exit;
}
$poll_id = (int)$_GET['id'];

$message = '';

// 2. SUPPRESSION D'UNE OPTION (Via lien GET)
if (isset($_GET['delete_option'])) {
    validate_csrf_token_get();
    $opt_id = (int)$_GET['delete_option'];
    
    // Sécurité : Vérifier que l'option appartient bien à ce sondage
    $check = mysqli_query($connect, "SELECT id FROM poll_options WHERE id=$opt_id AND poll_id=$poll_id");
    if (mysqli_num_rows($check) > 0) {
        $stmt_del = mysqli_prepare($connect, "DELETE FROM poll_options WHERE id=?");
        mysqli_stmt_bind_param($stmt_del, "i", $opt_id);
        mysqli_stmt_execute($stmt_del);
        $message = '<div class="alert alert-success">Option deleted.</div>';
    }
}

// 3. TRAITEMENT DU FORMULAIRE (Mise à jour)
if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $question = trim($_POST['question']);
    $active   = $_POST['active'];
    
    // A. Mise à jour du sondage principal
    $stmt = mysqli_prepare($connect, "UPDATE polls SET question=?, active=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssi", $question, $active, $poll_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // B. Mise à jour des options EXISTANTES
    if (isset($_POST['existing_options'])) {
        $stmt_upd = mysqli_prepare($connect, "UPDATE poll_options SET title=? WHERE id=? AND poll_id=?");
        foreach ($_POST['existing_options'] as $oid => $title) {
            $title = trim($title);
            if (!empty($title)) {
                mysqli_stmt_bind_param($stmt_upd, "sii", $title, $oid, $poll_id);
                mysqli_stmt_execute($stmt_upd);
            }
        }
        mysqli_stmt_close($stmt_upd);
    }
    
    // C. Insertion des NOUVELLES options
    if (isset($_POST['new_options'])) {
        $stmt_ins = mysqli_prepare($connect, "INSERT INTO poll_options (poll_id, title, votes) VALUES (?, ?, 0)");
        foreach ($_POST['new_options'] as $new_title) {
            $new_title = trim($new_title);
            if (!empty($new_title)) {
                mysqli_stmt_bind_param($stmt_ins, "is", $poll_id, $new_title);
                mysqli_stmt_execute($stmt_ins);
            }
        }
        mysqli_stmt_close($stmt_ins);
    }
    
    $message = '<div class="alert alert-success">Poll updated successfully.</div>';
}

// 4. RÉCUPÉRATION DES DONNÉES
// Le Sondage
$q_poll = mysqli_query($connect, "SELECT * FROM polls WHERE id = $poll_id");
$poll   = mysqli_fetch_assoc($q_poll);

if (!$poll) {
    echo '<div class="alert alert-danger">Poll not found.</div>';
    include "footer.php";
    exit;
}

// Les Options
$options = [];
$q_opts = mysqli_query($connect, "SELECT * FROM poll_options WHERE poll_id = $poll_id ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($q_opts)) {
    $options[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-edit"></i> Edit Poll</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Poll Details</h3></div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label>Question</label>
                                <input type="text" name="question" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($poll['question']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes" <?php if($poll['active']=='Yes') echo 'selected'; ?>>Active (Visible)</option>
                                    <option value="No" <?php if($poll['active']=='No') echo 'selected'; ?>>Inactive (Draft)</option>
                                </select>
                            </div>

                            <hr>
                            <label>Existing Answers</label>
                            <div class="mb-3">
                                <?php foreach($options as $opt): ?>
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><small><?php echo $opt['votes']; ?> votes</small></span>
                                    </div>
                                    <input type="text" name="existing_options[<?php echo $opt['id']; ?>]" 
                                           class="form-control" value="<?php echo htmlspecialchars($opt['title']); ?>" required>
                                    
                                    <div class="input-group-append">
                                        <a href="?id=<?php echo $poll_id; ?>&delete_option=<?php echo $opt['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Delete this option?');" title="Delete Option">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <label>Add New Options</label>
                            <div id="new-options-container"></div>
                            
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addNewOption()">
                                <i class="fas fa-plus"></i> Add Another Option
                            </button>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="submit" class="btn btn-primary">Update Poll</button>
                            <a href="polls.php" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Stats</h3></div>
                        <div class="card-body">
                            <p><strong>Created:</strong> <?php echo $poll['created_at']; ?></p>
                            <p><strong>Total Options:</strong> <?php echo count($options); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>

<script>
function addNewOption() {
    const container = document.getElementById('new-options-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    
    div.innerHTML = `
        <div class="input-group-prepend">
            <span class="input-group-text text-success"><i class="fas fa-plus"></i></span>
        </div>
        <input type="text" name="new_options[]" class="form-control" placeholder="New Option" required>
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    container.appendChild(div);
}
</script>