<?php
include "header.php";

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $question = trim($_POST['question']);
    $active   = $_POST['active'];
    $options  = $_POST['options']; // C'est un tableau (array) grâce à name="options[]"
    
    if (empty($question)) {
        echo '<div class="alert alert-danger">Question is required.</div>';
    } else {
        // 1. Insérer le sondage
        $stmt = mysqli_prepare($connect, "INSERT INTO polls (question, active) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $question, $active);
        
        if(mysqli_stmt_execute($stmt)) {
            $poll_id = mysqli_insert_id($connect); // On récupère l'ID du nouveau sondage
            mysqli_stmt_close($stmt);
            
            // 2. Insérer les options (Boucle)
            $stmt_opt = mysqli_prepare($connect, "INSERT INTO poll_options (poll_id, title) VALUES (?, ?)");
            
            foreach ($options as $opt_text) {
                $opt_text = trim($opt_text);
                if (!empty($opt_text)) { // On n'insère pas les lignes vides
                    mysqli_stmt_bind_param($stmt_opt, "is", $poll_id, $opt_text);
                    mysqli_stmt_execute($stmt_opt);
                }
            }
            mysqli_stmt_close($stmt_opt);
            
            echo '<meta http-equiv="refresh" content="0; url=polls.php">';
            exit;
        } else {
            echo '<div class="alert alert-danger">Error creating poll.</div>';
        }
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-plus-circle"></i> Create New Poll</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Poll Details</h3></div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label>Question</label>
                                <input type="text" name="question" class="form-control form-control-lg" placeholder="Ex: What is your favorite programming language?" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes">Active (Visible)</option>
                                    <option value="No">Inactive (Draft)</option>
                                </select>
                            </div>

                            <hr>
                            <label>Answers / Options</label>
                            <div id="options-container">
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend"><span class="input-group-text">1</span></div>
                                    <input type="text" name="options[]" class="form-control" placeholder="Option 1 (ex: PHP)" required>
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend"><span class="input-group-text">2</span></div>
                                    <input type="text" name="options[]" class="form-control" placeholder="Option 2 (ex: Python)" required>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addOption()">
                                <i class="fas fa-plus"></i> Add Another Option
                            </button>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">Create Poll</button>
                            <a href="polls.php" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Tip</h5>
                        Create a question and add as many options as you need. 
                        Once created, you can display this poll in your sidebar using the Widgets manager (coming soon).
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>

<script>
// Script pour ajouter des champs dynamiquement
let optionCount = 2;

function addOption() {
    optionCount++;
    const container = document.getElementById('options-container');
    
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    
    div.innerHTML = `
        <div class="input-group-prepend">
            <span class="input-group-text">${optionCount}</span>
        </div>
        <input type="text" name="options[]" class="form-control" placeholder="Option ${optionCount}" required>
        <div class="input-group-append">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    container.appendChild(div);
}
</script>