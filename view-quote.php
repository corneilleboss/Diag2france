<?php
// On récupère l'identifiant du devis depuis l'URL
$quoteId = $_GET['id'] ?? '';

// Vérifier que l'identifiant existe et que le fichier du devis existe
if (empty($quoteId) || !file_exists("quotes/{$quoteId}.html")) {
    http_response_code(404);
    echo "<h1>Erreur 404 - Devis non trouvé</h1><p>Le devis que vous cherchez n'existe pas ou l'URL est incorrecte.</p>";
    exit;
}

// Charger le contenu HTML du devis
$quoteHtml = file_get_contents("quotes/{$quoteId}.html");

// Charger les données du devis (pour d'éventuels traitements futurs)
$quoteData = json_decode(file_get_contents("quotes/{$quoteId}.json"), true);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis n°<?php echo htmlspecialchars($quoteId); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #444;
        }
        .quote-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .quote-content {
            /* Styles pour le contenu HTML du devis généré */
            padding: 20px;
        }
        .accept-section {
            background-color: #e9ecef;
            padding: 30px;
            text-align: center;
            border-top: 2px solid #ccc;
            margin-bottom: 20px; /* Ajout d'une marge en bas pour séparer du contenu */
        }
        .accept-section h2 {
            color: #004d99;
            margin-top: 0;
        }
        .accept-btn {
            background-color: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: inline-block;
        }
        .accept-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="quote-container">

    <div class="accept-section">
        <h2>Acceptation du devis</h2>
        <p>En cliquant sur le bouton ci-dessous, vous acceptez les termes et conditions du devis. Cela générera votre Ordre de Mission que vous pourrez signer.</p>
        <form action="generate-mission-order.php" method="POST">
            <input type="hidden" name="quoteId" value="<?php echo htmlspecialchars($quoteId); ?>">
            <button type="submit" class="accept-btn">Accepter et continuer</button>
        </form>
    </div>

    <div class="quote-content">
        <?php 
            // On inclut le contenu HTML du devis généré
            echo $quoteHtml; 
        ?>
    </div>
    
    <div class="accept-section">
        <h2>Acceptation du devis</h2>
        <p>En cliquant sur le bouton ci-dessous, vous acceptez les termes et conditions du devis. Cela générera votre Ordre de Mission que vous pourrez signer.</p>
        <form action="generate-mission-order.php" method="POST">
            <input type="hidden" name="quoteId" value="<?php echo htmlspecialchars($quoteId); ?>">
            <button type="submit" class="accept-btn">Accepter et continuer</button>
        </form>
    </div>

</div>

</body>
</html>