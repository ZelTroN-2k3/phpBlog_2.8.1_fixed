<?php
include_once "config.php"; // Pour $connect

// --- AJOUT : Charger les paramètres (requis pour la redirection) ---
$settings = [];
$result = mysqli_query($connect, "SELECT config_name, config_value FROM settings");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['config_name']] = $row['config_value'];
    }
} else {
    // Si la BDD ne répond pas, rediriger vers la racine manuellement
    $settings['site_url'] = '.'; // Fallback de sécurité
}
// --- FIN AJOUT ---

if(!isset($_SESSION)) {
    session_start();
}
session_destroy();

// Cette ligne fonctionnera maintenant
echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '" />';
exit();
?>