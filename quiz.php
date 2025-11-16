<?php
include "core.php";
head(); // Affiche le <head> et le menu de navigation

$purifier = get_purifier();
$quiz_id = $_GET['id'] ?? null; // Vérifie si un ID de quiz est passé dans l'URL

?>
<div class="col-md-8">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-graduation-cap"></i> <?php echo isset($quiz_id) ? "Quiz" : "Liste des Quiz"; ?></h3>
        </div>
        <div class="card-body">

            <?php
            // -----------------------------------------------------------------
            // SCÉNARIO 1 : Afficher un Quiz spécifique (ex: quiz.php?id=1)
            // -----------------------------------------------------------------
            if ($quiz_id) :
                $quiz_id = (int)$quiz_id;

                // 1. Récupérer les informations du Quiz
                $stmt_quiz = mysqli_prepare($connect, "SELECT * FROM quizzes WHERE id = ? AND active = 'Yes'");
                mysqli_stmt_bind_param($stmt_quiz, "i", $quiz_id);
                mysqli_stmt_execute($stmt_quiz);
                $quiz_result = mysqli_stmt_get_result($stmt_quiz);
                $quiz = mysqli_fetch_assoc($quiz_result);
                mysqli_stmt_close($stmt_quiz);

                // 2. Récupérer les questions de ce quiz
                $stmt_questions = mysqli_prepare($connect, "SELECT * FROM quiz_questions WHERE quiz_id = ? AND active='Yes' ORDER BY position_order ASC, id ASC");
                mysqli_stmt_bind_param($stmt_questions, "i", $quiz_id);
                mysqli_stmt_execute($stmt_questions);
                $questions_result = mysqli_stmt_get_result($stmt_questions);
                
                $questions_data = [];
                while ($row = mysqli_fetch_assoc($questions_result)) {
                    $questions_data[] = $row;
                }
                $total_questions = count($questions_data);
                mysqli_stmt_close($stmt_questions);

                if ($quiz && $total_questions > 0) :
                    
                    // --- Logique du Badge de Difficulté ---
                    $difficulty = $quiz['difficulty'];
                    $badge_class = 'bg-info text-dark'; // Défaut pour NORMAL
                    switch ($difficulty) {
                        case 'FACILE': $badge_class = 'bg-success'; break;
                        case 'DIFFICILE': $badge_class = 'bg-warning text-dark'; break;
                        case 'EXPERT': $badge_class = 'bg-danger'; break;
                    }
                    // --- Fin Logique ---
            ?>
                    <div class="quiz-header text-center mb-4">
                        <?php if (!empty($quiz['image'])) : ?>
                            <img src="<?php echo htmlspecialchars($quiz['image']); ?>" alt="<?php echo htmlspecialchars($quiz['title']); ?>" class="img-fluid rounded mb-3" style="width: 100%; max-height: 250px; object-fit: cover;">
                        <?php endif; ?>

                        <h2 class="fw-bold"><?php echo htmlspecialchars($quiz['title']); ?></h2>

                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <span class="badge <?php echo $badge_class; ?> fs-6 me-3"><?php echo $difficulty; ?></span>
                            <span class="text-muted"><i class="fas fa-list-ol"></i> <?php echo $total_questions; ?> Questions</span>
                        </div>

                        <div class="text-muted">
                            <?php echo $purifier->purify($quiz['description']); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="quiz-container">
                        <?php
                        $question_num = 1;
                        foreach ($questions_data as $question) :
                            $question_id = $question['id'];
                        ?>
                            <div class="quiz-question mb-4" id="question-<?php echo $question_id; ?>">
                                <h5 class="fw-bold">
                                    Question <?php echo $question_num; ?> / <?php echo $total_questions; ?>
                                </h5>
                                <p class="fs-5"><?php echo htmlspecialchars($question['question']); ?></p>
                                
                                <div class="quiz-options list-group">
                                    <?php
                                    // Récupérer les options pour cette question
                                    $options_query = mysqli_prepare($connect, "SELECT * FROM quiz_options WHERE question_id = ? ORDER BY id ASC");
                                    mysqli_stmt_bind_param($options_query, "i", $question_id);
                                    mysqli_stmt_execute($options_query);
                                    $options_result = mysqli_stmt_get_result($options_query);
                                    
                                    while ($option = mysqli_fetch_assoc($options_result)) :
                                    ?>
                                        <button type="button" 
                                                class="list-group-item list-group-item-action quiz-option-btn" 
                                                data-option-id="<?php echo $option['id']; ?>" 
                                                data-question-id="<?php echo $question_id; ?>">
                                            <?php echo htmlspecialchars($option['title']); ?>
                                        </button>
                                    <?php endwhile; ?>
                                    <?php mysqli_stmt_close($options_query); ?>
                                </div>

                                <div id="explanation-<?php echo $question_id; ?>" class="alert mt-3" style="display: none;"></div>
                            </div>
                        <?php 
                            $question_num++;
                        endforeach; 
                        ?>
                    </div>

                <?php else : ?>
                    <div class="alert alert-warning">Ce quiz n'a pas été trouvé ou ne contient aucune question.</div>
                    <a href="quiz.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour à la liste des quiz</a>
                <?php endif; ?>

            <?php
            // -----------------------------------------------------------------
            // SCÉNARIO 2 : Afficher la liste de tous les quiz (quiz.php)
            // -----------------------------------------------------------------
            else :
                
                // Requête pour lister tous les quiz et compter leurs questions
                $all_quizzes_query = mysqli_query($connect, "
                    SELECT q.*, COUNT(qq.id) AS question_count
                    FROM quizzes q
                    LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id AND qq.active = 'Yes'
                    WHERE q.active = 'Yes'
                    GROUP BY q.id
                    HAVING question_count > 0
                    ORDER BY q.id DESC
                ");

                if (mysqli_num_rows($all_quizzes_query) > 0) :
            ?>
                <p>Bienvenue dans notre centre de quiz. Choisissez un sujet pour tester vos connaissances !</p>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php
                    while ($quiz = mysqli_fetch_assoc($all_quizzes_query)) :
                        
                        // --- Logique du Badge de Difficulté ---
                        $difficulty = $quiz['difficulty'];
                        $badge_class = 'bg-info text-dark'; // Normal
                        switch ($difficulty) {
                            case 'FACILE': $badge_class = 'bg-success'; break;
                            case 'DIFFICILE': $badge_class = 'bg-warning text-dark'; break;
                            case 'EXPERT': $badge_class = 'bg-danger'; break;
                        }
                        // --- Fin Logique ---
                        
                        $quiz_url = "quiz.php?id=" . $quiz['id'];
                        $image_src = !empty($quiz['image']) ? $quiz['image'] : 'assets/img/no-image.png'; // Mettez une image par défaut
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <a href="<?php echo $quiz_url; ?>">
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($quiz['title']); ?>" style="height: 150px; object-fit: cover;">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title fw-bold">
                                        <a href="<?php echo $quiz_url; ?>" class="text-decoration-none"><?php echo htmlspecialchars($quiz['title']); ?></a>
                                    </h5>
                                    <p class="card-text small text-muted">
                                        <?php echo htmlspecialchars(short_text(strip_tags($purifier->purify($quiz['description'])), 100)); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $difficulty; ?></span>
                                    <span class="text-muted small"><i class="fas fa-list-ol"></i> <?php echo $quiz['question_count']; ?> Questions</span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <div class="alert alert-info">Il n'y a pas de quiz disponibles pour le moment.</div>
            <?php endif; ?>

            <?php endif; // Fin du if/else principal ?>

        </div>
    </div>
</div>

<?php 
sidebar(); 
footer(); 
?>

<style>
    .quiz-option-btn { cursor: pointer; }
    .quiz-option-btn.correct {
        background-color: #d1e7dd; border-color: #a3cfbb;
        color: #0a3622; font-weight: bold;
    }
    .quiz-option-btn.wrong {
        background-color: #f8d7da; border-color: #f1aeb5;
        color: #58151c;
    }
</style>

<script>
$(document).ready(function() {
    // Ce script ne s'activera que si les boutons .quiz-option-btn existent
    // (c'est-à-dire, uniquement sur la page d'un quiz spécifique)
    $('.quiz-option-btn').on('click', function() {
        
        var $thisButton = $(this);
        var optionId = $thisButton.data('option-id');
        var questionId = $thisButton.data('question-id');
        var $questionContainer = $('#question-' + questionId);
        var $explanationBox = $('#explanation-' + questionId);
        var $allOptions = $questionContainer.find('.quiz-option-btn');

        $allOptions.prop('disabled', true);

        $.ajax({
            url: 'ajax_check_answer.php',
            type: 'POST',
            data: {
                option_id: optionId,
                question_id: questionId,
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'correct') {
                    $thisButton.addClass('correct');
                    $explanationBox.addClass('alert-success').html(response.explanation).slideDown();
                    
                } else if (response.status === 'wrong') {
                    $thisButton.addClass('wrong');
                    $questionContainer.find('.quiz-option-btn[data-option-id="' + response.correct_option_id + '"]').addClass('correct');
                    $explanationBox.addClass('alert-danger').html(response.explanation).slideDown();
                    
                } else {
                    $explanationBox.addClass('alert-warning').text(response.message || 'Une erreur est survenue.').slideDown();
                    $allOptions.prop('disabled', false); 
                }
            },
            error: function() {
                $explanationBox.addClass('alert-danger').text('Erreur de connexion avec le serveur.').slideDown();
                $allOptions.prop('disabled', false);
            }
        });
    });
});
</script>