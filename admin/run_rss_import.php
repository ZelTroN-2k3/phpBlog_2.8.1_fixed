<?php
// --- SCRIPT D'IMPORTATION RSS (Corrigé pour <media:content>) ---

// 1. Inclure le cœur du CMS (pour BDD, $settings, session_start(), etc.)
include "../core.php";

// Clé secrète pour le cron job.
// !! CHANGEZ CETTE VALEUR pour quelque chose de long et aléatoire !!
define('RSS_CRON_SECRET_KEY', '#7dWrR!W@29LxG22wW^b'); // VOTRE_CLE_SECRETE_12345

// Déterminer le mode de fonctionnement
$manual_run_id = $_GET['id'] ?? null;
$cron_run_key = $_GET['key'] ?? null;

$sql_query = ""; // Initialiser

// 2. GESTION DE LA SÉCURITÉ
if ($manual_run_id) {
    // --- C'est un test manuel depuis l'admin ---
    if (!isset($_SESSION['sec-username'])) {
        die('Accès non autorisé. (Non connecté)');
    }
    
    $uname = $_SESSION['sec-username'];
    $stmt_admin_check = mysqli_prepare($connect, "SELECT role FROM `users` WHERE username=? AND role='Admin'");
    mysqli_stmt_bind_param($stmt_admin_check, "s", $uname);
    mysqli_stmt_execute($stmt_admin_check);
    $result_admin_check = mysqli_stmt_get_result($stmt_admin_check);
    
    if (mysqli_num_rows($result_admin_check) == 0) {
        die('Accès non autorisé. (Pas un admin)');
    }
    mysqli_stmt_close($stmt_admin_check);
    
    $sql_query = "SELECT * FROM rss_imports WHERE id = " . (int)$manual_run_id;

} elseif ($cron_run_key === RSS_CRON_SECRET_KEY) {
    // --- C'est un run automatique (cron) ---
    $sql_query = "SELECT * FROM rss_imports WHERE is_active = 1";
} else {
    // --- Accès non autorisé ---
    die('Accès non autorisé. (Clé ou paramètre manquant)');
}


// 3. Initialiser HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// 4. Fonction (corrigée) pour créer un "slug" (URL-friendly) UNIQUE
function createUniqueSlug($connect, $string) {
    // 1. Créer le slug de base
    $slug = strtolower(strip_tags($string));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'post-' . time();
    }
    
    // 2. Vérifier les doublons
    $stmt_check_slug = mysqli_prepare($connect, "SELECT id FROM posts WHERE slug = ?");
    $original_slug = $slug;
    $counter = 1;
    
    mysqli_stmt_bind_param($stmt_check_slug, "s", $slug);
    mysqli_stmt_execute($stmt_check_slug);
    $result = mysqli_stmt_get_result($stmt_check_slug);

    while (mysqli_num_rows($result) > 0) {
        // Le slug existe, on en crée un nouveau
        $slug = $original_slug . '-' . $counter;
        $counter++;
        
        // On re-vérifie
        mysqli_stmt_bind_param($stmt_check_slug, "s", $slug);
        mysqli_stmt_execute($stmt_check_slug);
        $result = mysqli_stmt_get_result($stmt_check_slug);
    }
    
    mysqli_stmt_close($stmt_check_slug);
    return $slug;
}


// --- DÉBUT DE L'IMPORTATION ---
header('Content-Type: text/plain; charset=utf-8'); 
echo "--- Début de l'importation RSS ---\n\n";

// Augmenter le temps d'exécution
set_time_limit(300); // 5 minutes

$imported_count = 0;
$skipped_count = 0;
$feed_errors = [];

// 5. Déterminer le mode (pour l'affichage)
if ($manual_run_id) {
    echo "Mode : Importation manuelle du flux ID: " . (int)$manual_run_id . "\n";
} else {
    echo "Mode : Importation automatique de tous les flux actifs.\n";
}
echo "---------------------------------\n";

// 6. Récupérer les flux
$result_feeds = mysqli_query($connect, $sql_query);
if (!$result_feeds || mysqli_num_rows($result_feeds) == 0) {
    echo "Aucun flux à importer.\n";
} else {
    while ($feed = mysqli_fetch_assoc($result_feeds)) {
        
        $feed_url = $feed['feed_url'];
        echo "\n[Traitement du flux: " . htmlspecialchars($feed_url) . "]\n";
        
        // 7. Charger le flux XML
        libxml_use_internal_errors(true); 
        $xml = @simplexml_load_file($feed_url);
        libxml_clear_errors();
        
        if ($xml === false) {
            echo "ERREUR : Impossible de charger ou de parser ce flux.\n";
            $feed_errors[] = $feed_url;
            continue; 
        }
        
        // 8. Préparer les requêtes BDD
        $stmt_check_guid = mysqli_prepare($connect, "SELECT id FROM posts WHERE imported_guid = ?");
        
        // Utiliser 'author_id' (corrigé)
        $stmt_insert = mysqli_prepare($connect, "
            INSERT INTO posts (author_id, category_id, title, slug, content, image, created_at, active, publish_at, imported_guid) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Yes', ?, ?)
        ");
        
        if($stmt_insert === false) {
            echo "ERREUR : Impossible de préparer la requête d'insertion MySQL : " . mysqli_error($connect) . "\n";
            continue;
        }

        // 9. Parcourir chaque <item> ou <entry>
        $items = $xml->channel->item ?? $xml->entry ?? [];
        
        if (empty($items)) {
             echo "Aucun article trouvé dans ce flux.\n";
             continue;
        }

        foreach ($items as $item) {
            
            // 10. Extraire les données
            $namespaces = $item->getNamespaces(true);
            $content_ns = $item->children($namespaces['content'] ?? null);
            // --- NOUVELLE LIGNE ---
            $media_ns = $item->children($namespaces['media'] ?? null); // Obtenir le namespace 'media'

            $title = (string)$item->title;
            $link = (string)$item->link['href'] ?? (string)$item->link;
            $pubDate = (string)$item->pubDate ?? (string)$item->updated ?? date('Y-m-d H:i:s');
            $guid = (string)$item->guid ?? (string)$item->id ?? $link; 
            $description = (string)$content_ns->encoded ?? (string)$item->description ?? (string)$item->summary ?? 'Contenu non disponible.';
            
            // --- !! BLOC DE RECHERCHE D'IMAGE CORRIGÉ !! ---
            $image_url = '';

            if (isset($media_ns->content)) {
                // 1. Priorité : <media:content> (Le Monde, etc.)
                $attrs = $media_ns->content->attributes();
                if (isset($attrs['url'])) {
                     $image_url = (string)$attrs['url'];
                }
            }
            
            if (empty($image_url) && isset($item->enclosure) && strpos((string)$item->enclosure['type'], 'image') !== false) {
                // 2. Deuxième choix : <enclosure> (Standard RSS)
                $image_url = (string)$item->enclosure['url'];
            } 
            
            if (empty($image_url) && preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
                // 3. Dernier choix : 1ère image dans la description
                $image_url = $matches[1];
            }
            // --- FIN DU BLOC CORRIGÉ ---

            // 11. Vérifier les doublons de GUID
            mysqli_stmt_bind_param($stmt_check_guid, "s", $guid);
            mysqli_stmt_execute($stmt_check_guid);
            $result_check = mysqli_stmt_get_result($stmt_check_guid);
            
            if (mysqli_num_rows($result_check) > 0) {
                $skipped_count++;
                continue;
            }
            
            // 12. L'article est nouveau : Nettoyer et Insérer
            $clean_title = strip_tags($title);
            $clean_content = $purifier->purify($description); // Sécurité !
            $slug = createUniqueSlug($connect, $clean_title);
            $post_time = date('Y-m-d H:i:s', strtotime($pubDate));
            
            // Chaîne de types corrigée : 'author_id' (i), 'category_id' (i), ... (iisssssss)
            mysqli_stmt_bind_param($stmt_insert, "iisssssss", 
                $feed['import_as_user_id'], 
                $feed['import_as_category_id'], 
                $clean_title, 
                $slug, 
                $clean_content, 
                $image_url, 
                $post_time,      // pour created_at
                $post_time,      // pour publish_at
                $guid
            );
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $imported_count++;
                echo "  -> IMPORTÉ : " . $clean_title . "\n";
            } else {
                 echo "  -> ERREUR BDD : " . mysqli_error($connect) . "\n";
            }
        }
        
        // 13. Fermer les requêtes pour ce flux
        mysqli_stmt_close($stmt_check_guid);
        mysqli_stmt_close($stmt_insert);
        
        // 14. Mettre à jour l'heure de dernière importation
        $stmt_update_time = mysqli_prepare($connect, "UPDATE rss_imports SET last_import_time = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update_time, "i", $feed['id']);
        mysqli_stmt_execute($stmt_update_time);
        mysqli_stmt_close($stmt_update_time);
    }
}

mysqli_close($connect);

// 15. Afficher le rapport final
echo "\n---------------------------------\n";
echo "RAPPORT FINAL :\n";
echo "Nouveaux articles importés : $imported_count\n";
echo "Articles déjà existants (ignorés) : $skipped_count\n";
echo "Flux en erreur : " . count($feed_errors) . "\n";
echo "\n--- Importation terminée. ---";

?>