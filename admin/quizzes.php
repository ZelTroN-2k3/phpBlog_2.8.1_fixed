<?php
// 1. INCLURE LE NOYAU D'ABORD
include_once '../core.php'; 

// 2. VÉRIFICATION DE SÉCURITÉ
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND (role='Admin' OR role='Editor')");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($suser) <= 0) {
        header("Location: " . $settings['site_url']); exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    header("Location: ../login"); exit;
}

// --- Logique de Suppression ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete-id'];

    // Avant de supprimer le quiz, nous devons supprimer ses dépendances :
    // 1. Trouver toutes les questions
    $q_ids_stmt = mysqli_prepare($connect, "SELECT id FROM quiz_questions WHERE quiz_id = ?");
    mysqli_stmt_bind_param($q_ids_stmt, "i", $id);
    mysqli_stmt_execute($q_ids_stmt);
    $q_ids_result = mysqli_stmt_get_result($q_ids_stmt);
    $question_ids = [];
    while ($row = mysqli_fetch_assoc($q_ids_result)) {
        $question_ids[] = $row['id'];
    }
    mysqli_stmt_close($q_ids_stmt);

    // 2. Supprimer les options de ces questions (si elles existent)
    if (!empty($question_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($question_ids), '?'));
        $types = str_repeat('i', count($question_ids));
        
        $del_opt_stmt = mysqli_prepare($connect, "DELETE FROM quiz_options WHERE question_id IN ($ids_placeholder)");
        mysqli_stmt_bind_param($del_opt_stmt, $types, ...$question_ids);
        mysqli_stmt_execute($del_opt_stmt);
        mysqli_stmt_close($del_opt_stmt);
    }
    
    // 3. Supprimer les questions
    $del_q_stmt = mysqli_prepare($connect, "DELETE FROM quiz_questions WHERE quiz_id = ?");
    mysqli_stmt_bind_param($del_q_stmt, "i", $id);
    mysqli_stmt_execute($del_q_stmt);
    mysqli_stmt_close($del_q_stmt);
    
    // 4. Supprimer le quiz lui-même
    $del_quiz_stmt = mysqli_prepare($connect, "DELETE FROM quizzes WHERE id = ?");
    mysqli_stmt_bind_param($del_quiz_stmt, "i", $id);
    mysqli_stmt_execute($del_quiz_stmt);
    mysqli_stmt_close($del_quiz_stmt);

    header("Location: quizzes.php");
    exit;
}

// --- Logique de Basculement de Statut (Toggle) ---
$csrf_token = $_SESSION['csrf_token'];

if (isset($_GET['activate-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['activate-id'];
    $stmt_activate = mysqli_prepare($connect, "UPDATE quizzes SET active = 'Yes' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_activate, "i", $id);
    mysqli_stmt_execute($stmt_activate);
    mysqli_stmt_close($stmt_activate);
    header("Location: quizzes.php");
    exit;
}

if (isset($_GET['deactivate-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['deactivate-id'];
    $stmt_deactivate = mysqli_prepare($connect, "UPDATE quizzes SET active = 'No' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_deactivate, "i", $id);
    mysqli_stmt_execute($stmt_deactivate);
    mysqli_stmt_close($stmt_deactivate);
    header("Location: quizzes.php");
    exit;
}

// 3. INCLURE LE HEADER HTML
include 'header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gérer les Quiz</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Quiz Manager</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tous les Quiz</h3>
                        <a href="add_quiz.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Créer un nouveau Quiz</a>
                    </div>
                    <div class="card-body">
                        <table id="quiz-table" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Titre</th>
                                    <th>Difficulté</th>
                                    <th>Questions</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Requête pour lister les quiz et compter les questions
                                $query_quizzes = mysqli_query($connect, "
                                    SELECT q.*, COUNT(qq.id) AS question_count
                                    FROM quizzes q
                                    LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
                                    GROUP BY q.id
                                    ORDER BY q.id DESC
                                ");
                                while ($row = mysqli_fetch_assoc($query_quizzes)) {
                                    
                                    // Badge de difficulté
                                    $badge_class = 'bg-info text-dark'; // Normal
                                    switch ($row['difficulty']) {
                                        case 'FACILE': $badge_class = 'bg-success'; break;
                                        case 'DIFFICILE': $badge_class = 'bg-warning text-dark'; break;
                                        case 'EXPERT': $badge_class = 'bg-danger'; break;
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <?php if(!empty($row['image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($row['image']); ?>" alt="Image" style="width: 100px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span class="text-muted small">Aucune image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['difficulty']; ?></span></td>
                                        <td>
                                            <a href="quiz_questions.php?quiz_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary">
                                                Gérer (<?php echo $row['question_count']; ?>)
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($row['active'] == 'Yes') : ?>
                                                <span class="badge badge-success">Actif</span>
                                            <?php else : ?>
                                                <span class="badge badge-warning">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['active'] == 'Yes') : ?>
                                                <a href="quizzes.php?deactivate-id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-warning btn-sm" title="Désactiver"><i class="fas fa-toggle-off"></i></a>
                                            <?php else : ?>
                                                <a href="quizzes.php?activate-id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-success btn-sm" title="Activer"><i class="fas fa-toggle-on"></i></a>
                                            <?php endif; ?>

                                            <a href="edit_quiz.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="Modifier le Quiz"><i class="fas fa-edit"></i></a>
                                            
                                            <a href="quizzes.php?delete-id=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Voulez-vous vraiment supprimer ce Quiz ET toutes ses questions et réponses associées ?');" title="Supprimer le Quiz">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include 'footer.php';
?>

<script>
$(function () {
    $('#quiz-table').DataTable({
        "paging": true, "lengthChange": true, "searching": true,
        "ordering": true, "info": true, "autoWidth": false, "responsive": true,
        "order": [[ 0, "desc" ]]
    });
});
</script>