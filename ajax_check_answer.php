<?php
include "core.php"; // Inclut la session, la BDD et les fonctions

// --- Sécurité : valider le jeton CSRF ---
validate_csrf_token(); // Fonction de core.php

// Initialiser la réponse par défaut
$response = [
    'status' => 'error',
    'message' => 'Invalid request.'
];

if (isset($_POST['option_id']) && isset($_POST['question_id'])) {
    
    $option_id = (int)$_POST['option_id'];
    $question_id = (int)$_POST['question_id'];

    // 1. Vérifier si l'option sélectionnée est correcte
    $stmt_check = mysqli_prepare($connect, "SELECT is_correct FROM quiz_options WHERE id = ? AND question_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $option_id, $question_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $selected_option = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($selected_option) {
        
        // 2. Récupérer l'explication de la question
        $stmt_exp = mysqli_prepare($connect, "SELECT explanation FROM quiz_questions WHERE id = ?");
        mysqli_stmt_bind_param($stmt_exp, "i", $question_id);
        mysqli_stmt_execute($stmt_exp);
        $result_exp = mysqli_stmt_get_result($stmt_exp);
        $question_data = mysqli_fetch_assoc($result_exp);
        mysqli_stmt_close($stmt_exp);

        // Nettoyer l'explication pour l'affichage
        $purifier = get_purifier();
        $explanation_html = $purifier->purify($question_data['explanation']);
        if(empty(trim(strip_tags($explanation_html)))) {
            $explanation_html = ""; // Ne pas afficher si c'est vide
        }

        if ($selected_option['is_correct'] == 'Yes') {
            // --- Réponse correcte ---
            $response = [
                'status' => 'correct',
                'explanation' => '<strong>Bonne réponse !</strong><br>' . $explanation_html
            ];
            
        } else {
            // --- Réponse incorrecte ---
            
            // 3. Trouver quelle était la bonne réponse
            $correct_option_id = 0;
            $stmt_correct = mysqli_prepare($connect, "SELECT id FROM quiz_options WHERE question_id = ? AND is_correct = 'Yes' LIMIT 1");
            mysqli_stmt_bind_param($stmt_correct, "i", $question_id);
            mysqli_stmt_execute($stmt_correct);
            $result_correct = mysqli_stmt_get_result($stmt_correct);
            if ($row_correct = mysqli_fetch_assoc($result_correct)) {
                $correct_option_id = $row_correct['id'];
            }
            mysqli_stmt_close($stmt_correct);

            $response = [
                'status' => 'wrong',
                'correct_option_id' => $correct_option_id,
                'explanation' => '<strong>Mauvaise réponse.</strong><br>' . $explanation_html
            ];
        }

    } else {
        $response['message'] = 'Option non trouvée.';
    }
}

// Envoyer la réponse JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>