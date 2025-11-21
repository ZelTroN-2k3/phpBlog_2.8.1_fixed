<?php
// Inclure l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// --- Variable de version ---
// $phpblog_version = "x.x.x"; // Définie dans config.php

// ------------------------------------------------------------
// --- MODIFICATION : Correction du chemin ---
// ------------------------------------------------------------

// Rediriger vers l'installation si le fichier de configuration est manquant
// Vérifier si le fichier de configuration existe
$configfile = __DIR__ . '/config.php'; // Utilise le chemin absolu du dossier de core.php
if (!file_exists($configfile)) {
    // MODIFICATION : Correction de l'URL de redirection (ajout de ../)
    echo '<meta http-equiv="refresh" content="0; url=install/index.php" />';
    exit();
}
// --- FIN MODIFICATION ---

// Set longer maxlifetime of the session (7 days)
@ini_set( "session.gc_maxlifetime", '604800');

// Set longer cookie lifetime of the session (7 days)
@ini_set( "session.cookie_lifetime", '604800');

session_start();

// ------------------------------------------------------------
// --- Protection CSRF ---
// ------------------------------------------------------------

// Générer un jeton CSRF unique s'il n'existe pas déjà dans la session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// --- FIN Protection CSRF ---

include "config.php";

// ------------------------------------------------------------
// --- SYSTÈME DE BANNISSEMENT (VÉRIFICATION & DESIGN) ---
// ------------------------------------------------------------

// Récupération de l'image de fond
$ban_bg_file = 'default.jpg'; 

// On lit la colonne spécifique 'ban_bg_image' de la table settings
$q_bg_setting = mysqli_query($connect, "SELECT ban_bg_image FROM settings WHERE id = 1 LIMIT 1");
if ($q_bg_setting && mysqli_num_rows($q_bg_setting) > 0) {
    $row_bg = mysqli_fetch_assoc($q_bg_setting);
    if (!empty($row_bg['ban_bg_image'])) {
        $ban_bg_file = $row_bg['ban_bg_image'];
    }
}

// Modèle HTML pour la page de bannissement (Design Pro)
// On utilise <<<HTML pour éviter les erreurs de guillemets avec le CSS
$ban_page_template = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            
            /* --- CORRECTION ARRIÈRE-PLAN --- */
            /* 1. L'image de fond gérée par PHP */
            background-image: url('uploads/banned_bg/{{BG_IMAGE}}');
            
            /* 2. Important : Couvrir tout l'écran sans déformer */
            background-size: cover;
            
            /* 3. Centrer l'image */
            background-position: center center;
            
            /* 4. CRUCIAL : Empêcher l'image de se répéter */
            background-repeat: no-repeat;
            
            /* 5. Fixer l'image pour qu'elle ne bouge pas si la page scrolle */
            background-attachment: fixed;
            
            /* 6. Couleur de fond de secours si l'image ne charge pas */
            background-color: #343a40; 
            /* --- FIN CORRECTION ARRIÈRE-PLAN --- */

            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ban-container {
            text-align: center;
            
            /* --- CORRECTION BOÎTE CENTRALE --- */
            /* Nous utilisons une couleur blanche OPAQUE (ou légèrement transparente ici 0.96) */
            /* Cela empêche le fond de se "répéter" à l'intérieur de la boîte */
            background: rgba(255, 255, 255, 0.96);
            
            /* Effet "verre dépoli" moderne (optionnel, marche sur les navigateurs récents) */
            backdrop-filter: blur(10px);
            /* --- FIN CORRECTION BOÎTE --- */
            
            padding: 40px;
            border-radius: 12px;
            /* Ombre plus marquée pour bien détacher la boîte du fond */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 90%;
            border-top: 6px solid #dc3545;
        }
        .icon-container {
            color: #dc3545;
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #343a40;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        p.subtitle {
            color: #6c757d;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .details-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 6px;
            text-align: left;
            font-size: 0.95em;
            color: #495057;
        }
        .details-box strong {
            color: #212529;
            display: inline-block;
            width: 100px;
        }
        .footer-note {
            margin-top: 25px;
            font-size: 0.8em;
            color: #000000ff;
            /* Petit fond sombre sous le texte du footer pour la lisibilité */
            background: rgba(87, 87, 87, 0.1);
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="ban-container">
        <div class="icon-container">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h1>{{TITLE}}</h1>
        <p class="subtitle">{{MESSAGE}}</p>
        
        <div class="details-box">
            <div><strong>{{LABEL}}:</strong> {{TARGET}}</div>
            <div style="margin-top: 10px;"><strong>Reason:</strong> {{REASON}}</div>
        </div>

        <div class="footer-note">
            Site Security System
        </div>
    </div>
</body>
</html>
HTML;

$visitor_ip = $_SERVER['REMOTE_ADDR'];
$visitor_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// 1. Vérifier l'IP
$stmt_ban_ip = mysqli_prepare($connect, "SELECT reason FROM bans WHERE ban_type='ip' AND ban_value=? AND active='Yes' LIMIT 1");
mysqli_stmt_bind_param($stmt_ban_ip, "s", $visitor_ip);
mysqli_stmt_execute($stmt_ban_ip);
$res_ban_ip = mysqli_stmt_get_result($stmt_ban_ip);

if ($ban_row = mysqli_fetch_assoc($res_ban_ip)) {
    // Remplacement des placeholders
    $page = str_replace(
        ['{{TITLE}}', '{{MESSAGE}}', '{{LABEL}}', '{{TARGET}}', '{{REASON}}', '{{BG_IMAGE}}'],
        ['Access Denied', 'Your IP address has been blocked.', 'IP Address', htmlspecialchars($visitor_ip), htmlspecialchars($ban_row['reason']), $ban_bg_file],
        $ban_page_template
    );
    die($page);
}
mysqli_stmt_close($stmt_ban_ip);

// 2. Vérifier User-Agent
if (!empty($visitor_ua)) {
    $q_ua_bans = mysqli_query($connect, "SELECT ban_value, reason FROM bans WHERE ban_type='user_agent' AND active='Yes'");
    while ($row_ua = mysqli_fetch_assoc($q_ua_bans)) {
        if (stripos($visitor_ua, $row_ua['ban_value']) !== false) {
            $page = str_replace(
                ['{{TITLE}}', '{{MESSAGE}}', '{{LABEL}}', '{{TARGET}}', '{{REASON}}', '{{BG_IMAGE}}'],
                ['Access Restricted', 'Automated traffic detected.', 'User Agent', 'Bot/Spam Signature', htmlspecialchars($row_ua['reason']), $ban_bg_file],
                $ban_page_template
            );
            die($page);
        }
    }
}

// 3. Vérifier Utilisateur
if (isset($_SESSION['sec-username'])) {
    $current_user_ban = $_SESSION['sec-username'];
    $stmt_ban_user = mysqli_prepare($connect, "SELECT reason FROM bans WHERE (ban_type='username' OR ban_type='email') AND ban_value=? AND active='Yes' LIMIT 1");
    mysqli_stmt_bind_param($stmt_ban_user, "s", $current_user_ban);
    mysqli_stmt_execute($stmt_ban_user);
    $res_ban_user = mysqli_stmt_get_result($stmt_ban_user);
    
    if ($ban_row = mysqli_fetch_assoc($res_ban_user)) {
         session_destroy();
         $page = str_replace(
            ['{{TITLE}}', '{{MESSAGE}}', '{{LABEL}}', '{{TARGET}}', '{{REASON}}', '{{BG_IMAGE}}'],
            ['Account Suspended', 'Your account has been suspended.', 'Account', htmlspecialchars($current_user_ban), htmlspecialchars($ban_row['reason']), $ban_bg_file],
            $ban_page_template
        );
        die($page);
    }
    mysqli_stmt_close($stmt_ban_user);
}
// --- FIN SYSTÈME DE BANNISSEMENT ---

// --- Charger les paramètres depuis la BDD (structure à 1 ligne) ---
$settings = array(); // Initialiser le tableau
$stmt_settings = mysqli_prepare($connect, "SELECT * FROM settings WHERE id = 1");

if ($stmt_settings) { // Vérifier si la préparation a réussi
    
    mysqli_stmt_execute($stmt_settings);
    $result_settings = mysqli_stmt_get_result($stmt_settings);

    if (!$result_settings) {
        die("Erreur critique : Impossible d'obtenir les résultats des paramètres.");
    }

    // On récupère la ligne unique de paramètres
    // Plus besoin de boucle while !
    $settings = mysqli_fetch_assoc($result_settings);
    
    mysqli_stmt_close($stmt_settings);

    if (!$settings) {
        // Cela arrive si la table est vide (l'installation a échoué)
         die("Erreur critique : La table des paramètres est vide.");
    }

} else {
    // La préparation de la requête a échoué
    die("Erreur critique : Impossible de préparer la requête des paramètres.");
}
// --- FIN DE LA MODIFICATION ---


// ------------------------------------------------------------
// --- VÉRIFICATION DU MODE MAINTENANCE ---
// ------------------------------------------------------------

if ($settings['maintenance_mode'] == 'On') {

    // 1. Vérifier si l'utilisateur est un admin
    $is_admin = false;
    if (isset($_SESSION['sec-username'])) {
        $uname = $_SESSION['sec-username'];
        $stmt_admin_check = mysqli_prepare($connect, "SELECT role FROM `users` WHERE username=? AND role='Admin'");
        mysqli_stmt_bind_param($stmt_admin_check, "s", $uname);
        mysqli_stmt_execute($stmt_admin_check);
        $result_admin_check = mysqli_stmt_get_result($stmt_admin_check);
        if (mysqli_num_rows($result_admin_check) > 0) {
            $is_admin = true;
        }
        mysqli_stmt_close($stmt_admin_check);
    }

    // 2. Vérifier si on est sur la page de login admin
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    $is_admin_login_page = ($current_script == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
    $is_admin_folder = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
    
    // --- NOUVELLE EXCEPTION ---
    $is_special_admin_login = ($current_script == 'admin.php');
    // --- FIN ---

    // 3. Si le mode est ON et que l'utilisateur N'EST PAS un admin
    //    ET qu'il n'essaie PAS d'accéder à l'admin...
    //    ET qu'il n'essaie PAS d'accéder au login spécial...
    if (!$is_admin && !$is_admin_folder && !$is_special_admin_login) {

// --- PAGE DE MAINTENANCE ---
        $purifier = get_purifier();
        $safe_message = $purifier->purify($settings['maintenance_message']);
        $page_title = htmlspecialchars($settings['maintenance_title']);
        $sitename = htmlspecialchars($settings['sitename']);
        
        // Gestion de l'image de fond
        $bg_style = 'background: #f4f6f9;'; // Fond gris par défaut
        if (!empty($settings['maintenance_image']) && file_exists($settings['maintenance_image'])) {
            $bg_url = htmlspecialchars($settings['maintenance_image']);
            $bg_style = "background: url('$bg_url') no-repeat center center fixed; background-size: cover;";
        }

        die('
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $page_title . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            ' . $bg_style . '
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        /* Ajout d\'un overlay sombre léger si image */
        .bg-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); /* Assombrit légèrement le fond */
            z-index: 0;
            ' . (empty($settings['maintenance_image']) ? 'display: none;' : '') . '
        }
        .maintenance-container {
            position: relative; /* Pour passer au-dessus de l\'overlay */
            z-index: 1;
            text-align: center;
            max-width: 600px;
            width: 90%;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95); /* Fond blanc légèrement transparent */
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(5px); /* Effet de flou moderne */
        }
        .maintenance-icon { font-size: 80px; color: #ffc107; margin-bottom: 20px; }
        .site-name { color: #6c757d; font-weight: 600; letter-spacing: 1px; margin-bottom: 30px; text-transform: uppercase; font-size: 0.9rem; }
        h1 { font-weight: 700; color: #343a40; margin-bottom: 20px; }
        .message { font-size: 1.1rem; color: #6c757d; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    
    <div class="maintenance-container">
        <div class="site-name">' . $sitename . '</div>
        <div class="maintenance-icon"><i class="fas fa-tools"></i></div>
        <h1>' . $page_title . '</h1>
        <div class="message">' . $safe_message . '</div>
        <div class="mt-4">
             <a href="admin.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-lock"></i> Admin Login</a>
        </div>
    </div>
</body>
</html>
        ');
        // --- FIN PAGE MAINTENANCE ---
    }
}
// --- FIN VÉRIFICATION MAINTENANCE ---


// ------------------------------------------------------------
// --- MODIFICATION MODE SOMBRE (DÉFINITION GLOBALE) ---
// ------------------------------------------------------------

// Définir les variables de thème ici pour les rendre globales
$light_theme_name = $settings['theme'];
$dark_theme_name = "Darkly"; // Vous pouvez changer ceci pour "Slate" ou "Superhero" si vous préférez

// Chemin vers le CSS de Bootstrap 5 standard (si "Bootstrap 5" est sélectionné)
$bootstrap_css = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
// Chemin vers le CSS de Bootswatch
$bootswatch_base_url = "https://bootswatch.com/5/";

// Déterminer l'URL du thème clair
$light_theme_url = ($light_theme_name == "Bootstrap 5") 
    ? $bootstrap_css 
    : $bootswatch_base_url . strtolower($light_theme_name) . "/bootstrap.min.css";

// Déterminer l'URL du thème sombre
$dark_theme_url = $bootswatch_base_url . strtolower($dark_theme_name) . "/bootstrap.min.css";
// --- FIN MODIFICATION MODE SOMBRE ---


// Data Sanitization
$_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
//$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

if (!isset($_SESSION['sec-username'])) {
    $logged = 'No';
} else {
    
    $username = $_SESSION['sec-username'];
    
    // Requête préparée pour la vérification de session
    $stmt_user_check = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? LIMIT 1");
    mysqli_stmt_bind_param($stmt_user_check, "s", $username);
    mysqli_stmt_execute($stmt_user_check);
    $querych = mysqli_stmt_get_result($stmt_user_check);
    
    if (mysqli_num_rows($querych) == 0) {
        $logged = 'No';
        // Détruire la session invalide
        unset($_SESSION['sec-username']);
    } else {
        $rowu   = mysqli_fetch_assoc($querych); // Utiliser fetch_assoc pour la cohérence
        $logged = 'Yes';
    }
    mysqli_stmt_close($stmt_user_check);
}

function short_text($text, $length)
{
    $maxTextLenght = $length;
    $aspace        = " ";
    if (strlen($text) > $maxTextLenght) {
        $text = substr(trim($text), 0, $maxTextLenght);
        $text = substr($text, 0, strlen($text) - strpos(strrev($text), $aspace));
        $text = $text . '...';
    }
    return $text;
}

function emoticons($text)
{
    // ... (votre fonction emoticons reste inchangée) ...
    $icons = array(
        ':)' => '🙂',
        ':-)' => '🙂',
        ':}' => '🙂',
        ':D' => '😀',
        ':d' => '😁',
        ':-D ' => '😂',
        ';D' => '😂',
        ';d' => '😂',
        ';)' => '😉',
        ';-)' => '😉',
        ':P' => '😛',
        ':-P' => '😛',
        ':-p' => '😛',
        ':p' => '😛',
        ':-b' => '😛',
        ':-Þ' => '😛',
        ':(' => '🙁',
        ';(' => '😓',
        ':\'(' => '😓',
        ':o' => '😮',
        ':O' => '😮',
        ':0' => '😮',
        ':-O' => '😮',
        ':|' => '😐',
        ':-|' => '😐',
        ' :/' => ' 😕',
        ':-/' => '😕',
        ':X' => '😷',
        ':x' => '😷',
        ':-X' => '😷',
        ':-x' => '😷',
        '8)' => '😎',
        '8-)' => '😎',
        'B-)' => '😎',
        ':3' => '😊',
        '^^' => '😊',
        '^_^' => '😊',
        '<3' => '😍',
        ':*' => '😘',
        'O:)' => '😇',
        '3:)' => '😈',
        'o.O' => '😵',
        'O_o' => '😵',
        'O_O' => '😵',
        'o_o' => '😵',
        '0_o' => '😵',
        'T_T' => '😵',
        '-_-' => '😑',
        '>:O' => '😆',
        '><' => '😆',
        '>:(' => '😣',
        ':v' => '🙃',
        '(y)' => '👍',
        ':poop:' => '💩',
        ':|]' => '🤖'
    );
    // --- CORRECTION ---
    // On remplace les codes (clés) par les emojis (valeurs)
    return str_replace(array_keys($icons), array_values($icons), $text);    
}

/*function generateSeoURL($string, $random_numbers = 1, $wordLimit = 8) { 
    $separator = '-'; 
     
    if($wordLimit != 0){
        $wordArr = explode(' ', $string); 
        $string = implode(' ', array_slice($wordArr, 0, $wordLimit)); 
    } 
 
    $quoteSeparator = preg_quote($separator, '#'); 
 
    $trans = array( 
        '&.+?;'                 => '', 
        '[^\w\d _-]'            => '', 
        '\s+'                   => $separator, 
        '('.$quoteSeparator.')+'=> $separator 
    ); 
 
    $string = strip_tags($string); 
    foreach ($trans as $key => $val){
        $string = preg_replace('#'.$key.'#iu', $val, $string); 
    } 
 
    $string = strtolower($string); 
	if ($random_numbers == 1) {
		$string = $string . '-' . rand(10000, 99999); 
	}
 
    return trim(trim($string, $separator)); 
}*/

function generateSeoURL($string, $random_numbers = 1, $wordLimit = 8) { 
    $separator = '-'; 
    
    // 1. Nettoyer le HTML
    $string = strip_tags($string);

    // 2. Remplacer les caractères accentués (Table de conversion manuelle pour être sûr)
    $unwanted_array = array(    
        'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' 
    );
    $string = strtr($string, $unwanted_array);

    // 3. Supprimer tout ce qui n'est pas lettre ou chiffre (Garde uniquement ASCII propre)
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    
    // 4. Gérer la limite de mots
    if($wordLimit != 0){
        $wordArr = explode(' ', $string); 
        $string = implode(' ', array_slice($wordArr, 0, $wordLimit)); 
    } 
 
    // 5. Remplacer les espaces par des tirets
    $string = preg_replace('/\s+/', $separator, $string);
    $string = strtolower(trim($string, $separator));
 
    // 6. Ajouter le nombre aléatoire
	if ($random_numbers == 1) {
		$string = $string . '-' . rand(10000, 99999); 
	}
 
    return $string; 
}

// Obtenir une instance unique de HTMLPurifier
/*function get_purifier() {
    static $purifier = null;
    if ($purifier === null) {
        // S'assurer que le chemin est correct par rapport à core.php
        // require_once __DIR__ . '/vendor/htmlpurifier/library/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        // Vous pouvez configurer le purifier ici si nécessaire
        // Par exemple : $config->set('HTML.Allowed', 'p,b,a[href],i');
        $purifier = new HTMLPurifier($config);
    }
    return $purifier;
}*/

function get_purifier() {
    static $purifier = null;
    if ($purifier === null) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', __DIR__ . '/cache');
        
        // --- MODIFICATIONS POUR IFRAME (YouTube/Vimeo) ---
        
        // 1. Activer le module SafeIframe (gère src, width, height, etc.)
        $config->set('HTML.SafeIframe', true);
        
        // 2. Définir la "liste blanche" des URL de confiance
        // Ceci autorise :
        // - http(s)://www.youtube.com/embed/
        // - http(s)://www.youtube-nocookie.com/embed/
        // - http(s)://player.vimeo.com/video/
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
        
        // 3. Mettre à jour la liste des éléments autorisés
        // On ajoute 'iframe' et tous ses attributs nécessaires
        // Liste blanche améliorée pour autoriser les classes (Bootstrap/FontAwesome) et les liens externes
        $config->set('HTML.Allowed', 
            'p[style|class],b,i[class|style],u,s,a[href|title|class|target|rel],ul[class],ol,li[class],br,img[src|alt|title|width|height|style|class],span[style|class],div[style|class],blockquote,pre,h1,h2,h3,h4,h5,h6,table[class],thead,tbody,tr,th,td' .
            ',iframe[src|width|height|frameborder|allow|allowfullscreen|title|referrerpolicy]'
        );
        
        // 4. Autoriser les images 'data:' (votre modification existante)
        $config->set('URI.AllowedSchemes', array(
            'http' => true, 
            'https' => true, 
            'mailto' => true, 
            'ftp' => true, 
            'data' => true
        ));
        
        // 5. Autoriser les propriétés CSS (votre modification existante)
        $config->set('CSS.AllowedProperties', 'width,height,text-decoration,color,background-color,font-weight,font-style,text-align');
        
        // --- FIN DES MODIFICATIONS ---
        
        $purifier = new HTMLPurifier($config);
    }
    return $purifier;
}

function format_comment_with_code($text)
{
    // 1. Protéger les blocs de code dans un tableau temporaire.
    $code_blocks = [];
    $i = 0;

    // 2. Remplacer [code=lang]...[/code] par un placeholder
    // (Ex: [code=php]...[/code])
    $text = preg_replace_callback(
        '/\[code=([a-zA-Z0-9_-]+)\](.*?)\[\/code\]/s',
        function ($matches) use (&$code_blocks, &$i) {
            $lang = htmlspecialchars($matches[1]); // Sécurise la classe de langue
            $code_content = htmlspecialchars($matches[2]); // Échappe le code à l'intérieur
            $placeholder = "---CODEBLOCK{$i}---";
            // Crée le bloc HTML final
            $code_blocks[$placeholder] = '<pre><code class="language-' . $lang . '">' . $code_content . '</code></pre>';
            $i++;
            return $placeholder;
        },
        $text
    );

    // 3. Remplacer [code]...[/code] (sans langue spécifiée) par un placeholder
    $text = preg_replace_callback(
        '/\[code\](.*?)\[\/code\]/s',
        function ($matches) use (&$code_blocks, &$i) {
            $code_content = htmlspecialchars($matches[1]); // Échappe le code
            $placeholder = "---CODEBLOCK{$i}---";
            $code_blocks[$placeholder] = '<pre><code>' . $code_content . '</code></pre>';
            $i++;
            return $placeholder;
        },
        $text
    );

    // 4. Maintenant que le code est protégé, on sécurise TOUT le reste.
    $text = htmlspecialchars($text); // Sécurise tout autre HTML (ex: <script>)
    $text = emoticons($text);      // Applique les émoticônes sur le texte normal
    $text = nl2br($text);           // Ajoute les sauts de ligne <br> au texte normal

    // 5. Ré-injecter les blocs de code (qui sont déjà formatés et sécurisés)
    if (!empty($code_blocks)) {
        $text = str_replace(array_keys($code_blocks), array_values($code_blocks), $text);
    }

    return $text;
}

// --- NOUVELLE FONCTION POUR LES COMMENTAIRES ---
function display_comments($post_id, $parent_id = 0, $level = 0) {
    global $connect, $settings, $logged, $rowu; // Rendre les variables globales accessibles

    // Ajuster la marge pour l'indentation
    // Limiter la profondeur pour éviter les abus (max 5 niveaux)
    $margin_left = ($level > 5) ? (5 * 30) : ($level * 30); // 30px par niveau

    $stmt_comments = mysqli_prepare($connect, "SELECT * FROM comments WHERE post_id=? AND parent_id = ? AND approved='Yes' ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt_comments, "ii", $post_id, $parent_id);
    mysqli_stmt_execute($stmt_comments);
    $q = mysqli_stmt_get_result($stmt_comments);
    
    while ($comment = mysqli_fetch_array($q)) {
        // --- Bloc d'affichage d'un commentaire (copié de post.php) ---
        $aauthor_id = $comment['user_id'];
        $aauthor_name = 'Guest';
        $comment_badge = ''; // Initialiser le badge
        
        if ($comment['guest'] == 'Yes') {
            $aavatar = 'assets/img/avatar.png';
            $arole   = '<span class="badge bg-secondary">Guest</span>';
            $aauthor_name = htmlspecialchars($comment['user_id']); // Nom de l'invité
        } else {
            // Utiliser une requête préparée pour obtenir les infos de l'utilisateur
            $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt_user, "i", $aauthor_id);
            mysqli_stmt_execute($stmt_user);
            $querych = mysqli_stmt_get_result($stmt_user);
            
            if (mysqli_num_rows($querych) > 0) {
                $rowch = mysqli_fetch_assoc($querych);
                $aavatar = $rowch['avatar'];
                $aauthor_name = $rowch['username'];
                if ($rowch['role'] == 'Admin') {
                    $arole = '<span class="badge bg-danger">Administrator</span>';
                } elseif ($rowch['role'] == 'Editor') {
                    $arole = '<span class="badge bg-warning">Editor</span>';
                } else {
                    $arole = '<span class="badge bg-info">User</span>';
                }
                
                // --- NOUVELLE LIGNE ---
                // Appeler la fonction de badge UNIQUEMENT pour les utilisateurs enregistrés
                $comment_badge = get_user_comment_badge($aauthor_id);
                // --- FIN NOUVELLE LIGNE ---
                
            }
            mysqli_stmt_close($stmt_user);
        }
        
        echo '
        <div class="comment-container" style="margin-left: ' . $margin_left . 'px;" id="comment-' . $comment['id'] . '">
            <div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
                <div class="mb-2 d-flex flex-start align-items-center">
                    <img class="rounded-circle shadow-1-strong mt-1 me-3"
                        src="' . htmlspecialchars($aavatar) . '" alt="' . htmlspecialchars($aauthor_name) . '" 
                        width="50" height="50" />
                    <div class="mt-1 mb-1">
                        <h6 class="fw-bold mt-1 mb-1">
                            <i class="fa fa-user"></i> ' . htmlspecialchars($aauthor_name) . ' ' . $arole . ' ' . $comment_badge . ' </h6>
                        <p class="small mb-0">
                            <i><i class="fas fa-calendar"></i> ' . date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])) . '</i>
                        </p>
                    </div>
                </div>
                <hr class="my-0" />
                <p class="mt-1 mb-1 pb-1">
                    ' . format_comment_with_code(html_entity_decode($comment['comment'])) . '
                </p>
                <hr class="my-0" />
                <div class="p-2">
                    <button class="btn btn-sm btn-link" onclick="replyToComment(' . $comment['id'] . ')">
                        <i class="fas fa-reply"></i> Answer
                    </button>
                ';
        
                // AJOUT : Bouton Modifier si l'utilisateur est l'auteur
                if ($logged == 'Yes' && $comment['guest'] == 'No' && $rowu['id'] == $comment['user_id']) {
                    echo '
                    <a href="edit-comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-link text-primary">
                        <i class="fas fa-edit"></i> To modify
                    </a>';
                }
                
                echo '
                </div>
            </div>
        ';
        // --- Fin du bloc d'affichage ---
        
        // Appel récursif pour afficher les enfants de ce commentaire
        display_comments($post_id, $comment['id'], $level + 1);
        
        echo '</div>'; // Fermer le comment-container
    }
    mysqli_stmt_close($stmt_comments);
}
// --- FIN NOUVELLE FONCTION ---

// --- NOUVELLE FONCTION POUR L'AJAX ---
function render_comment_html($comment_id, $margin_left = 0) {
    global $connect, $settings, $logged, $rowu;
    
    // 1. Récupérer le commentaire
    $stmt_comment = mysqli_prepare($connect, "SELECT * FROM comments WHERE id=? AND approved='Yes' LIMIT 1");
    mysqli_stmt_bind_param($stmt_comment, "i", $comment_id);
    mysqli_stmt_execute($stmt_comment);
    $q = mysqli_stmt_get_result($stmt_comment);
    $comment = mysqli_fetch_array($q);
    mysqli_stmt_close($stmt_comment);

    if (!$comment) {
        return ""; // Retourne une chaîne vide si le commentaire n'est pas trouvé
    }

    // 2. Récupérer les infos de l'auteur
    $aauthor_id = $comment['user_id'];
// 2. Récupérer les infos de l'auteur
    $aauthor_id = $comment['user_id'];
    $aauthor_name = 'Guest';
    $comment_badge = ''; // Initialiser le badge
    
    if ($comment['guest'] == 'Yes') {
        $aavatar = 'assets/img/avatar.png';
        $arole   = '<span class="badge bg-secondary">Guest</span>';
        $aauthor_name = htmlspecialchars($comment['user_id']); // Nom de l'invité
    } else {
        $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt_user, "i", $aauthor_id);
        mysqli_stmt_execute($stmt_user);
        $querych = mysqli_stmt_get_result($stmt_user);
        
        if (mysqli_num_rows($querych) > 0) {
            $rowch = mysqli_fetch_assoc($querych);
            $aavatar = $rowch['avatar'];
            $aauthor_name = $rowch['username'];
            if ($rowch['role'] == 'Admin') {
                $arole = '<span class="badge bg-danger">Administrator</span>';
            } elseif ($rowch['role'] == 'Editor') {
                $arole = '<span class="badge bg-warning">Editor</span>';
            } else {
                $arole = '<span class="badge bg-info">User</span>';
            }
            
            // --- NOUVELLE LIGNE ---
            // Appeler la fonction de badge UNIQUEMENT pour les utilisateurs enregistrés
            $comment_badge = get_user_comment_badge($aauthor_id);
            // --- FIN NOUVELLE LIGNE ---
        }
        mysqli_stmt_close($stmt_user);
    }
    
    // 3. Construire le HTML (on utilise ob_start pour "capturer" l'echo)
    ob_start();
    ?>
    <div class="comment-container" style="margin-left: <?php echo $margin_left; ?>px; opacity: 0; transition: opacity 0.5s ease;" id="comment-<?php echo $comment['id']; ?>">
        <div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
            <div class="mb-2 d-flex flex-start align-items-center">
                <img class="rounded-circle shadow-1-strong mt-1 me-3"
                    src="<?php echo htmlspecialchars($aavatar); ?>" alt="<?php echo htmlspecialchars($aauthor_name); ?>" 
                    width="50" height="50" />
                <div class="mt-1 mb-1">
                    <h6 class="fw-bold mt-1 mb-1">
                        <i class="fa fa-user"></i> <?php echo htmlspecialchars($aauthor_name); ?> <?php echo $arole; ?> <?php echo $comment_badge; ?> </h6>
                    <p class="small mb-0">
                        <i><i class="fas fa-calendar"></i> <?php echo date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])); ?></i>
                    </p>
                </div>
            </div>
            <hr class="my-0" />
            <p class="mt-1 mb-1 pb-1">
                <?php echo format_comment_with_code(html_entity_decode($comment['comment'])); ?>
            </p>
            <hr class="my-0" />
                <div class="p-2">
                    <button class="btn btn-sm btn-link" onclick="replyToComment(<?php echo $comment['id']; ?>)">
                        <i class="fas fa-reply"></i> Answer
                    </button>
                    <?php
                    // AJOUT : Bouton Modifier si l'utilisateur est l'auteur
                    if ($logged == 'Yes' && $comment['guest'] == 'No' && $rowu['id'] == $comment['user_id']) {
                        echo '
                        <a href="edit-comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-link text-primary">
                            <i class="fas fa-edit"></i> To modify
                        </a>';
                    }
                    ?>
                </div>
        </div>
        </div>
    
    <?php
    // 4. Retourner le HTML capturé
    return ob_get_clean();
}
// --- FIN NOUVELLE FONCTION AJAX ---

function post_author($author_id)
{
    // Rendre $connect accessible (meilleure pratique : passer $connect en paramètre)
    global $connect; 
    
    $author_name = '-';
    $author_username = ''; // Pour le lien
    
    $stmt = mysqli_prepare($connect, "SELECT username FROM `users` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $author_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowauthp = mysqli_fetch_assoc($result)) {
        $author_name   = $rowauthp['username'];
        $author_username = $rowauthp['username']; // Stocker le nom d'utilisateur brut pour l'URL
    }
    mysqli_stmt_close($stmt);
 
    // MODIFICATION : Retourner un lien HTML au lieu d'un simple texte
    if ($author_username) {
        return '<a href="author.php?username=' . urlencode($author_username) . '">' . htmlspecialchars($author_name) . '</a>';
    } else {
        return htmlspecialchars($author_name); // Retourner le nom simple si l'auteur n'est pas trouvé
    }
}

function post_title($post_id)
{
    global $connect;
    
    $title = '-';
    
    $stmt = mysqli_prepare($connect, "SELECT title FROM `posts` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowtitlep = mysqli_fetch_assoc($result)) {
        $title     = $rowtitlep['title'];
    }
    mysqli_stmt_close($stmt);
 
    return $title;
}

function post_category($category_id)
{
    global $connect;
    
    $category = '-';

    $stmt = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowcat = mysqli_fetch_assoc($result)) {
        $category = $rowcat['category'];
    }
    mysqli_stmt_close($stmt);
 
    return $category;
}

function post_slug($post_id)
{
    global $connect;
    
    $post_slug = '';

    $stmt = mysqli_prepare($connect, "SELECT slug FROM `posts` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowpost = mysqli_fetch_assoc($result)) {
        $post_slug = $rowpost['slug'];
    }
    mysqli_stmt_close($stmt);
 
    return $post_slug;
}

function post_categoryslug($category_id)
{
    global $connect;
    
    $category_slug = '';

    $stmt = mysqli_prepare($connect, "SELECT slug FROM `categories` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowcat = mysqli_fetch_assoc($result)) {
        $category_slug = $rowcat['slug'];
    }
    mysqli_stmt_close($stmt);
 
    return $category_slug;
}

function post_commentscount($post_id)
{
    global $connect;
    
    $comments_count = '0';

    $stmt = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $comments_count = $row['count'];
    mysqli_stmt_close($stmt);
 
    return $comments_count;
}

function get_post_like_count($post_id)
{
    global $connect;
    $count = 0;
    
    $stmt = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM `post_likes` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $count = $row['count'];
    }
    mysqli_stmt_close($stmt);
    return $count;
}

function check_user_has_liked($post_id)
{
    global $connect, $logged, $rowu;
    
    if ($logged == 'Yes') {
        $user_id = $rowu['id'];
        $stmt = mysqli_prepare($connect, "SELECT id FROM `post_likes` WHERE post_id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
    } else {
        $session_id = session_id();
        $stmt = mysqli_prepare($connect, "SELECT id FROM `post_likes` WHERE post_id=? AND session_id=?");
        mysqli_stmt_bind_param($stmt, "is", $post_id, $session_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $has_liked = (mysqli_num_rows($result) > 0);
    mysqli_stmt_close($stmt);
    
    return $has_liked;
}

// Récupère un badge HTML basé sur le nombre de commentaires d'un utilisateur.
function get_user_comment_badge($user_id)
{
    global $connect;
    
    // 1. Compter les commentaires approuvés de l'utilisateur
    $count = 0;
    $stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM comments WHERE user_id = ? AND guest = 'No' AND approved = 'Yes'");
    mysqli_stmt_bind_param($stmt_count, "i", $user_id);
    mysqli_stmt_execute($stmt_count);
    $result = mysqli_stmt_get_result($stmt_count);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $count = (int)$row['count'];
    }
    mysqli_stmt_close($stmt_count);

    // 2. Définir les seuils et les badges
    if ($count >= 50) {
        return '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-star"></i> Veteran</span>';
    } elseif ($count >= 20) {
        return '<span class="badge bg-success ms-1"><i class="fas fa-comments"></i> Loyal</span>';
    } elseif ($count >= 5) {
        return '<span class="badge bg-info ms-1"><i class="fas fa-comment-dots"></i> Active</span>';
    } elseif ($count >= 1) {
        return '<span class="badge bg-secondary ms-1"><i class="fas fa-comment"></i> Pipette</span>';
    }

    return ''; // Pas de badge si 0 commentaire
}


// Calcule le temps de lecture estimé pour un texte.
function get_reading_time($content, $wpm = 200) {
    // 1. Supprimer le HTML
    $text = strip_tags(html_entity_decode($content));
    
    // 2. Compter les mots
    $word_count = str_word_count($text);
    
    // 3. Calculer le temps
    $minutes = ceil($word_count / $wpm);
    
    // 4. Gérer le cas de "0 minute" (articles très courts)
    if ($minutes < 1) {
        $minutes = 1;
    }
    
    // 5. Retourner la chaîne formatée
    return '<i class="far fa-clock"></i> Read: ' . $minutes . ' min';
}

/**
 * Affiche un widget en fonction de son type.
 *
 * @param array $widget_row La ligne de la base de données pour le widget.
 */
function render_widget($widget_row) {
    global $connect, $settings, $purifier, $quiz_id;

    if (!isset($purifier)) {
        $purifier = get_purifier();
    }
    
    $position = $widget_row['position'];
    $type = $widget_row['widget_type'];
    $wrapper_open = false; // Sécurité pour savoir si on a ouvert une div

    // --- 1. OUVERTURE DU CONTENEUR ---
    // Cas Sidebar : On ouvre toujours une carte
    if ($position == 'Sidebar') {
        echo '
            <div class="card mb-3">
                  <div class="card-header">' . htmlspecialchars($widget_row['title']) . '</div>
                  <div class="card-body">';
        $wrapper_open = true;
    
    // Cas Header/Footer : On ouvre sauf pour certains types
    } else { 
        if ($type == 'latest_posts') {
             echo '<h5 class="mt-3">' . htmlspecialchars($widget_row['title']) . '</h5>';
        } elseif ($type != 'html') {
             echo '
                <div class="card mb-3">
                      <div class="card-header">' . htmlspecialchars($widget_row['title']) . '</div>
                      <div class="card-body">';
             $wrapper_open = true;
        }
    }

    // --- 2. CONTENU DU WIDGET ---
    switch ($type) {

        // CAS : HTML
        case 'html':
            echo $purifier->purify($widget_row['content']);
            break;

        // CAS : SLIDER TÉMOIGNAGES
        case 'testimonials':
            // (Votre code Témoignages reste ici, assurez-vous de l'avoir gardé si vous l'aviez ajouté)
            $query_sql = "SELECT * FROM testimonials WHERE active = 'Yes' ORDER BY RAND() LIMIT 5";
            $stmt_testi = mysqli_prepare($connect, $query_sql);
            if ($stmt_testi) {
                mysqli_stmt_execute($stmt_testi);
                $res_testi = mysqli_stmt_get_result($stmt_testi);
                if (mysqli_num_rows($res_testi) > 0) {
                    $carousel_id = 'carouselTestimonials_' . $widget_row['id'];
                    echo '<div id="' . $carousel_id . '" class="carousel slide testimonial-widget" data-bs-ride="carousel"><div class="carousel-inner">';
                    $first = true;
                    while ($t = mysqli_fetch_assoc($res_testi)) {
                        $active_class = $first ? 'active' : '';
                        $avatar = !empty($t['avatar']) ? $t['avatar'] : 'assets/img/avatar.png';
                        echo '<div class="carousel-item ' . $active_class . '"><div class="text-center px-3 py-2"><div class="testimonial-quote-icon"><i class="fas fa-quote-left"></i></div><p class="testimonial-text mb-4">' . htmlspecialchars(strip_tags($t['content'])) . '</p><div class="testimonial-author d-flex align-items-center justify-content-center"><img src="' . htmlspecialchars($avatar) . '" alt="User" class="testimonial-avatar shadow-sm"><div class="text-start ms-3"><h6 class="fw-bold mb-0 text-dark">' . htmlspecialchars($t['name']) . '</h6><small class="text-muted">' . htmlspecialchars($t['position']) . '</small></div></div></div></div>';
                        $first = false;
                    }
                    echo '</div><button class="carousel-control-prev" type="button" data-bs-target="#' . $carousel_id . '" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true" style="filter: invert(1);"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#' . $carousel_id . '" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true" style="filter: invert(1);"></span><span class="visually-hidden">Next</span></button></div>';
                } else {
                    echo '<p class="text-muted small text-center">Aucun témoignage.</p>';
                }
                mysqli_stmt_close($stmt_testi);
            }
            break;

        // CAS : ARTICLES RÉCENTS
        case 'latest_posts':
            $config = json_decode($widget_row['config_data'], true);
            $limit = isset($config['count']) ? (int)$config['count'] : 4;
            $q_posts = mysqli_query($connect, "SELECT id, title, slug, image, created_at FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT $limit");

            if (mysqli_num_rows($q_posts) == 0) {
                echo '<p>Aucun article à afficher.</p>';
            } else {
                if ($position == 'Sidebar') {
                    while ($post = mysqli_fetch_assoc($q_posts)) {
                        $image = ($post['image'] != "") ? '<img class="rounded shadow-1-strong me-1" src="' . htmlspecialchars($post['image']) . '" width="70" height="70" style="object-fit: cover;" />' : '<div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white" style="width:70px; height:70px;"><i class="fas fa-image"></i></div>';
                        echo '<div class="mb-2 d-flex flex-start align-items-center bg-light rounded"><a href="post?name=' . htmlspecialchars($post['slug']) . '" class="ms-1">' . $image . '</a><div class="mt-2 mb-2 ms-1 me-1" style="min-width: 0;"> <h6 class="text-primary mb-1 text-truncate"> <a href="post?name=' . htmlspecialchars($post['slug']) . '">' . htmlspecialchars($post['title']) . '</a></h6><p class="text-muted small mb-0"><i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($post['created_at'])) . '</p></div></div>';
                    }
                } else {
                    $col_class = ($limit == 3) ? 'col-md-4' : (($limit == 2) ? 'col-md-6' : 'col-md-3');
                    echo '<div class="row">';
                    while ($post = mysqli_fetch_assoc($q_posts)) {
                         $image = ($post['image'] != "") ? '<img src="' . htmlspecialchars($post['image']) . '" class="card-img-top" width="100%" height="150" style="object-fit: cover;"/>' : '<div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:150px;">No Image</div>';
                         echo '<div class="' . $col_class . ' mb-3"><div class="card shadow-sm h-100 d-flex flex-column"><a href="post?name=' . htmlspecialchars($post['slug']) . '">'. $image .'</a><div class="card-body d-flex flex-column flex-grow-1 p-3"><a href="post?name=' . htmlspecialchars($post['slug']) . '" class="text-decoration-none"><h6 class="card-title text-primary small">' . htmlspecialchars(short_text($post['title'], 50)) . '</h6></a><small class="text-muted d-block mt-auto"> <i class="far fa-calendar-alt"></i> ' . date($settings['date_format'], strtotime($post['created_at'])) . '</small></div></div></div>';
                    }
                    echo '</div>';
                }
            }
            break;
        
        // CAS : RECHERCHE
        case 'search':
            echo '<form action="search.php" method="GET"><div class="input-group"><input type="search" class="form-control" placeholder="Rechercher..." name="q" required /><button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button></div></form>';
            break;

        // CAS : QUIZ LEADERBOARD
        case 'quiz_leaderboard':
            if (isset($quiz_id) && $quiz_id > 0) {
                // --- PAGE QUIZ SPÉCIFIQUE ---
                $stmt_avg = mysqli_prepare($connect, "SELECT AVG(score) AS avg_score, COUNT(DISTINCT user_id) AS total_players FROM quiz_attempts WHERE quiz_id = ?");
                mysqli_stmt_bind_param($stmt_avg, "i", $quiz_id);
                mysqli_stmt_execute($stmt_avg);
                $res_avg = mysqli_stmt_get_result($stmt_avg);
                $global_stats = mysqli_fetch_assoc($res_avg);
                mysqli_stmt_close($stmt_avg);

                $stmt_month = mysqli_prepare($connect, "SELECT COUNT(id) AS monthly_count FROM quiz_attempts WHERE quiz_id = ? AND attempt_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
                mysqli_stmt_bind_param($stmt_month, "i", $quiz_id);
                mysqli_stmt_execute($stmt_month);
                $res_month = mysqli_stmt_get_result($stmt_month);
                $monthly_plays = mysqli_fetch_assoc($res_month)['monthly_count'];
                mysqli_stmt_close($stmt_month);

                // Leaderboard spécifique
                $leaderboard = [];
                $stmt_lead = mysqli_prepare($connect, "SELECT u.username, t1.score, t1.time_seconds FROM quiz_attempts t1 JOIN users u ON t1.user_id = u.id WHERE t1.quiz_id = ? AND t1.id = (SELECT id FROM quiz_attempts t2 WHERE t2.quiz_id = t1.quiz_id AND t2.user_id = t1.user_id ORDER BY t2.score DESC, t2.time_seconds ASC, t2.id DESC LIMIT 1) ORDER BY t1.score DESC, t1.time_seconds ASC LIMIT 9");
                mysqli_stmt_bind_param($stmt_lead, "i", $quiz_id);
                mysqli_stmt_execute($stmt_lead);
                $res_lead = mysqli_stmt_get_result($stmt_lead);
                while($row = mysqli_fetch_assoc($res_lead)) { $leaderboard[] = $row; }
                mysqli_stmt_close($stmt_lead);
                
                echo '<div style="font-size: 0.9em; line-height: 1.6;"><p class="mb-1">Moyenne sur <strong>' . (int)$global_stats['total_players'] . '</strong> joueurs : <strong>' . round((float)$global_stats['avg_score'], 1) . '%</strong></p><p class="mb-2"><strong>' . (int)$monthly_plays . '</strong> tentatives ce mois-ci.</p>';
                
                if (empty($leaderboard)) {
                    echo '<small class="text-muted">Personne n\'a encore joué à ce quiz !</small>';
                } else {
                    // Affichage PROPRE avec Flexbox (Correction chevauchement)
                    echo '<div class="list-group list-group-flush mt-2">';
                    $rank = 1;
                    foreach ($leaderboard as $player) {
                        $rank_color = 'text-muted';
                        if ($rank == 1) $rank_color = 'text-warning';
                        if ($rank == 2) $rank_color = 'text-secondary';
                        if ($rank == 3) $rank_color = 'text-danger';
            
                        echo '<div class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center border-0" style="background: transparent;">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span class="fw-bold ' . $rank_color . ' me-1" style="min-width: 15px;">' . $rank++ . '.</span>
                                    <span class="text-truncate fw-bold text-dark" title="' . htmlspecialchars($player['username']) . '">' . htmlspecialchars($player['username']) . '</span>
                                </div>
                                <div class="text-end ms-1" style="white-space: nowrap; font-size: 0.85em;">
                                    <span class="badge bg-primary">' . $player['score'] . '%</span>
                                    <small class="text-muted ms-1">(' . $player['time_seconds'] . 's)</small>
                                </div>
                              </div>';
                    }
                    echo '</div>';
                }
                echo '</div>';

            } else {
                // --- HALL OF FAME GLOBAL ---
                $stmt_global = mysqli_prepare($connect, "SELECT u.username, u.avatar, AVG(a.score) AS avg_score, COUNT(DISTINCT a.quiz_id) AS quizzes_played, (SELECT q.image FROM quizzes q JOIN quiz_attempts qa ON q.id = qa.quiz_id WHERE qa.user_id = u.id ORDER BY qa.attempt_date DESC LIMIT 1) AS last_quiz_image FROM quiz_attempts a JOIN users u ON a.user_id = u.id GROUP BY u.id, u.username, u.avatar ORDER BY avg_score DESC, quizzes_played DESC LIMIT 10");
                
                mysqli_stmt_execute($stmt_global);
                $result_global = mysqli_stmt_get_result($stmt_global);

                if (mysqli_num_rows($result_global) == 0) {
                    echo '<p class="text-muted small">Aucun joueur n\'a encore terminé de quiz.</p>';
                } else {
                    echo '<ol class="list-unstyled mb-0 quiz-leaderboard">';
                    $rank = 1;
                    while ($player = mysqli_fetch_assoc($result_global)) {
                        $last_quiz_img_html = !empty($player['last_quiz_image']) ? '<img src="' . htmlspecialchars($player['last_quiz_image']) . '" class="rounded" width="60" height="45" style="object-fit: cover;">' : '<span class="rounded bg-light d-inline-block d-flex align-items-center justify-content-center" style="width: 60px; height: 45px;"><i class="fas fa-image text-muted"></i></span>';

                        echo '<li class="d-flex align-items-center mb-2 pb-2 border-bottom"><span class="fw-bold me-2" style="width: 20px;">' . $rank++ . '.</span><img src="' . htmlspecialchars($player['avatar']) . '" class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;"><div class="flex-grow-1 me-2"><span class="fw-bold d-block" style="font-size: 0.9em;">' . htmlspecialchars($player['username']) . '<small class="text-muted"> (' . (int)$player['quizzes_played'] . ' quiz)</small></span><small class="text-muted">Score moyen: <span class="badge bg-primary">' . round($player['avg_score']) . '%</span></small></div>' . $last_quiz_img_html . '</li>';
                    }
                    echo '</ol>';
                }
                mysqli_stmt_close($stmt_global);
            }
            break;

        // CAS : FAQ LEADERBOARD
        case 'faq_leaderboard':
            $query_sql = "SELECT id, question FROM faqs WHERE active = 'Yes' ORDER BY position_order ASC LIMIT 10";
            $stmt_faq = mysqli_prepare($connect, $query_sql);
            if ($stmt_faq) {
                mysqli_stmt_execute($stmt_faq);
                $faqs = mysqli_stmt_get_result($stmt_faq);
                if (mysqli_num_rows($faqs) > 0) {
                    echo '<ul class="list-group list-group-flush faq-leaderboard">';
                    while ($faq = mysqli_fetch_assoc($faqs)) {
                        $faq_url = htmlspecialchars($settings['site_url']) . '/faq.php#faq-' . (int)$faq['id'];
                        echo '<li class="list-group-item px-0 py-1" style="font-size: 0.9em;"><a href="' . $faq_url . '" class="text-decoration-none d-block text-truncate"><i class="fas fa-question-circle fa-fw text-muted me-1"></i> ' . htmlspecialchars($faq['question']) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="text-muted small">Aucune question n\'est disponible.</p>';
                }
                mysqli_stmt_close($stmt_faq);
            }
            break;
            
    } // Fin switch

    // --- 3. FERMETURE DU CONTENEUR (SÉCURISÉE) ---
    if ($wrapper_open) {
         echo '
              </div>
        </div>
        ';
    }
}

// --- DÉBUT DE LA NOUVELLE FONCTION ---

/**
 * Optimise, redimensionne et sauvegarde une image téléversée.
 * Convertit l'image en JPEG pour une taille de fichier optimale.
 *
 * @param string $temp_file Chemin vers le fichier temporaire (ex: $_FILES['image']['tmp_name'])
 * @param string $output_file_base Chemin de destination de base (ex: '../uploads/posts/image_abc') SANS extension.
 * @param int $max_width Largeur maximale de l'image.
 * @param int $quality Qualité JPEG (1-100).
 * @return string|bool Le chemin complet du nouveau fichier .jpg si succès, sinon False.
 */
function optimize_and_save_image($temp_file, $output_file_base, $max_width = 1200, $quality = 85) {
    
    $image_info = @getimagesize($temp_file);
    if (!$image_info) {
        return false; // Ce n'est pas une image valide
    }
    
    $mime = $image_info['mime'];
    $original_width = $image_info[0];
    $original_height = $image_info[1];
    
    // Charger l'image en mémoire
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($temp_file);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($temp_file);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($temp_file);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($temp_file);
            break;
        default:
            return false; // Type de fichier non supporté
    }
    
    if ($image === false) {
        return false; // Échec du chargement de l'image
    }
    
    // Calculer les nouvelles dimensions
    $new_width = $original_width;
    $new_height = $original_height;
    
    if ($original_width > $max_width) {
        $ratio = $max_width / $original_width;
        $new_width = $max_width;
        $new_height = $original_height * $ratio;
    }
    
    // Créer une nouvelle image (canvas)
    $new_image = imagecreatetruecolor((int)$new_width, (int)$new_height);
    
    // Gérer la transparence (pour PNG/GIF/WEBP) en remplissant avec un fond blanc
    $white = imagecolorallocate($new_image, 255, 255, 255);
    imagefill($new_image, 0, 0, $white);
    
    // Redimensionner et copier l'ancienne image sur la nouvelle
    imagecopyresampled(
        $new_image, $image,
        0, 0, 0, 0,
        (int)$new_width, (int)$new_height,
        (int)$original_width, (int)$original_height
    );
    
    // Détruire l'image originale de la mémoire
    imagedestroy($image);
    
    // Définir le chemin de sortie final avec l'extension .jpg
    $final_output_file = $output_file_base . '.jpg';
    
    // Sauvegarder la nouvelle image en tant que JPEG
    if (!@imagejpeg($new_image, $final_output_file, $quality)) {
         imagedestroy($new_image);
         return false;
    }
    
    // Libérer la mémoire
    imagedestroy($new_image);
    
    // Retourner le nouveau nom de fichier (avec l'extension .jpg)
    return $final_output_file;
}
// --- FIN DE LA NOUVELLE FONCTION ---

// --- NOUVELLE FONCTION : Validation CSRF ---
/**
 * Valide le jeton CSRF soumis via POST.
 * Arrête l'exécution si le jeton est invalide ou manquant.
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Le token est manquant ou invalide
        die('Validation error (CSRF token mismatch). The session may have expired. Please reload the page and try again.');
    }
}
// --- FIN NOUVELLE FONCTION ---
// --- NOUVELLE FONCTION : Validation CSRF pour GET ---
/**
 * Valide le jeton CSRF soumis via GET.
 * Arrête l'exécution si le jeton est invalide ou manquant.
 */
function validate_csrf_token_get() {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Le token est manquant ou invalide
        die('Validation error (CSRF token mismatch). The session may have expired. Please reload the page and try again.');
    }
}
// --- FIN NOUVELLE FONCTION ---


function head()
{
    // Rendre $connect, $logged, $rowu, $settings accessibles
    // AJOUT DES VARIABLES GLOBALES DE THÈME
    global $connect, $logged, $rowu, $settings, $light_theme_url, $dark_theme_url;
    
    // --- DÉBUT DE LA LOGIQUE DE TITRE ET DESCRIPTION ---
    global $current_page, $pagetitle, $description;

    $display_title = '';
    $display_description = '';
    
    if ($current_page == 'index.php') {
        // Page d'accueil : utiliser le titre SEO global et la description globale
        $display_title = $settings['meta_title'];
        $display_description = $settings['description'];
    } else {
        // Autres pages : utiliser le titre et la description spécifiques à la page
        // S'assurer que les variables existent pour éviter les erreurs
        $display_title = (isset($pagetitle) ? $pagetitle : 'Page') . ' - ' . $settings['sitename'];
        $display_description = isset($description) ? $description : $settings['description'];
    }
    
    // Construction de l'URL canonique (BEAUCOUP mieux pour le SEO)
    $current_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // --- FIN DE LA LOGIQUE ---
?>
<!DOCTYPE html>
<html lang="en">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <script>
        (function() {
            // Ces variables sont maintenant globales et seront correctement "echo"
            const lightThemeUrl = '<?php echo $light_theme_url; ?>';
            const darkThemeUrl = '<?php echo $dark_theme_url; ?>';
            let currentTheme = localStorage.getItem('theme');

            // Si aucune préférence n'est sauvegardée, vérifier la préférence du système
            if (!currentTheme) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    currentTheme = 'dark';
                } else {
                    currentTheme = 'light';
                }
            }

            // Appliquer le thème en écrivant la balise <link> appropriée
            const themeUrl = (currentTheme === 'dark') ? darkThemeUrl : lightThemeUrl;
            document.write('<link id="theme-link" rel="stylesheet" href="' + themeUrl + '">');
            
            // Sauvegarder le choix (surtout si c'était la détection auto)
            localStorage.setItem('theme', currentTheme);
        })();
    </script>
    <?php
	$current_page = basename($_SERVER['SCRIPT_NAME']);
    $pagetitle   = '';
    $description = '';

    // SEO Titles, Descriptions and Sharing Tags
    if ($current_page == 'contact.php') {
        $pagetitle   = 'Contact';
		$description = 'If you have any questions do not hestitate to send us a message.';
		
    } else if ($current_page == 'gallery.php') {
        $pagetitle   = 'Gallery';
		$description = 'View all images from the Gallery.';
		
    } else if ($current_page == 'blog.php') {
        $pagetitle   = 'Blog';
		$description = 'View all blog posts.';
        
    } else if ($current_page == 'profile.php') {
        $pagetitle   = 'Profile';
		$description = 'Manage your account settings.';
		
    } else if ($current_page == 'my-comments.php') {
        $pagetitle   = 'My Comments';
		$description = 'Manage your comments.';
		
    } else if ($current_page == 'my-favorites.php') {
        $pagetitle   = 'My Favorites';
		$description = 'Manage your favorite posts.';
		
    } else if ($current_page == 'author.php') {
        $pagetitle   = 'Author Profile';
		$description = 'View author profile.';
		
    } else if ($current_page == 'edit-comment.php') {
        $pagetitle   = 'Edit Comment';
		$description = 'Edit your comment.';
		
    } else if ($current_page == 'login.php') {
        $pagetitle   = 'Sign In';
		$description = 'Login into your account.';
		
    } else if ($current_page == 'unsubscribe.php') {
        $pagetitle   = 'Unsubscribe';
		$description = 'Unsubscribe from Newsletter.';
		
    } else if ($current_page == 'error404.php') {
        $pagetitle   = 'Error 404';
		$description = 'Page is not found.';
		
    } else if ($current_page == 'search.php') {
		
		if (!isset($_GET['q'])) {
			echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
		}
		
		$word        = $_GET['q']; // Déjà filtré par FILTER_SANITIZE_SPECIAL_CHARS au début
        $pagetitle   = 'Search';
		$description = 'Search results for ' . $word . '.';
		
    } else if ($current_page == 'post.php') {
        $slug = $_GET['name'] ?? ''; // Utiliser l'opérateur Null Coalescing
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_post_seo = mysqli_prepare($connect, "SELECT title, slug, image, content FROM `posts` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_post_seo, "s", $slug);
        mysqli_stmt_execute($stmt_post_seo);
        $runpt = mysqli_stmt_get_result($stmt_post_seo);
        
        if (mysqli_num_rows($runpt) == 0) {
            mysqli_stmt_close($stmt_post_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowpt = mysqli_fetch_assoc($runpt);
        mysqli_stmt_close($stmt_post_seo);
        
        $pagetitle   = $rowpt['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpt['content'])), 150);
		
        // Utiliser htmlspecialchars pour la sécurité dans les balises meta
		echo '
		<meta property="og:title" content="' . htmlspecialchars($rowpt['title']) . '" />
		<meta property="og:description" content="' . htmlspecialchars(short_text(strip_tags(html_entity_decode($rowpt['content'])), 150)) . '" />
		<meta property="og:image" content="' . htmlspecialchars($rowpt['image']) . '" />
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="' . htmlspecialchars($settings['site_url'] . '/post?name=' . $rowpt['slug']) . '" />
		<meta name="twitter:card" content="summary_large_image"></meta>
		<meta name="twitter:title" content="' . htmlspecialchars($rowpt['title']) . '" />
		<meta name="twitter:description" content="' . htmlspecialchars(short_text(strip_tags(html_entity_decode($rowpt['content'])), 150)) . '" />
		<meta name="twitter:image" content="' . htmlspecialchars($rowpt['image']) . '" />
		<meta name="twitter:url" content="' . htmlspecialchars($settings['site_url'] . '/post?name=' . $rowpt['slug']) . '" />
		';
		
    } else if ($current_page == 'page.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        
        // Requête préparée
        $stmt_page_seo = mysqli_prepare($connect, "SELECT title, content FROM `pages` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_page_seo, "s", $slug);
        mysqli_stmt_execute($stmt_page_seo);
        $runpp = mysqli_stmt_get_result($stmt_page_seo);
        
        if (mysqli_num_rows($runpp) == 0) {
            mysqli_stmt_close($stmt_page_seo);
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        $rowpp = mysqli_fetch_assoc($runpp);
        mysqli_stmt_close($stmt_page_seo);
        
        $pagetitle   = $rowpp['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpp['content'])), 150);
		
    } else if ($current_page == 'category.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_cat_seo = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_cat_seo, "s", $slug);
        mysqli_stmt_execute($stmt_cat_seo);
        $runct = mysqli_stmt_get_result($stmt_cat_seo);
        
        if (mysqli_num_rows($runct) == 0) {
            mysqli_stmt_close($stmt_cat_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowct = mysqli_fetch_assoc($runct);
        mysqli_stmt_close($stmt_cat_seo);
        
        $pagetitle   = $rowct['category'];
		$description = 'View all blog posts from ' . $rowct['category'] . ' category.';
    
    } else if ($current_page == 'tag.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_tag_seo = mysqli_prepare($connect, "SELECT name FROM `tags` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_tag_seo, "s", $slug);
        mysqli_stmt_execute($stmt_tag_seo);
        $runtag = mysqli_stmt_get_result($stmt_tag_seo);
        
        if (mysqli_num_rows($runtag) == 0) {
            mysqli_stmt_close($stmt_tag_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowtag = mysqli_fetch_assoc($runtag);
        mysqli_stmt_close($stmt_tag_seo);
        
        $pagetitle   = 'Articles tagged: ' . $rowtag['name'];
		$description = 'See all articles tagged ' . $rowtag['name'];
    }
    
    // Utiliser htmlspecialchars pour le titre et la description
    if ($current_page == 'index.php') {
        echo '
		<title>' . htmlspecialchars($settings['sitename']) . '</title>
		<meta name="description" content="' . htmlspecialchars($settings['description']) . '" />';
    } else {
        echo '
		<title>' . htmlspecialchars($pagetitle) . ' - ' . htmlspecialchars($settings['sitename']) . '</title>
		<meta name="description" content="' . htmlspecialchars($description) . '" />';
    }
?>

        <title><?php echo htmlspecialchars($display_title); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($display_description); ?>" />
        
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
        
        <meta property="og:title" content="<?php echo htmlspecialchars($display_title); ?>" />
        <meta property="og:description" content="<?php echo htmlspecialchars($display_description); ?>" />
        <meta property="og:site_name" content="<?php echo htmlspecialchars($settings['sitename']); ?>" />
        <meta property="og:type" content="website" />
        
        <meta property="og:url" content="<?php echo htmlspecialchars($current_page_url); ?>" />
        <link rel="canonical" href="<?php echo htmlspecialchars($current_page_url); ?>" />

        <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($settings['favicon_url']); ?>" />
        <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($settings['apple_touch_icon_url']); ?>" />

        <meta name="author" content="<?php echo htmlspecialchars($settings['meta_author']); ?>" />
        <meta name="generator" content="<?php echo htmlspecialchars($settings['meta_generator']); ?>" />
        <meta name="robots" content="<?php echo htmlspecialchars($settings['meta_robots']); ?>" />
        
        <link rel="stylesheet" id="theme-light" href="<?php echo htmlspecialchars($light_theme_url); ?>">
        <link rel="stylesheet" id="theme-dark" href="<?php echo htmlspecialchars($dark_theme_url); ?>" disabled>
        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo htmlspecialchars($settings['site_url']); ?>/assets/css/phpblog.css?v=<?php echo time(); ?>">


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" type="text/css" rel="stylesheet"/>
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
		<link href="assets/css/phpblog.css" rel="stylesheet">
		<script src="assets/js/phpblog.js"></script>
<?php
if ($current_page == 'post.php') {
?>
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.css" />
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials-theme-classic.css" />
        <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.min.js"></script>
<?php
}
?>
<?php
if ($current_page == 'post.php' || $current_page == 'tag.php') { // MODIF : Ajout de tag.php
?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/css.min.js"></script>
        <script>
            // Initialise la coloration après le chargement de la page
            document.addEventListener('DOMContentLoaded', (event) => {
                hljs.highlightAll();
            });
        </script>
        <?php
}
?>
	
        <style>
<?php
if($settings['background_image'] != "") {
    // Échapper l'URL pour la sécurité dans le CSS
    echo 'body {
        background: url("' . htmlspecialchars($settings['background_image']) . '") no-repeat center center fixed;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
    }';
}
?>
/* --- CSS MEGA MENU RESPONSIVE --- */

/* 1. Par défaut (Mobile) : Le menu prend 100% de la largeur et s'empile */
.mega-menu-custom {
    width: 100%;
    border: none;
    box-shadow: none;
    margin-top: 0;
    padding: 0;
}

/* 2. Sur PC (Écrans > 992px) : On applique le style "Mega Menu Centré" */
@media (min-width: 992px) {
    .nav-item.dropdown {
        position: relative; /* Le parent redevient la référence */
    }
    
    .mega-menu-custom {
        position: absolute;
        min-width: 900px; /* Largeur fixe pour PC */
        left: 50%;
        transform: translateX(-30%); /* Centrage parfait */
        border-top: 3px solid #007bff;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); /* Ombre uniquement sur PC */
        border-radius: 0.25rem;
        padding: 1rem 0;
    }
}
        </style>
        
<?php
    // Code personnalisé de l'admin (avec vérification On/Off)
    if ($settings['head_customcode_enabled'] == 'On' && !empty($settings['head_customcode'])) {
        echo base64_decode($settings['head_customcode']);
    }
?>
</head>

<body <?php 
if ($settings['rtl'] == "Yes") {
	echo 'dir="rtl"';
}
?>>

<?php
if ($logged == 'Yes' && ($rowu['role'] == 'Admin' || $rowu['role'] == 'Editor')) {
?>
	<div class="nav-scroller bg-dark shadow-sm">
		<nav class="nav" aria-label="Secondary navigation">
<?php
if ($rowu['role'] == 'Admin') {
?>
			<a class="nav-link text-white" href="admin/dashboard.php">ADMIN MENU</a>
<?php
} else {
?>
			<a class="nav-link text-white" href="admin/dashboard.php">EDITOR MENU</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="admin/dashboard.php">
				<i class="fas fa-columns"></i> Dashboard
			</a>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="fas fa-tasks"></i> Manage
			</a>
				<ul class="dropdown-menu bg-dark">
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/settings.php">
							Site Settings
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/menu_editor.php">
							Menu
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/widgets.php">
							Widgets
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/users.php">
							Users
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/newsletter.php">
							Newsletter
						</a>
					</li>
<?php
}
?>
					<li>
						<a class="dropdown-item text-white" href="admin/files.php">
							Files
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/posts.php">
							Posts
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/gallery.php">
							Gallery
						</a>
					</li>
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/pages.php">
							Pages
						</a>
					</li>
<?php
}
?>
				</ul>
<?php
if ($rowu['role'] == 'Admin') {
    // Requête simple sans variable externe, pas besoin de préparer
	$msgcount_query  = mysqli_query($connect, "SELECT id FROM messages WHERE viewed = 'No'");
	$unread_messages = mysqli_num_rows($msgcount_query);
?>
			
			<a class="nav-link text-secondary" href="admin/messages.php">
				<i class="fas fa-envelope"></i> Messages
				<span class="badge text-bg-light rounded-pill align-text-bottom"><?php
	echo $unread_messages; 
?> </span>
			</a>
			<a class="nav-link text-secondary" href="admin/comments.php">
				<i class="fas fa-comments"></i> Comments
			</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="far fa-plus-square"></i> New
			</a>
				<ul class="dropdown-menu bg-dark">
					<li>
						<a class="dropdown-item text-white" href="admin/add_post.php">
							Add Post
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/add_image.php">
							Add Image
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/upload_file.php">
							Upload File
						</a>
					</li>
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/add_page.php">
							Add Page
						</a>
					</li>
<?php
}
?>
				</ul>
<?php
// Logique pour définir l'icône et le texte
$maintenance_status = $settings['maintenance_mode'] ?? 'Off';
$status_icon = '';
$status_text = '';

if ($maintenance_status == 'On') {
    $status_icon = 'fas fa-circle text-danger'; // Icône Font Awesome (rouge)
    $status_text = 'Maintenance ON';
} else {
    $status_icon = 'fas fa-circle text-success'; // Icône Font Awesome (verte)
    $status_text = 'Maintenance OFF';
}
?>
                <li class="nav-item">
                    <a class="nav-link text-secondary" href="admin/maintenance.php" title="Manage maintenance mode">
                        <i class="<?php echo $status_icon; ?> me-1" style="font-size: 0.8em;"></i>
                        <span><?php echo $status_text; ?></span>
                    </a>
                </li>                
		</nav>
	</div>
<?php
}
?>
	
	<header class="py-3 border-bottom bg-primary">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> d-flex flex-wrap justify-content-center">
            <a href="<?php echo htmlspecialchars($settings['site_url']); ?>" class="d-flex align-items-center text-white mb-3 mb-md-0 me-md-auto text-decoration-none">
                <?php if (!empty($settings['site_logo']) && file_exists($settings['site_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="<?php echo htmlspecialchars($settings['sitename']); ?>" height="44" style="max-width: 200px; object-fit: contain;">
                <?php else: ?>
                    <span class="fs-4"><b><i class="far fa-newspaper"></i> <?php echo htmlspecialchars($settings['sitename']); ?></b></span>
                <?php endif; ?>
            </a>
			
			<form class="col-12 col-lg-auto mb-3 mb-lg-0" action="search" method="GET">
				<div class="input-group">
					<input type="search" class="form-control" placeholder="Search" name="q" value="<?php
if (isset($_GET['q'])) {
    // Utiliser htmlspecialchars pour la valeur de l'input
	echo htmlspecialchars($_GET['q']);
}
?>" required />
					<span class="input-group-btn">
						<button class="btn btn-dark" type="submit"><i class="fa fa-search"></i></button>
					</span>
				</div>
			</form>
		</div>
	</header>
	
	<nav class="navbar nav-underline navbar-expand-lg py-2 bg-light <?php echo ($settings['sticky_header'] == 'On' ? 'sticky-top shadow-sm' : 'border-bottom'); ?>">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?>">
			<button class="navbar-toggler mx-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span> Navigation
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav me-auto">
<?php
    // Requête simple sans variable externe
	$runq = mysqli_query($connect, "SELECT * FROM `menu` WHERE active = 'Yes' ORDER BY id ASC"); // Supposant que l'ordre est géré par ID
    while ($row = mysqli_fetch_assoc($runq)) {

        if ($row['path'] == 'blog') {
			
            echo '	<li class="nav-item link-body-emphasis dropdown">
						<a href="blog" class="nav-link link-dark dropdown-toggle px-2';
            if ($current_page == 'blog.php' || $current_page == 'category.php' || $current_page == 'tag.php') {
                echo ' active';
            }
            // Utiliser htmlspecialchars pour les icônes et le texte
            echo '" data-bs-toggle="dropdown">
							<i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . ' 
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="blog">View all posts</a></li>
                            <li><a class="dropdown-item" href="categories">View all Categories</a></li>';
            
            // Requête simple sans variable externe
            $run2 = mysqli_query($connect, "SELECT * FROM `categories` ORDER BY category ASC");
            while ($row2 = mysqli_fetch_array($run2)) {
                // Utiliser htmlspecialchars
                echo '		<li><a class="dropdown-item" href="category?name=' . htmlspecialchars($row2['slug']) . '"><i class="fas fa-chevron-right"></i> ' . htmlspecialchars($row2['category']) . '</a></li>';
            }
            echo '		</ul>
					</li>';
		
        } else {

			echo '	<li class="nav-item link-body-emphasis">
						<a href="' . htmlspecialchars($row['path']) . '" class="nav-link link-dark px-2';
            
            $current_slug = $_GET['name'] ?? '';
            if ($current_page == 'page.php'
				&& ($current_slug == ltrim(strstr($row['path'], '='), '='))
			) {
                echo ' active';
			
            } else if ($current_page != 'page.php' && $current_page == $row['path'] . '.php') {
                echo ' active';
            }
            // Utiliser htmlspecialchars
            echo '">
							<i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . '
						</a>
					</li>';
        }
    // ... fin de la boucle des menus standards ...
    }
?>
<?php
    // --- AFFICHAGE DES MEGA MENUS DYNAMIQUES (Table 'mega_menus') ---
    $mm_query = mysqli_query($connect, "SELECT * FROM mega_menus WHERE active='Yes' ORDER BY position_order ASC");
    
    while ($mm = mysqli_fetch_assoc($mm_query)) {
        
        // 1. Vérifier la visibilité des colonnes
        $show_col_2 = ($mm['col_2_type'] != 'none');
        $show_col_3 = ($mm['col_3_type'] != 'none');

        // 2. Calculer la largeur idéale du menu (PC uniquement)
        // Par défaut 900px. On réduit si on cache des colonnes.
        $custom_width = '900px'; 
        if (!$show_col_2 && !$show_col_3) {
            $custom_width = '250px'; // Une seule colonne (Explore)
        } elseif (!$show_col_2 || !$show_col_3) {
            $custom_width = '600px'; // Deux colonnes
        }

        echo '<li class="nav-item dropdown">
                <a href="' . htmlspecialchars($mm['trigger_link']) . '" class="nav-link dropdown-toggle px-2" data-bs-toggle="dropdown">
                    <i class="fa ' . htmlspecialchars($mm['trigger_icon']) . '"></i> ' . htmlspecialchars($mm['trigger_text']) . ' 
                </a>
                
                <div class="dropdown-menu mega-menu-custom bg-white" style="min-width: ' . $custom_width . ';">
                    <div class="px-4 py-3">
                        <div class="row g-4">
                            
                            <div class="col-12 col-lg-2 border-end-lg">
                                <h6 class="text-uppercase fw-bold text-primary mb-3 pt-2" style="font-size: 0.85rem;">
                                    ' . htmlspecialchars($mm['col_1_title']) . '
                                </h6>
                                <div class="text-small">
                                    ' . $mm['col_1_content'] . ' 
                                </div>
                            </div>';

                            // --- COLONNE 2 (Conditionnelle) ---
                            if ($show_col_2) {
                                echo '<div class="col-12 col-lg-4 border-end-lg">
                                        <h6 class="text-uppercase fw-bold text-secondary mb-3 pt-2" style="font-size: 0.85rem;">
                                            ' . htmlspecialchars($mm['col_2_title']) . '
                                        </h6>
                                        <div class="row">';
                                
                                if ($mm['col_2_type'] == 'categories') {
                                    $run_cats = mysqli_query($connect, "SELECT * FROM `categories` ORDER BY category ASC");
                                    while ($rc = mysqli_fetch_assoc($run_cats)) {
                                        echo '<div class="col-6 mb-1">
                                                <a class="dropdown-item rounded px-2 py-1 small text-truncate" href="category?name=' . htmlspecialchars($rc['slug']) . '">
                                                    <i class="fas fa-angle-right text-muted me-1"></i> ' . htmlspecialchars($rc['category']) . '
                                                </a>
                                              </div>';
                                    }
                                } elseif ($mm['col_2_type'] == 'custom') {
                                    echo '<div class="col-12">' . $mm['col_2_content'] . '</div>';
                                }
                                
                                echo '  </div>
                                    </div>';
                            }

                            // --- COLONNE 3 (Conditionnelle) ---
                            if ($show_col_3) {
                                echo '<div class="col-12 col-lg-6">
                                        <h6 class="text-uppercase fw-bold text-success mb-3 pt-2" style="font-size: 0.85rem;">
                                            ' . htmlspecialchars($mm['col_3_title']) . '
                                        </h6>
                                        <div class="row g-3">';
                                
                                if ($mm['col_3_type'] == 'latest_posts') {
                                    $recent_q = mysqli_query($connect, "SELECT title, slug, image, created_at FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT 4");
                                    if(mysqli_num_rows($recent_q) > 0){
                                        while($post = mysqli_fetch_assoc($recent_q)){
                                            $img_src = $post['image'] != '' ? htmlspecialchars($post['image']) : 'assets/img/no-image.png';
                                            if($post['image'] == '') {
                                                 $img_display = '<div class="bg-light d-flex align-items-center justify-content-center text-muted small" style="height: 60px; width: 80px; border-radius: 4px;"><i class="fas fa-image"></i></div>';
                                            } else {
                                                 $img_display = '<img src="' . $img_src . '" class="img-fluid rounded" style="height: 60px; width: 80px; object-fit: cover;" alt="Post">';
                                            }
                                            echo '
                                            <div class="col-12 col-md-6">
                                                <a href="post?name=' . htmlspecialchars($post['slug']) . '" class="text-decoration-none link-dark d-flex align-items-center p-1 rounded hover-bg-light">
                                                    <div class="flex-shrink-0 me-2">' . $img_display . '</div>
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <h6 class="mb-0 small fw-bold text-truncate" style="line-height: 1.4;">' . htmlspecialchars($post['title']) . '</h6>
                                                        <small class="text-muted" style="font-size: 0.75rem;">' . date('M d, Y', strtotime($post['created_at'])) . '</small>
                                                    </div>
                                                </a>
                                            </div>';
                                        }
                                    } else { echo '<div class="col-12 text-muted">No posts.</div>'; }
                                } elseif ($mm['col_3_type'] == 'custom') {
                                    echo '<div class="col-12">' . $mm['col_3_content'] . '</div>';
                                }

                                echo '  </div>
                                    </div>';
                            }

        echo '          </div> </div>
                </div>
              </li>';
    }
?>  
				</ul>

           
                <ul class="navbar-nav ms-auto d-flex flex-row align-items-center">
                    <li class="nav-item me-2">
                        <button class="btn btn-link nav-link theme-switcher" id="theme-switcher-btn" type="button" aria-label="Toggle theme">
                            <i class="fas fa-moon" id="theme-icon-moon"></i>
                            <i class="fas fa-sun" id="theme-icon-sun" style="display: none;"></i>
                        </button>
                    </li>
                <?php
    if ($logged == 'No') {
?>
					<li class="nav-item">
						<a href="login" class="btn btn-primary px-2">
							<i class="fas fa-sign-in-alt"></i> Sign In &nbsp;|&nbsp; Register
						</a>
					</li>
<?php
    } else {
?>
					<li class="nav-item dropdown">
						<a href="#" class="nav-link link-dark dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
							<img src="<?php echo htmlspecialchars($rowu['avatar']); ?>" alt="Avatar" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 5px;">
							Profile <span class="caret"></span>
						</a>
<ul class="dropdown-menu">
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'profile.php') {
	echo ' active';
}
?>" href="profile">
									<i class="fas fa-cog"></i> Settings
								</a>
							</li>
                            <li>
								<a class="dropdown-item <?php
if ($current_page == 'my-posts.php') { 
	echo ' active';
}
?>" href="my-posts.php"> 
                                    <i class="fas fa-file-alt"></i> My submitted articles
								</a>
							</li>
                            <li>
								<a class="dropdown-item <?php
if ($current_page == 'submit_post.php') { 
	echo ' active';
}
?>" href="submit_post.php"> 
                                    <i class="fas fa-pen-square"></i> Submit an article
								</a>
							</li>
                            <li>
                                <a class="dropdown-item <?php 
if ($current_page == 'submit_testimonial.php'){ 
	echo ' active';
}
?>" href="submit_testimonial.php"> 
                                    <i class="fas fa-star"></i> Add Testimonial
                                </a>
                            </li>                            
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'my-favorites.php') { 
	echo ' active';
}
?>" href="my-favorites.php"> 
                                    <i class="fa fa-bookmark"></i> My favorites
								</a>
							</li>
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'my-comments.php') {
	echo ' active';
}
?>" href="my-comments">
									<i class="fa fa-comments"></i> My Comments
								</a>
							</li>
                            <li role="separator" class="divider"></li>
							<li>
								<a class="dropdown-item" href="logout">
									<i class="fas fa-sign-out-alt"></i> Logout
								</a>
							</li>
						</ul>
					</li>
<?php
    }
?>
				</ul>
			</div>
		</div>
	</nav>
    
<?php
if ($settings['latestposts_bar'] == 'Enabled') {
?>
    <div class="latest-news-bar bg-white border-bottom shadow-sm" style="height: 50px; overflow: hidden;">
        <div class="<?php echo ($settings['layout'] == 'Wide') ? 'container-fluid' : 'container'; ?> h-100">
            <div class="row h-100 g-0">
                
                <div class="col-auto d-flex align-items-center bg-danger text-white px-3 position-relative" style="z-index: 10;">
                    <i class="fas fa-bolt me-2"></i> 
                    <span class="fw-bold text-uppercase" style="font-size: 0.9rem;">Latest</span>
                    <div style="position: absolute; right: -10px; top: 0; width: 0; height: 0; border-top: 50px solid #dc3545; border-right: 10px solid transparent;"></div>
                </div>

                <div class="col d-flex align-items-center overflow-hidden position-relative bg-light">
                    <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop();" onmouseout="this.start();" style="line-height: 50px;">
                        <?php
                        // Récupérer les 6 derniers articles
                        $run = mysqli_query($connect, "SELECT title, slug, image, created_at FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT 6");
                        
                        if (mysqli_num_rows($run) > 0) {
                            while ($row = mysqli_fetch_assoc($run)) {
                                // Gestion de l'image
                                $img_url = !empty($row['image']) ? htmlspecialchars($row['image']) : 'assets/img/no-image.png';
                                $date = date('d M', strtotime($row['created_at']));
                                
                                echo '
                                <span class="d-inline-flex align-items-center me-5">
                                    <img src="' . $img_url . '" class="rounded border" style="width: 35px; height: 35px; object-fit: cover; margin-right: 4px;">
                                    <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="text-dark text-decoration-none fw-bold" style="font-size: 0.9rem;">
                                        ' . htmlspecialchars($row['title']) . '
                                    </a>
                                    <span class="badge bg-secondary ms-2" style="font-size: 0.7em;">' . $date . '</span>
                                </span>';
                            }
                        }
                        ?>
                    </marquee>
                </div>

            </div>
        </div>
    </div>
<?php
}
?>
	
    <div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> mt-3">
	
<?php
// --- ✨✨ MODIFICATION ICI ✨✨ ---
// Requête pour les widgets de type "header"
$run = mysqli_query($connect, "SELECT * FROM widgets WHERE position = 'header' AND active = 'Yes' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($run)) {
    // Appelle la nouvelle fonction d'affichage
    render_widget($row);
}
// --- ✨✨ FIN DE LA MODIFICATION ✨✨ ---
?>
	
        <div class="row">
<?php
}

function sidebar() {
	
    global $connect, $settings;
?>
			<div id="sidebar" class="col-md-4">

<?php
    // --- WIDGET SONDAGE (POLL) ---
    // (Votre code original pour les SONDAGES reste ici, inchangé)
    $poll_q = mysqli_query($connect, "SELECT * FROM polls WHERE active='Yes' ORDER BY id DESC LIMIT 1");
    
    if (mysqli_num_rows($poll_q) > 0) {
        $poll = mysqli_fetch_assoc($poll_q);
        $poll_id = $poll['id'];
        
        // Vérifier si l'utilisateur a déjà voté (Cookie ou IP)
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $has_voted = false;
        if (isset($_COOKIE['poll_voted_' . $poll_id])) {
            $has_voted = true;
        } else {
            // Requête préparée pour la vérification des votes
            $stmt_check_vote = mysqli_prepare($connect, "SELECT id FROM poll_voters WHERE poll_id=? AND ip_address=?");
            mysqli_stmt_bind_param($stmt_check_vote, "is", $poll_id, $user_ip);
            mysqli_stmt_execute($stmt_check_vote);
            $result_check_vote = mysqli_stmt_get_result($stmt_check_vote);
            if (mysqli_num_rows($result_check_vote) > 0) {
                $has_voted = true;
            }
            mysqli_stmt_close($stmt_check_vote);
        }
?>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-poll-h"></i> Poll of the week
        </div>
        <div class="card-body" id="poll-container-<?php echo $poll_id; ?>">
            <h6 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($poll['question']); ?></h6>
            
            <?php if (!$has_voted): ?>
                <form id="poll-form-<?php echo $poll_id; ?>">
                    <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="poll-options custom-poll-options mb-3">
                        <?php
                        $opts_q = mysqli_query($connect, "SELECT * FROM poll_options WHERE poll_id='$poll_id' ORDER BY id ASC");
                        while ($opt = mysqli_fetch_assoc($opts_q)) {
                            echo '
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="option_id" id="opt-'.$opt['id'].'" value="'.$opt['id'].'">
                                <label class="form-check-label" for="opt-'.$opt['id'].'">
                                    '.htmlspecialchars($opt['title']).'
                                </label>
                            </div>';
                        }
                        ?>
                    </div>
                    <div id="poll-msg-<?php echo $poll_id; ?>" class="text-danger small mb-2"></div>
                    <button type="button" onclick="submitPoll(<?php echo $poll_id; ?>)" class="btn btn-sm btn-primary w-100">Vote</button>
                </form>
            <?php endif; ?>

            <div id="poll-results-<?php echo $poll_id; ?>" style="<?php echo ($has_voted ? '' : 'display:none;'); ?>">
                <?php
                // Calcul initial (si déjà voté, on affiche direct)
                if ($has_voted) {
                    $total_v = 0;
                    $res_data = [];
                    $res_q = mysqli_query($connect, "SELECT * FROM poll_options WHERE poll_id='$poll_id'");
                    while($r = mysqli_fetch_assoc($res_q)) { 
                        $res_data[] = $r; 
                        $total_v += $r['votes']; 
                    }
                    
                    foreach ($res_data as $row) {
                        $percent = ($total_v > 0) ? round(($row['votes'] / $total_v) * 100) : 0;
                        echo '
                        <small>'.htmlspecialchars($row['title']).' ('.$percent.'%)</small>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: '.$percent.'%;" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>';
                    }
                    echo '<div class="text-center small text-muted mt-2">Total votes: '.$total_v.'</div>';
                    echo '<div class="alert alert-success py-1 px-2 mt-2 small text-center"><i class="fas fa-check"></i> You have voted!</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    function submitPoll(pollId) {
        const form = document.getElementById('poll-form-' + pollId);
        const formData = new FormData(form);
        const msgDiv = document.getElementById('poll-msg-' + pollId);
        
        // Validation simple côté client
        if(!formData.get('option_id')) {
            msgDiv.innerText = "Please select an option.";
            return;
        }
        msgDiv.innerText = "Sending...";

        fetch('ajax_vote_poll.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Cacher le formulaire
                form.style.display = 'none';
                
                // Générer le HTML des résultats
                let html = '';
                let total = data.total_votes;
                
                data.results.forEach(opt => {
                    let percent = (total > 0) ? Math.round((opt.votes / total) * 100) : 0;
                    html += `<small>${opt.title} (${percent}%)</small>
                             <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: ${percent}%;"></div>
                             </div>`;
                });
                
                html += `<div class="text-center small text-muted mt-2">Total votes: ${total}</div>`;
                html += `<div class="alert alert-success py-1 px-2 mt-2 small text-center"><i class="fas fa-check"></i> ${data.message}</div>`;
                
                const resDiv = document.getElementById('poll-results-' + pollId);
                resDiv.innerHTML = html;
                $(resDiv).fadeIn(); // Effet jQuery doux
                
            } else {
                msgDiv.innerText = data.message;
            }
        })
        .catch(error => {
            msgDiv.innerText = "Error connecting to server.";
            console.error(error);
        });
    }
    </script>
<?php
    }
    // --- FIN WIDGET SONDAGE ---
?>
            <!-- ADVERTISEMENT WIDGET -->
            <?php render_ad('300x250', true); ?> <!-- Affichage de la publicité 300x250 -->
            <?php render_ad('300x600', true); ?> <!-- Affichage de la publicité 300x600 -->
            <!-- FIN ADVERTISEMENT WIDGET -->

                <div class="card mb-3">
                    <div class="card-header"><i class="fas fa-list"></i> Categories</div>
                    
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush sidebar-categories">
                            <?php
                            $categories_query = mysqli_query($connect, "
                                SELECT 
                                    c.category, c.slug, COUNT(p.id) AS posts_count
                                FROM `categories` c
                                LEFT JOIN posts p ON c.id = p.category_id AND p.active = 'Yes' AND p.publish_at <= NOW()
                                GROUP BY c.id
                                ORDER BY c.category ASC
                            ");
                            
                            while ($row = mysqli_fetch_assoc($categories_query)) {
                                echo '
                                    <a href="category?name=' . htmlspecialchars($row['slug']) . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-chevron-right small me-2 text-muted category-icon"></i> 
                                            ' . htmlspecialchars($row['category']) . '
                                        </span>
                                        <span class="badge bg-light text-dark border rounded-pill">' . $row['posts_count'] . '</span>
                                    </a>
                                ';
                            }
?>
                        </div>
                    </div>
                </div>
				
				<div class="card mb-3">
					<div class="card-header"><i class="fas fa-tags"></i> Popular Tags</div>
                        <div class="card-body">
                                                <div class="d-flex flex-wrap sidebar-tags">
                        <?php
                            // Requête pour récupérer les tags les plus utilisés
                            $stmt_tags = mysqli_prepare($connect, "
                                SELECT 
                                    t.name, t.slug, COUNT(pt.tag_id) AS tag_count
                                FROM tags t
                                JOIN post_tags pt ON t.id = pt.tag_id
                                JOIN posts p ON pt.post_id = p.id
                                WHERE p.active = 'Yes' AND p.publish_at <= NOW()
                                GROUP BY pt.tag_id
                                ORDER BY tag_count DESC, t.name ASC
                                LIMIT 15
                            ");
                            mysqli_stmt_execute($stmt_tags);
                            $result_tags = mysqli_stmt_get_result($stmt_tags);

                            if (mysqli_num_rows($result_tags) == 0) {
                                echo '<div class="alert alert-info p-2 small w-100">No tags found.</div>';
                            } else {
                                while ($row_tag = mysqli_fetch_assoc($result_tags)) {
                                    echo '
                                        <a href="tag.php?name=' . htmlspecialchars($row_tag['slug']) . '" class="tag-link shadow-sm">
                                            <i class="fas fa-hashtag text-muted small"></i> ' . htmlspecialchars($row_tag['name']) . '
                                        </a>
                                    ';
                                }
                            }
                            mysqli_stmt_close($stmt_tags);
?>
						</div>
					</div>
				</div>
				
<div class="card mb-3 sidebar-tabs-card">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs nav-justified" id="sidebarTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular" type="button" role="tab" aria-selected="true">
                    <i class="fas fa-bolt text-warning"></i> Popular
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#commentss" type="button" role="tab" aria-selected="false">
                    <i class="fas fa-comments text-info"></i> Comments
                </button>
            </li>
        </ul>
    </div>
        <div class="card-body p-0"> <div class="tab-content" id="sidebarTabsContent">
            
            <div class="tab-pane fade show active" id="popular" role="tabpanel" aria-labelledby="popular-tab">
                <div class="list-group list-group-flush">
                <?php
                $run = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY views DESC, id DESC LIMIT 4");
                if (mysqli_num_rows($run) <= 0) {
                    echo '<div class="p-3 text-muted small">No posts found.</div>';
                } else {
                    while ($row = mysqli_fetch_assoc($run)) {
                        $img_src = ($row['image'] != "") ? htmlspecialchars($row['image']) : 'assets/img/no-image.png'; // Fallback simple ou SVG
                        
                        // Si c'est votre SVG placeholder, gardez votre logique, sinon :
                        $image_html = '<img src="' . $img_src . '" alt="Img" class="rounded" width="60" height="60" style="object-fit: cover;">';
                        if($row['image'] == "") {
                             // Placeholder SVG minimaliste si pas d'image
                             $image_html = '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width:60px; height:60px;"><i class="fas fa-image"></i></div>';
                        }

                        echo '
                        <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="list-group-item list-group-item-action d-flex align-items-center p-3 sidebar-post-item">
                            <div class="flex-shrink-0 me-3">
                                ' . $image_html . '
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-1 text-truncate small fw-bold text-dark title-hover">' . htmlspecialchars($row['title']) . '</h6>
                                <small class="text-muted d-block">
                                    <i class="far fa-clock me-1"></i> ' . date($settings['date_format'], strtotime($row['created_at'])) . '
                                </small>
                            </div>
                        </a>';
                    }
                }
                ?>
                </div>
            </div>

            <div class="tab-pane fade" id="commentss" role="tabpanel" aria-labelledby="comments-tab">
                <div class="list-group list-group-flush">
                    <?php
                    $comments_query = mysqli_query($connect, "
                        SELECT c.id, c.user_id, c.guest, c.created_at, p.title AS post_title, p.slug AS post_slug, u.username AS user_username, u.avatar AS user_avatar
                        FROM `comments` c JOIN `posts` p ON c.post_id = p.id LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
                        WHERE c.approved='Yes' AND p.active='Yes' ORDER BY c.id DESC LIMIT 4
                    ");
                    
                    if (mysqli_num_rows($comments_query) == 0) {
                        echo '<div class="p-3 text-muted small">No comments yet.</div>';
                    } else {
                        while ($row = mysqli_fetch_assoc($comments_query)) {
                            $acavatar = 'assets/img/avatar.png';
                            $acuthor_name = 'Guest';
                            if ($row['guest'] == 'Yes') {
                                $acuthor_name = $row['user_id'];
                            } else if ($row['user_username']) {
                                $acavatar = $row['user_avatar'];
                                $acuthor_name = $row['user_username'];
                            }
                            
                            echo '
                            <a href="post?name=' . htmlspecialchars($row['post_slug']) . '#comments" class="list-group-item list-group-item-action d-flex align-items-start p-3 sidebar-comment-item">
                                <img src="' . htmlspecialchars($acavatar) . '" class="rounded-circle me-3 border" width="40" height="40" style="object-fit: cover;" alt="Avatar">
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-dark">' . htmlspecialchars($acuthor_name) . '</span>
                                        <small class="text-muted" style="font-size: 0.7rem;">' . date('d/m', strtotime($row['created_at'])) . '</small>
                                    </div>
                                    <p class="mb-0 small text-muted text-truncate">
                                        on <span class="text-primary">' . htmlspecialchars($row['post_title']) . '</span>
                                    </p>
                                </div>
                            </a>';
                        }
                    }
                    ?>
                </div>
            </div>

        </div>
    </div>
</div>
				
<div class="card mb-3 sidebar-subscribe shadow-sm">
    <div class="card-body text-center p-4">
        <div class="icon-box mb-3 mx-auto bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="fas fa-envelope-open-text fa-2x"></i>
        </div>
        <h5 class="card-title fw-bold">Newsletter</h5>
        <p class="card-text small text-muted mb-4">Get the latest news and exclusive offers directly in your inbox.</p>
        
        <form action="" method="POST">
            <div class="mb-3">
                <input type="email" class="form-control text-center" placeholder="Your email address" name="email" required style="border-radius: 20px;">
            </div>
            <div class="d-grid">
                <button class="btn btn-primary btn-block" type="submit" name="subscribe" style="border-radius: 20px;">
                    Subscribe Now
                </button>
            </div>
        </form>
        
        <?php
        if (isset($_POST['subscribe'])) {
            $email = $_POST['email']; 
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo '<div class="alert alert-danger mt-2 small p-2">The entered E-Mail Address is invalid</div>';
            } else {
                // Requête préparée pour vérifier l'existence
                $stmt_sub_check = mysqli_prepare($connect, "SELECT email FROM `newsletter` WHERE email=? LIMIT 1");
                mysqli_stmt_bind_param($stmt_sub_check, "s", $email);
                mysqli_stmt_execute($stmt_sub_check);
                $result_sub_check = mysqli_stmt_get_result($stmt_sub_check);
                
                if (mysqli_num_rows($result_sub_check) > 0) {
                    echo '<div class="alert alert-warning mt-2 small p-2">This E-Mail Address is already subscribed.</div>';
                } else {
                    // Requête préparée pour l'insertion
                    $stmt_sub_insert = mysqli_prepare($connect, "INSERT INTO `newsletter` (email) VALUES (?)");
                    mysqli_stmt_bind_param($stmt_sub_insert, "s", $email);
                    mysqli_stmt_execute($stmt_sub_insert);
                    mysqli_stmt_close($stmt_sub_insert);
                    echo '<div class="alert alert-success mt-2 small p-2">You have successfully subscribed to our newsletter.</div>';
                }
                mysqli_stmt_close($stmt_sub_check);
            }
        }
        ?>
    </div>
</div>

<?php
// Requête pour les widgets de type "sidebar"
$run = mysqli_query($connect, "SELECT * FROM widgets WHERE position = 'sidebar' AND active = 'Yes' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($run)) {
    // Appelle la nouvelle fonction d'affichage
    render_widget($row);
}
?>
			</div>
		
<?php
}

function footer()
{
    // Rendre les variables globales accessibles
    global $phpblog_version, $connect, $settings, $purifier;
    
    // --- NOUVEAU BLOC : RÉCUPÉRER LES PAGES DU FOOTER ---
    
    // Initialiser un tableau pour stocker nos 5 pages
    $footer_content = [
        'legal' => null,
        'contact_methods' => null,
        'most_viewed' => null,
        'cta_buttons' => null,
        'trust_badges' => null
    ];
    
    // S'assurer que le purificateur est prêt
    if (!isset($purifier)) {
        $purifier = get_purifier();
    }

    // Requête pour récupérer les 5 pages actives
    $stmt_footer = mysqli_prepare($connect, "
        SELECT page_key, title, content 
        FROM footer_pages 
        WHERE active = 'Yes' AND page_key IN ('legal', 'contact_methods', 'most_viewed', 'cta_buttons', 'trust_badges')
    ");
    
    if ($stmt_footer) {
        mysqli_stmt_execute($stmt_footer);
        $result_footer = mysqli_stmt_get_result($stmt_footer);
        
        while ($page = mysqli_fetch_assoc($result_footer)) {
            // Remplir notre tableau
            $footer_content[$page['page_key']] = [
                'title' => htmlspecialchars($page['title']),
                'content' => $purifier->purify($page['content']) // Nettoyer le contenu
            ];
        }
        mysqli_stmt_close($stmt_footer);
    }
    // --- FIN DU NOUVEAU BLOC ---
    
?>
            </div> <?php
    // Requête pour les widgets de type "footer" (EXISTANT)
    $run = mysqli_query($connect, "SELECT * FROM widgets WHERE position = 'footer' AND active = 'Yes' ORDER BY id ASC");
    while ($row = mysqli_fetch_assoc($run)) {
        render_widget($row);
    }
?>
    </div>    <footer class="bg-dark text-light pt-5 pb-3 mt-3">
        <div class="<?php echo ($settings['layout'] == 'Wide') ? 'container-fluid' : 'container'; ?>">
            <div class="row gy-4">

            <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">
                        <i class="far fa-newspaper me-2"></i> <?php echo htmlspecialchars($settings['sitename']); ?>
                    </h5>
                    <p class="text-white-50">
                        <?php echo htmlspecialchars($settings['description']); ?>
                    </p>
                    
                    <h5 class="text-uppercase fw-bold mt-4 mb-3">Follow us</h5>
                    <div class="mt-3">
                        <?php if ($settings['facebook'] != ''): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" target="_blank" class="text-white-50 text-decoration-none me-3" title="Facebook"><i class="bi bi-facebook fs-4"></i></a>
                        <?php endif; ?>
                        <?php if ($settings['twitter'] != ''): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter']); ?>" target="_blank" class="text-white-50 text-decoration-none me-3" title="Twitter"><i class="bi bi-twitter-x fs-4"></i></a>
                        <?php endif; ?>
                        <?php if ($settings['instagram'] != ''): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" target="_blank" class="text-white-50 text-decoration-none me-3" title="Instagram"><i class="bi bi-instagram fs-4"></i></a>
                        <?php endif; ?>
                        <?php if ($settings['youtube'] != ''): ?>
                            <a href="<?php echo htmlspecialchars($settings['youtube']); ?>" target="_blank" class="text-white-50 text-decoration-none me-3" title="YouTube"><i class="bi bi-youtube fs-4"></i></a>
                        <?php endif; ?>
                        <?php if ($settings['linkedin'] != ''): ?>
                            <a href="<?php echo htmlspecialchars($settings['linkedin']); ?>" target="_blank" class="text-white-50 text-decoration-none me-3" title="LinkedIn"><i class="bi bi-linkedin fs-4"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h5 class="text-uppercase fw-bold mb-4">Others</h5>
                        <ul class="list-unstyled mb-0">
                            <div class="d-flex gap-2 justify-content-start flex-wrap">
                                <a href="sitemap.php" class="btn btn-outline-light btn-sm" title="Sitemap">
                                    <i class="fas fa-sitemap fa-lg text-info"></i> <span class="small">Sitemap</span>
                                </a>
                                <a href="rss.php" class="btn btn-outline-light btn-sm" title="RSS Feed">
                                    <i class="fas fa-rss fa-lg text-warning"></i> <span class="small">RSS Feed</span>
                                </a>
                            </div>
                        </ul>
                    </div>                
                </div>





                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">Navigation</h5>
                    <ul class="list-unstyled mb-0">
                        <?php
                        // Boucle pour les liens du menu
                        $menu_query = mysqli_query($connect, "SELECT * FROM `menu` WHERE active = 'Yes' ORDER BY id ASC");
                        while ($menu_item = mysqli_fetch_assoc($menu_query)) {
                            echo '<li class="mb-2">
                                <a href="' . htmlspecialchars($menu_item['path']) . '" class="text-white-50 text-decoration-none">
                                    <i class="fa ' . htmlspecialchars($menu_item['fa_icon']) . ' me-2" style="width: 1.2em;"></i> ' . htmlspecialchars($menu_item['page']) . '
                                </a>
                            </li>';
                        }
                        ?>
                        <!-- li class="mb-2">
                            <a href="rss.php" target="_blank" class="text-white-50 text-decoration-none"><i class="bi bi-rss me-2" style="width: 1.2em;"></i> Flux RSS</a>
                        </li>
                        <li class="mb-2">
                            <a href="sitemap.php" target="_blank" class="text-white-50 text-decoration-none"><i class="bi bi-diagram-3 me-2" style="width: 1.2em;"></i> Sitemap</a>
                        </li -->
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <?php if ($footer_content['legal']): ?>
                        <h5 class="text-uppercase fw-bold mb-4"><?php echo $footer_content['legal']['title']; ?></h5>
                        <div class="text-white-50 small mb-3">
                            <?php echo $footer_content['legal']['content']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($footer_content['contact_methods']): ?>
                        <h5 class="text-uppercase fw-bold mb-4"><?php echo $footer_content['contact_methods']['title']; ?></h5>
                        <div class="text-white-50 small">
                            <?php echo $footer_content['contact_methods']['content']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <?php if ($footer_content['cta_buttons']): ?>
                        <h5 class="text-uppercase fw-bold mb-4"><?php echo $footer_content['cta_buttons']['title']; ?></h5>
                        <div class="text-white-50 small mb-3">
                            <?php echo $footer_content['cta_buttons']['content']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($footer_content['most_viewed']): ?>
                        <h5 class="text-uppercase fw-bold mb-4"><?php echo $footer_content['most_viewed']['title']; ?></h5>
                        <div class="text-white-50 small mb-3">
                            <?php echo $footer_content['most_viewed']['content']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($footer_content['trust_badges']): ?>
                        <h5 class="text-uppercase fw-bold mb-4"><?php echo $footer_content['trust_badges']['title']; ?></h5>
                        <div class="text-white-50 small">
                            <?php echo $footer_content['trust_badges']['content']; ?>
                        </div>
                    <?php else: ?>
                        <h5 class="text-uppercase fw-bold mb-4">Logo</h5>
                        <img src="<?php echo htmlspecialchars($settings['favicon_url']); ?>" alt="Logo" width="96" height="96">
                    <?php endif; ?>
                </div>
            
            </div>

            <div class="text-center text-white-50 border-top border-secondary-subtle pt-3 mt-4">
                <p class="small mb-0">
                    &copy; <?php echo date("Y") .' '. htmlspecialchars($settings['sitename']); ?>. All rights reserved.
                    <span class="mx-2">|</span>
                    <i>Powered by <b>phpBlog v<?php echo htmlspecialchars($phpblog_version); ?></b></i>
                </p>
            </div>
            
             <div class="scroll-btn"><div class="scroll-btn-arrow"></div></div>
            
        </div>
</footer>
<?php
global $current_page; // Récupérer le nom de la page actuelle (défini dans head())

// 1. Déterminer la condition d'affichage de la page
$page_condition = "display_pages = 'all'";
if ($current_page == 'index.php') {
    $page_condition = "(display_pages = 'all' OR display_pages = 'home')";
}

// 2. Préparer la requête pour chercher les popups actifs
$popups_to_show = [];
$stmt_popups = mysqli_prepare($connect, "
    SELECT * FROM popups 
    WHERE active = 'Yes' AND $page_condition
");

if ($stmt_popups) {
    mysqli_stmt_execute($stmt_popups);
    $result_popups = mysqli_stmt_get_result($stmt_popups);

    while ($popup = mysqli_fetch_assoc($result_popups)) {
        // 3. Vérifier si le popup a déjà été montré (pour la session)
        $session_key = 'popup_shown_' . $popup['id'];
        if ($popup['show_once_per_session'] == 'Yes' && isset($_SESSION[$session_key])) {
            continue; // Ce popup a déjà été vu, on l'ignore
        }

        // Ajouter ce popup à la liste d'affichage
        $popups_to_show[] = $popup;
    }
    mysqli_stmt_close($stmt_popups);
}

// 4. Boucler sur les popups à afficher et créer les Modals HTML
if (!empty($popups_to_show)) {

    // S'assurer que HTMLPurifier est prêt
    if (!isset($purifier)) {
        $purifier = get_purifier();
    }

    foreach ($popups_to_show as $popup) {
        $modal_id = 'popupModal' . (int)$popup['id'];
        $safe_content = $purifier->purify($popup['content']);

        // Extraire le titre du contenu si le titre du popup est vide
        // (Nous utilisons le titre de la BDD pour le modal-title)
        $modal_title = htmlspecialchars($popup['title']);

        // Générer le HTML du Modal
        echo "
        <div class='modal fade' id='{$modal_id}' tabindex='-1' aria-labelledby='{$modal_id}Label' aria-hidden='true'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='{$modal_id}Label'>{$modal_title}</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        {$safe_content}
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Fermer</button>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Obtenir les éléments du DOM
        const themeSwitcherBtn = document.getElementById('theme-switcher-btn');
        const lightTheme = document.getElementById('theme-light');
        const darkTheme = document.getElementById('theme-dark');
        const iconMoon = document.getElementById('theme-icon-moon');
        const iconSun = document.getElementById('theme-icon-sun');

        // Fonction pour appliquer le thème
        function updateTheme(theme) {
            if (theme === 'dark') {
                lightTheme.disabled = true;
                darkTheme.disabled = false;
                if(iconMoon) iconMoon.style.display = 'none';
                if(iconSun) iconSun.style.display = 'inline-block';
            } else {
                lightTheme.disabled = false;
                darkTheme.disabled = true;
                if(iconMoon) iconMoon.style.display = 'inline-block';
                if(iconSun) iconSun.style.display = 'none';
            }
        }

        // Obtenir le thème actuel depuis le localStorage
        let currentTheme = localStorage.getItem('theme');
        if (!currentTheme) {
            // Si rien n'est défini, utiliser 'light' par défaut
            currentTheme = 'light';
            localStorage.setItem('theme', currentTheme);
        }
        
        // Appliquer le thème au chargement de la page
        updateTheme(currentTheme);

        // Gérer le clic sur le bouton
        if (themeSwitcherBtn) {
            themeSwitcherBtn.addEventListener('click', function () {
                let theme = localStorage.getItem('theme');
                let newTheme = (theme === 'dark') ? 'light' : 'dark';
                
                // Appliquer le nouveau thème
                updateTheme(newTheme);
                
                // Sauvegarder le choix
                localStorage.setItem('theme', newTheme);
            });
        }
    });
    </script>
<?php
    // --- NOUVEL AJOUT ÉTAPE 2 ---
    // Charger le script d'interaction uniquement sur la page post.php
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    if ($current_page == 'post.php') {
        echo '<script src="assets/js/post-interactions.js"></script>';
    }
    // --- FIN AJOUT ---

    // --- NOUVELLE LOGIQUE POPUP (Étape 6) ---
    if (!empty($popups_to_show)) {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
        ";

        foreach ($popups_to_show as $popup) {
            $modal_id = 'popupModal' . (int)$popup['id'];
            $delay_ms = (int)$popup['delay_seconds'] * 1000;

            // Lancer le script pour ce popup
            echo "
            setTimeout(function() {
                var popupModal = new bootstrap.Modal(document.getElementById('{$modal_id}'), {});
                popupModal.show();
            }, {$delay_ms});
            ";

            // Marquer ce popup comme "vu" dans la session
            if ($popup['show_once_per_session'] == 'Yes') {
                $_SESSION['popup_shown_' . $popup['id']] = true;
            }
        }

        echo "
        });
        </script>";
    }
    // --- FIN LOGIQUE POPUP ---
    ?>    
    </body>
</html>
<?php
}

/**
 * Affiche une publicité selon la taille demandée.
 * @param string $size Le format (ex: '728x90', '300x250')
 * @param bool $wrapper (Optionnel) Si True, entoure la pub d'une "Card" blanche (pour la sidebar).
 */
function render_ad($size, $wrapper = false) {
    global $connect, $settings;
    
    $stmt = mysqli_prepare($connect, "SELECT * FROM ads WHERE active='Yes' AND ad_size = ? ORDER BY RAND() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $size);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Si une publicité est trouvée
    if ($row = mysqli_fetch_assoc($result)) {
        $tracking_url = $settings['site_url'] . '/click_ad.php?id=' . $row['id'];
        
        // 1. Si l'option Wrapper est activée, on ouvre la Card
        if ($wrapper) {
            echo '<div class="card mb-3"><div class="card-body text-center">';
        }

        // 2. Affichage de la publicité
        echo '
        <div class="ad-container text-center my-3">
            <a href="' . htmlspecialchars($tracking_url) . '" target="_blank" rel="nofollow">
                <img src="' . htmlspecialchars($row['image_url']) . '" alt="' . htmlspecialchars($row['name']) . '" class="img-fluid shadow-sm rounded" style="max-width:100%; height:auto;">
            </a>
        </div>';

        // 3. Si l'option Wrapper est activée, on ferme la Card
        if ($wrapper) {
            echo '</div></div>';
        }
    }
    // SINON (si pas de pub), on ne fait RIEN (donc pas de boîte vide)
    
    mysqli_stmt_close($stmt);
}
?>