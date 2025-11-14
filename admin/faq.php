<?php
include "header.php";

$message = '';

// --- SUPPRESSION ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    $stmt = mysqli_prepare($connect, "DELETE FROM faqs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Question deleted.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- TOGGLE STATUS ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    $stmt = mysqli_prepare($connect, "UPDATE faqs SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Status updated.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LISTING ---
$items = [];
$q = mysqli_query($connect, "SELECT * FROM faqs ORDER BY position_order ASC, id DESC");
while ($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-question-circle"></i> FAQ Manager</h1>
            </div>
            <div class="col-sm-6">
                <a href="add_faq.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Add Question</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <div class="card card-primary card-outline">
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">Order</th>
                            <th>Question</th>
                            <th>Answer Preview</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($items)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No questions added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($items as $row): ?>
                                <tr>
                                    <td><span class="badge bg-light border"><?php echo $row['position_order']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($row['question']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr(strip_tags(html_entity_decode($row['answer'])), 0, 60)) . '...'; ?></td>
                                    <td>
                                        <?php if($row['active'] == 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_faq.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                        
                                        <a href="?toggle_id=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm <?php echo ($row['active'] == 'Yes' ? 'btn-warning' : 'btn-success'); ?>">
                                           <i class="fas <?php echo ($row['active'] == 'Yes' ? 'fa-eye-slash' : 'fa-eye'); ?>"></i>
                                        </a>
                                        
                                        <a href="?delete_id=<?php echo $row['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this question?');">
                                           <i class="fas fa-trash"></i>
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