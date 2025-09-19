<?php
header('Content-Type: application/json');

// On récupère l'identifiant du devis envoyé par le formulaire
$quoteId = $_POST['quoteId'] ?? '';

if (empty($quoteId)) {
    echo json_encode(['success' => false, 'message' => 'Identifiant de devis manquant.']);
    exit;
}

try {
    $quoteJsonPath = "quotes/{$quoteId}.json";

    if (!file_exists($quoteJsonPath)) {
        echo json_encode(['success' => false, 'message' => 'Devis introuvable.']);
        exit;
    }

    $quoteData = json_decode(file_get_contents($quoteJsonPath), true);
    
    // On met à jour le statut du devis
    $quoteData['status'] = 'accepted';
    $quoteData['acceptance_date'] = date('Y-m-d H:i:s');
    
    file_put_contents($quoteJsonPath, json_encode($quoteData, JSON_PRETTY_PRINT));

    // Ici, vous ajouteriez la logique de redirection vers votre page de paiement
    // (ex: une intégration Stripe, PayPal, etc.).
    
    // Pour l'instant, on redirige vers une page de confirmation
    header("Location: confirmation-quote-accepted.html");
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'acceptation du devis : ' . $e->getMessage()]);
    error_log('Erreur acceptation devis: ' . $e->getMessage());
}
?>