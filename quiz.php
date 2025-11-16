<?php
include "core.php";

// --- NOUVELLE PARTIE : GESTION DE LA SOUMISSION DU QUIZ ---
// Cette logique doit être AVANT head() pour gérer la redirection
if (isset($_POST['submit_quiz']) && $logged == 'Yes') {
    
    // 1. Validation CSRF
    validate_csrf_token();
    
    $quiz_id = (int)$_POST['quiz_id'];
    $start_time = (int)$_POST['start_time'];
    $user_answers = $_POST['answers'] ?? [];
    $user_id = $rowu['id']; // $rowu est défini dans core.php

    $time_taken = time() - $start_time;
    $total_questions = 0;
    $correct_answers = 0;

    // 2. Vérifier les réponses
    if (!empty($user_answers)) {
        // Obtenir toutes les bonnes réponses pour ce quiz en une seule fois
        $stmt_correct = mysqli_prepare($connect, "
            SELECT opt.question_id, opt.id 
            FROM quiz_options opt
            JOIN quiz_questions q ON opt.question_id = q.id
            WHERE q.quiz_id = ? AND opt.is_correct = 'Yes'
        ");
        mysqli_stmt_bind_param($stmt_correct, "i", $quiz_id);
        mysqli_stmt_execute($stmt_correct);
        $correct_result = mysqli_stmt_get_result($stmt_correct);
        
        $correct_map = [];
        while ($row = mysqli_fetch_assoc($correct_result)) {
            $correct_map[$row['question_id']] = $row['id'];
        }
        mysqli_stmt_close($stmt_correct);

        $total_questions = count($correct_map);

        // Comparer les réponses de l'utilisateur
        foreach ($user_answers as $question_id => $option_id) {
            if (isset($correct_map[$question_id]) && $correct_map[$question_id] == $option_id) {
                $correct_answers++;
            }
        }
    }

    // 3. Calculer le score
    $score_percentage = ($total_questions > 0) ? round(($correct_answers / $total_questions) * 100) : 0;

    // 4. Enregistrer la tentative
    $stmt_insert = mysqli_prepare($connect, "
        INSERT INTO quiz_attempts (quiz_id, user_id, score, time_seconds, attempt_date) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param($stmt_insert, "iiii", $quiz_id, $user_id, $score_percentage, $time_taken);
    mysqli_stmt_execute($stmt_insert);
    $new_attempt_id = mysqli_insert_id($connect);
    mysqli_stmt_close($stmt_insert);

    // 5. Rediriger pour éviter la re-soumission (PRG Pattern)
    // Nous ajoutons un paramètre 'attempt' pour afficher un message de succès
    header("Location: quiz.php?id=" . $quiz_id . "&attempt=" . $new_attempt_id);
    exit;
}
// --- FIN DE LA GESTION DE LA SOUMISSION ---


// --- Affichage normal de la page ---
head(); 
$purifier = get_purifier();
$quiz_id_get = $_GET['id'] ?? null;

?>
<div class="col-md-8">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-graduation-cap"></i> <?php echo isset($quiz_id_get) ? "Quiz" : "Liste des Quiz"; ?></h3>
        </div>
        <div class="card-body">

            <?php
            // -----------------------------------------------------------------
            // SCÉNARIO 1 : Afficher un Quiz spécifique (ex: quiz.php?id=1)
            // -----------------------------------------------------------------
            if ($quiz_id_get) :
                $quiz_id = (int)$quiz_id_get;

                // --- Récupérer les informations du Quiz ---
                $stmt_quiz = mysqli_prepare($connect, "SELECT * FROM quizzes WHERE id = ? AND active = 'Yes'");
                mysqli_stmt_bind_param($stmt_quiz, "i", $quiz_id);
                mysqli_stmt_execute($stmt_quiz);
                $quiz_result = mysqli_stmt_get_result($stmt_quiz);
                $quiz = mysqli_fetch_assoc($quiz_result);
                mysqli_stmt_close($stmt_quiz);

                // --- Récupérer le nombre de questions ---
                $stmt_q_count = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM quiz_questions WHERE quiz_id = ? AND active='Yes'");
                mysqli_stmt_bind_param($stmt_q_count, "i", $quiz_id);
                mysqli_stmt_execute($stmt_q_count);
                $count_result = mysqli_stmt_get_result($stmt_q_count);
                $total_questions = mysqli_fetch_assoc($count_result)['count'];
                mysqli_stmt_close($stmt_q_count);

                if ($quiz) :
                    
                    // --- Logique du Badge de Difficulté ---
                    $difficulty = $quiz['difficulty'];
                    $badge_class = 'bg-info text-dark'; 
                    switch ($difficulty) {
                        case 'FACILE': $badge_class = 'bg-success'; break;
                        case 'DIFFICILE': $badge_class = 'bg-warning text-dark'; break;
                        case 'EXPERT': $badge_class = 'bg-danger'; break;
                    }
                    
                    // --- NOUVELLE PARTIE : REQUÊTES POUR LE LEADERBOARD ---
                    
                    $user_id = ($logged == 'Yes') ? $rowu['id'] : 0;
                    
                    // 1. Score personnel (Utilisateur connecté)
                    $personal_best = null;
                    if ($logged == 'Yes') {
                        $stmt_perso = mysqli_prepare($connect, "SELECT score, time_seconds FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY score DESC, time_seconds ASC LIMIT 1");
                        mysqli_stmt_bind_param($stmt_perso, "ii", $quiz_id, $user_id);
                        mysqli_stmt_execute($stmt_perso);
                        $res_perso = mysqli_stmt_get_result($stmt_perso);
                        $personal_best = mysqli_fetch_assoc($res_perso);
                        mysqli_stmt_close($stmt_perso);
                    }

                    // 2. Moyenne globale
                    $stmt_avg = mysqli_prepare($connect, "SELECT AVG(score) AS avg_score, COUNT(DISTINCT user_id) AS total_players FROM quiz_attempts WHERE quiz_id = ?");
                    mysqli_stmt_bind_param($stmt_avg, "i", $quiz_id);
                    mysqli_stmt_execute($stmt_avg);
                    $res_avg = mysqli_stmt_get_result($stmt_avg);
                    $global_stats = mysqli_fetch_assoc($res_avg);
                    mysqli_stmt_close($stmt_avg);

                    // 3. Joueurs ce mois-ci
                    $stmt_month = mysqli_prepare($connect, "SELECT COUNT(id) AS monthly_count FROM quiz_attempts WHERE quiz_id = ? AND attempt_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
                    mysqli_stmt_bind_param($stmt_month, "i", $quiz_id);
                    mysqli_stmt_execute($stmt_month);
                    $res_month = mysqli_stmt_get_result($stmt_month);
                    $monthly_plays = mysqli_fetch_assoc($res_month)['monthly_count'];
                    mysqli_stmt_close($stmt_month);

                    // 4. Leaderboard (Top 9)
                    // Cette requête complexe trouve le meilleur score de chaque utilisateur pour ce quiz
                    $leaderboard = [];
                    $stmt_lead = mysqli_prepare($connect, "
                        SELECT u.username, t1.score, t1.time_seconds
                        FROM quiz_attempts t1
                        JOIN users u ON t1.user_id = u.id
                        WHERE t1.quiz_id = ?
                        AND t1.id = (
                            SELECT id
                            FROM quiz_attempts t2
                            WHERE t2.quiz_id = t1.quiz_id AND t2.user_id = t1.user_id
                            ORDER BY t2.score DESC, t2.time_seconds ASC, t2.id DESC
                            LIMIT 1
                        )
                        ORDER BY t1.score DESC, t1.time_seconds ASC
                        LIMIT 9
                    ");
                    mysqli_stmt_bind_param($stmt_lead, "i", $quiz_id);
                    mysqli_stmt_execute($stmt_lead);
                    $res_lead = mysqli_stmt_get_result($stmt_lead);
                    while($row = mysqli_fetch_assoc($res_lead)) {
                        $leaderboard[] = $row;
                    }
                    mysqli_stmt_close($stmt_lead);

            ?>
                    <div class="quiz-header mb-4">
                        <?php if (!empty($quiz['image'])) : ?>
                            <img src="<?php echo htmlspecialchars($quiz['image']); ?>" alt="<?php echo htmlspecialchars($quiz['title']); ?>" class="img-fluid rounded mb-3" style="width: 100%; max-height: 250px; object-fit: cover;">
                        <?php endif; ?>

                        <h2 class="fw-bold text-center"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                        
                        <div class="row border rounded p-3 my-3">
                            <div class="col-md-6 border-end">
                                <h6 class="text-uppercase text-muted small">Statistiques</h6>
                                <p class="mb-1">
                                    <span class="badge <?php echo $badge_class; ?> me-2"><?php echo $difficulty; ?></span>
                                    <span class="text-muted"><i class="fas fa-list-ol"></i> <?php echo $total_questions; ?> Questions</span>
                                </p>
                                <p class="mb-1">
                                    Moyenne sur <strong><?php echo (int)$global_stats['total_players']; ?></strong> joueurs : <strong><?php echo round((float)$global_stats['avg_score'], 1); ?>%</strong>
                                </p>
                                <p class="mb-0">
                                    <strong><?php echo (int)$monthly_plays; ?></strong> tentatives ce mois-ci.
                                </p>
                                
                                <?php if ($personal_best): ?>
                                <div class="alert alert-info mt-2 p-2">
                                    <i class="fas fa-user-check"></i> Votre meilleur score : 
                                    <strong><?php echo $personal_best['score']; ?>%</strong> en <strong><?php echo $personal_best['time_seconds']; ?>s</strong>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small">Leaderboard</h6>
                                <?php if (empty($leaderboard)): ?>
                                    <small class="text-muted">Personne n'a encore joué à ce quiz !</small>
                                <?php else: ?>
                                    <ol class="list-unstyled mb-0" style="font-size: 0.9em;">
                                        <?php $rank = 1; foreach ($leaderboard as $player): ?>
                                            <li>
                                                <strong><?php echo $rank++; ?>. <?php echo htmlspecialchars($player['username']); ?></strong> - 
                                                <?php echo $player['score']; ?>% (<?php echo $player['time_seconds']; ?>s)
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-muted">
                            <?php echo $purifier->purify($quiz['description']); ?>
                        </div>
                    </div>
                    <hr>
                    <?php 
                    // Afficher un message de succès si on vient de soumettre
                    if (isset($_GET['attempt'])) {
                        echo '<div class="alert alert-success text-center"><strong>Quiz terminé !</strong> Votre score a été enregistré.</div>';
                    }
                    ?>
                    
                    <?php if ($total_questions > 0 && $logged == 'Yes') : ?>
                    <form action="quiz.php?id=<?php echo $quiz_id; ?>" method="POST">
                        <input type="hidden" name="submit_quiz" value="1">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        <input type="hidden" name="start_time" value="<?php echo time(); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="quiz-container">
                            <?php
                            // Récupérer les questions
                            $stmt_q = mysqli_prepare($connect, "SELECT * FROM quiz_questions WHERE quiz_id = ? AND active='Yes' ORDER BY position_order ASC, id ASC");
                            mysqli_stmt_bind_param($stmt_q, "i", $quiz_id);
                            mysqli_stmt_execute($stmt_q);
                            $questions_result = mysqli_stmt_get_result($stmt_q);

                            $question_num = 1;
                            while ($question = mysqli_fetch_assoc($questions_result)) :
                                $question_id = $question['id'];
                            ?>
                                <div class="quiz-question mb-4" id="question-<?php echo $question_id; ?>">
                                    <h5 class="fw-bold">
                                        Question <?php echo $question_num++; ?> / <?php echo $total_questions; ?>
                                    </h5>
                                    <p class="fs-5"><?php echo htmlspecialchars($question['question']); ?></p>
                                    
                                    <div class="quiz-options list-group">
                                        <?php
                                        // Récupérer les options
                                        $options_query = mysqli_prepare($connect, "SELECT * FROM quiz_options WHERE question_id = ? ORDER BY id ASC");
                                        mysqli_stmt_bind_param($options_query, "i", $question_id);
                                        mysqli_stmt_execute($options_query);
                                        $options_result = mysqli_stmt_get_result($options_query);
                                        
                                        while ($option = mysqli_fetch_assoc($options_result)) :
                                        ?>
                                            <label for="opt-<?php echo $option['id']; ?>" class="list-group-item list-group-item-action" style="cursor: pointer;">
                                                <input type="radio" 
                                                       name="answers[<?php echo $question_id; ?>]" 
                                                       id="opt-<?php echo $option['id']; ?>" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       required 
                                                       class="form-check-input me-2">
                                                <?php echo htmlspecialchars($option['title']); ?>
                                            </label>
                                        <?php endwhile; ?>
                                        <?php mysqli_stmt_close($options_query); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <?php mysqli_stmt_close($stmt_q); ?>
                        </div>

                        <hr>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle"></i> Valider et voir mon score
                            </button>
                        </div>
                    </form>
                    <?php elseif ($logged == 'No'): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle"></i> Vous devez être <a href="login.php" class="alert-link">connecté</a> pour jouer à ce quiz et enregistrer votre score.
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="alert alert-warning">Ce quiz n'a pas été trouvé ou n'est plus actif.</div>
                    <a href="quiz.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour à la liste des quiz</a>
                <?php endif; ?>

            <?php
            // -----------------------------------------------------------------
            // SCÉNARIO 2 : Afficher la liste de tous les quiz (quiz.php)
            // -----------------------------------------------------------------
            else :
                
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
                        
                        $difficulty = $quiz['difficulty'];
                        $badge_class = 'bg-info text-dark';
                        switch ($difficulty) {
                            case 'FACILE': $badge_class = 'bg-success'; break;
                            case 'DIFFICILE': $badge_class = 'bg-warning text-dark'; break;
                            case 'EXPERT': $badge_class = 'bg-danger'; break;
                        }
                        
                        $quiz_url = "quiz.php?id=" . $quiz['id'];
                        $image_src = !empty($quiz['image']) ? htmlspecialchars($quiz['image']) : 'assets/img/no-image.png';
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <a href="<?php echo $quiz_url; ?>">
                                    <img src="<?php echo $image_src; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($quiz['title']); ?>" style="height: 150px; object-fit: cover;">
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