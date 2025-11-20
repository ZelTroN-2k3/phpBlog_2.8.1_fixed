<?php
include "header.php";

if (!isset($_GET['id'])) exit;
$id = (int)$_GET['id'];

$q = mysqli_query($connect, "SELECT * FROM faqs WHERE id=$id");
$row = mysqli_fetch_assoc($q);
if(!$row) { echo "Not found"; exit; }

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $question = $_POST['question'];
    $answer   = $_POST['answer'];
    $active   = $_POST['active'];
    $order    = (int)$_POST['position_order'];

    $stmt = mysqli_prepare($connect, "UPDATE faqs SET question=?, answer=?, active=?, position_order=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssii", $question, $answer, $active, $order, $id);
    
    if(mysqli_stmt_execute($stmt)) {
        echo '<meta http-equiv="refresh" content="0; url=faq.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Edit Question</h1>
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
                        <input type="text" name="question" class="form-control" value="<?php echo htmlspecialchars($row['question']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Answer</label>
                        <textarea name="answer" class="summernote" required><?php echo $row['answer']; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Order</label>
                                <input type="number" name="position_order" class="form-control" value="<?php echo (int)$row['position_order']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes" <?php if($row['active']=='Yes') echo 'selected'; ?>>Active</option>
                                    <option value="No" <?php if($row['active']=='No') echo 'selected'; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Update</button>
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