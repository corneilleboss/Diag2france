<?php
// Incluez les bibliothèques nécessaires, par exemple pour PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

header('Content-Type: application/json');

// Récupération des données du POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

try {
// Les données nécessaires depuis le front-end
$diagnostiqueur = $data['diagnostiqueur'] ?? [];
$client = $data['client'] ?? [];
$adresse_bien = $data['adresseBien'] ?? 'Non spécifiée';
$adresse_facturation = $data['adresseFacturation'] ?? [];
$diagnostics = $data['diagnostics'] ?? [];
$services = $data['services'] ?? [];
$prixTotalTTC = $data['prixTotal'] ?? 0;
$dateRdv = $data['date'] ?? 'Non spécifiée';
$heureRdv = $data['heure'] ?? 'Non spécifiée';

// Informations du diagnostiqueur (basées sur les données envoyées ou une configuration statique)
$diagnostiqueurInfo = [
    'nom' => $diagnostiqueur['nom'] ?? '',
    'prenom' => $diagnostiqueur['prenom'] ?? '',
    'email' => $diagnostiqueur['email'] ?? '',
    'telephone' => $diagnostiqueur['telephone'] ?? '',
    'adresse' => $diagnostiqueur['adresse'] ?? '',
    'denomination_sociale' => 'Corneilldev',
    'nom_entreprise' => 'corneilledev DIAGNOSTICS',
    'siege_social' => '123 RUE DE LA LIBERTÉ, 75000 PARIS',
    'capital_social' => '50 000 €',
    'siret' => '123 456 789 00000',
    'code_ape' => '7112B',
    'tva' => 'FR123456789',
    'assureur' => 'AXA',
    'police_assurance' => 'RC123456789',
    'numero_certifie' => 'CERTI-123456',
    'organisme_certification' => 'CERTIF-PRO',
];

// Informations du médiateur
$mediateurInfo = [
    'nom' => 'Médiateur de la consommation',
    'telephone' => '01 23 45 67 89',
    'email' => 'contact@mediateur.com',
    'site' => 'www.mediateur-conso.fr',
];

// Liste des diagnostics avec leurs références réglementaires
$referencesReglementaires = [
    'DPE' => 'Méthode 3CL-DPE 2021 (arrêtés 31 mars et 8 oct. 2021)',
    'AMIANTE' => 'NF X 46-020',
    'PLOMB' => 'NF X 46-030',
    'GAZ' => 'NF P 45-500',
    'ELECTRICITE' => 'NF C 16-600',
    'TERMITES' => 'NF P 03-201',
    'ERP' => 'Code de l’environnement L125-5',
    'AUDIT ENERGETIQUE' => 'Décret n° 2022-780 du 4 mai 2022',
    'CARREZE BOUTIN' => 'NF X 46-030',
];

// CORRECTION DU CALCUL DES PRIX
$tvaRate = 0.20; // 20%
$prixHT = $prixTotalTTC / (1 + $tvaRate); // On calcule le prix HT à partir du TTC
$prixTVA = $prixTotalTTC - $prixHT;
$prixTTC = $prixTotalTTC;

// Formatage des prix pour l'affichage
$prixHT_formatted = number_format($prixHT, 2, ',', ' ');
$prixTVA_formatted = number_format($prixTVA, 2, ',', ' ');
$prixTTC_formatted = number_format($prixTTC, 2, ',', ' ');

// Génération d'un identifiant unique pour le devis
$quoteId = uniqid('Q-');

// Construction de l'adresse de facturation pour le devis
$fullBillingAddress = "";
if (isset($adresse_facturation['isSame']) && $adresse_facturation['isSame']) {
    $fullBillingAddress = $adresse_bien;
} else {
    $fullBillingAddress = "{$adresse_facturation['adresse']}, {$adresse_facturation['codePostal']} {$adresse_facturation['ville']}";
}

// Construction de l'affichage des informations client en fonction du type
$clientNom = ($client['type'] === 'particulier') ? ($client['nom'] ?? '') : ($client['representantLegal'] ?? '');
$clientPrenom = ($client['type'] === 'particulier') ? ($client['prenom'] ?? '') : '';
$clientDetails = "
    <strong>Préparé pour :</strong><br>
    {$clientNom} {$clientPrenom}<br>
    {$client['email']}<br>
    {$adresse_bien}
";

// Création du contenu HTML du devis
$quoteHtml = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Devis - {$quoteId}</title>
        <style>
            body { font-family: 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #444; }
            .container { max-width: 800px; margin: 20px auto; background-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
            .header { display: flex; justify-content: space-between; align-items: center; padding: 40px; background-color: #004d99; color: white; }
            .header .logo { max-width: 150px; }
            .header h1 { font-size: 2.5em; text-transform: uppercase; letter-spacing: 5px; margin: 0; }
            .company-info { text-align: right; font-size: 0.9em; line-height: 1.5; color: #eee; }
            .quote-details { display: flex; justify-content: space-between; padding: 20px; background-color: #f0f4f7; border-bottom: 2px solid #ccc; }
            .quote-details-box { flex: 1; margin: 0 10px; padding: 15px; background-color: #fff; border-radius: 5px; border: 1px solid #ccc; }
            h2 { color: #004d99; border-bottom: 2px solid #004d99; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
            table thead th { background-color: #004d99; color: white; text-transform: uppercase; font-size: 0.9em; }
            table tbody tr:nth-child(even) { background-color: #f9f9f9; }
            .total-section { display: flex; justify-content: flex-end; margin-top: 20px; }
            .total-table { width: 300px; border-collapse: collapse; }
            .total-table td { padding: 10px; border-bottom: 1px solid #ddd; }
            .total-table .label { text-align: right; font-weight: bold; }
            .total-table .value { text-align: right; }
            .grand-total { background-color: #004d99; color: white; font-size: 1.2em; font-weight: bold; }
            .footer { padding: 20px 40px; font-size: 0.8em; line-height: 1.6; }
            .signature { margin-top: 50px; border-top: 1px solid #ccc; padding-top: 10px; }
            .description { font-size: 0.8em; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='https://hubhabitats.ekkleo.com/public/uploads/site/1758099800_3f208a0c60a444682430.png' alt='Logo' class='logo'>
                <div class='company-info'>
                    {$diagnostiqueurInfo['nom_entreprise']}<br>
                    {$diagnostiqueurInfo['siege_social']}<br>
                    Tél: {$diagnostiqueurInfo['telephone']}<br>
                    {$diagnostiqueurInfo['email']}
                </div>
            </div>
            
            <div class='quote-details'>
                <div class='quote-details-box'>
                    {$clientDetails}
                </div>
                <div class='quote-details-box'>
                    <strong>Devis N° :</strong> {$quoteId}<br>
                    <strong>Date du devis :</strong> " . date('d/m/Y') . "<br>
                    <strong>Validité :</strong> 30 jours
                </div>
            </div>

            <div style='padding: 20px 40px;'>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Référence réglementaire</th>
                        </tr>
                    </thead>
                    <tbody>";

foreach ($diagnostics as $diag) {
    $ref = $referencesReglementaires[strtoupper(str_replace(' ', '', $diag))] ?? 'N/A';
    $quoteHtml .= "
                        <tr>
                            <td>
                                <p><strong>{$diag}</strong></p>
                                <p class='description'>Diagnostic technique immobilier.</p>
                            </td>
                            <td>{$ref}</td>
                        </tr>";
}
foreach ($services as $service) {
    $quoteHtml .= "
                        <tr>
                            <td>
                                <p><strong>{$service['name']}</strong></p>
                                <p class='description'>Service additionnel demandé.</p>
                            </td>
                            <td>N/A</td>
                        </tr>";
}
$quoteHtml .= "
                    </tbody>
                </table>
            </div>

            <div class='total-section'>
                <table class='total-table'>
                    <tbody>
                        <tr>
                            <td class='label'>Sous-total HT:</td>
                            <td class='value'>{$prixHT_formatted} €</td>
                        </tr>
                        <tr>
                            <td class='label'>TVA (20%):</td>
                            <td class='value'>{$prixTVA_formatted} €</td>
                        </tr>
                        <tr class='grand-total'>
                            <td class='label'>Total TTC:</td>
                            <td class='value'>{$prixTTC_formatted} €</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class='footer'>
                <h2>Conditions générales</h2>
                <p>Vos données personnelles sont traitées conformément au Règlement Général sur la Protection des Données (RGPD). Elles sont utilisées uniquement pour le traitement de votre demande de devis et la gestion de la mission.</p>
                <p><strong>Nom de l'assureur :</strong> {$diagnostiqueurInfo['assureur']}<br>
                <strong>Numéro de police :</strong> {$diagnostiqueurInfo['police_assurance']}<br>
                <strong>Numéro de certification du diagnostiqueur :</strong> {$diagnostiqueurInfo['numero_certifie']}<br>
                <strong>Médiateur de la consommation :</strong> {$mediateurInfo['nom']} - {$mediateurInfo['site']}</p>
                
                <p style='margin-top: 50px;'><strong>Conditions de règlement :</strong> Paiement en ligne sécurisé après acceptation du devis. Un acompte peut être demandé pour valider la réservation.</p>
                
                <div class='signature'>
                    <p>Bon pour accord</p>
                    <p>Signature du client : _________________________</p>
                </div>
            </div>
        </div>
    </body>
    </html>
";

// Sauvegarde du devis sur le serveur
if (!is_dir('quotes')) {
    mkdir('quotes', 0777, true);
}
file_put_contents("quotes/{$quoteId}.html", $quoteHtml);

// Sauvegarde des données JSON pour l'acceptation ultérieure
$quoteData = [
    'quoteId' => $quoteId,
    'client' => $client,
    'diagnostiqueur' => $diagnostiqueurInfo,
    'dateRdv' => $dateRdv,
    'heureRdv' => $heureRdv,
    'adresse_bien' => $adresse_bien,
    'adresse_facturation' => $adresse_facturation,
    'diagnostics' => $diagnostics,
    'services' => $services,
    'prixTotal' => $prixTotalTTC,
    'prixHT' => $prixHT,
    'prixTVA' => $prixTVA,
    'prixTTC' => $prixTTC,
    'date_creation' => date('Y-m-d H:i:s'),
    'status' => 'pending',
];
file_put_contents("quotes/{$quoteId}.json", json_encode($quoteData, JSON_PRETTY_PRINT));

// Génération du lien de devis
$quoteLink = "http://customers.hubhabitats.com/view-quote.php?id={$quoteId}"; // Mettez votre nom de domaine ici

// Détermination du nom du client pour l'email
$clientNomEmail = ($client['type'] === 'particulier') ? ($client['nom'] ?? 'Client') : ($client['representantLegal'] ?? 'Client');

// Envoi de l'e-mail au client
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.titan.email';
$mail->SMTPAuth = true;
$mail->Username = 'support@fratgo.com';
$mail->Password = 'Ambission@2026';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;

$mail->setFrom('support@fratgo.com', 'Hubhabitats');
$mail->addAddress($client['email'], $clientNomEmail);
$mail->isHTML(true);
$mail->Subject = 'Votre devis de diagnostic immobilier';
$mail->Body = "Bonjour {$clientNomEmail},<br><br>
               Veuillez trouver votre devis en cliquant sur ce lien :<br>
               <a href='{$quoteLink}'>{$quoteLink}</a><br><br>
               Cordialement,<br>Votre équipe";

if ($mail->send()) {
    echo json_encode(['success' => true, 'message' => 'Devis généré et envoyé avec succès.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du mail: ' . $mail->ErrorInfo]);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération du devis : ' . $e->getMessage()]);
    error_log('Erreur génération devis: ' . $e->getMessage());
}
?>