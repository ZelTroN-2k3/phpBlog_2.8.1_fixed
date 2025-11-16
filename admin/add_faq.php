<?php
include "header.php";

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $question = $_POST['question'];
    $answer   = $_POST['answer']; // Contenu HTML (Summernote)
    $active   = $_POST['active'];
    $order    = (int)$_POST['position_order'];

    $stmt = mysqli_prepare($connect, "INSERT INTO faqs (question, answer, active, position_order) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $question, $answer, $active, $order);
    
    if(mysqli_stmt_execute($stmt)) {
        echo '<meta http-equiv="refresh" content="0; url=faq.php">';
        exit;
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Add Question</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card card-primary">
                <div class="card-body">
                    <div class="form-group">
                        <label>Question</label>
                        <input type="text" name="question" class="form-control" required placeholder="Ex: How do I reset my password?">
                    </div>

                    <div class="form-group">
                        <label>Answer</label>
                        <textarea name="answer" class="summernote" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Order (Priority)</label>
                                <input type="number" name="position_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes">Active</option>
                                    <option value="No">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Save</button>
                    <a href="faq.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>
<script>
$(document).ready(function() {
    $('.summernote').summernote({ height: 200 });
});
</script>