<?php
$missionId = $_GET['id'] ?? '';

if (empty($missionId) || !file_exists("mission-orders/{$missionId}.json")) {
    http_response_code(404);
    echo "<h1>Erreur 404 - Ordre de Mission non trouvé.</h1>";
    exit;
}

$missionData = json_decode(file_get_contents("mission-orders/{$missionId}.json"), true);
$diagnostiqueurInfo = $missionData['diagnostiqueur'] ?? [];
$clientInfo = $missionData['client'] ?? [];
$adresseBien = $missionData['adresse_bien'] ?? [];
$prixTotal = $missionData['prixTotal'] ?? 'non spécifié';
$diagnostics = $missionData['diagnostics'] ?? [];
$services = $missionData['services'] ?? [];

$diagnosticsList = implode(', ', array_merge($diagnostics, array_column($services, 'name')));

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Mission - Signature</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 20px; background-color: #f4f4f9; color: #333; }
        .om-container { max-width: 800px; margin: 20px auto; padding: 40px; background-color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; }
        h1 { color: #004d99; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #004d99; padding-bottom: 10px; }
        h2 { color: #004d99; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 30px; }
        p, ul { line-height: 1.6; }
        ul { padding-left: 20px; }
        li strong { color: #004d99; }
        .info-box { border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-top: 15px; }
        .signature-section { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 2px dashed #ddd; }
        .signature-pad-container { border: 2px solid #ccc; border-radius: 5px; margin: 20px auto; width: 300px; height: 150px; background-color: #fafafa; }
        #signaturePad { width: 100%; height: 100%; cursor: crosshair; }
        .buttons { margin-top: 10px; }
        .buttons button { padding: 10px 20px; margin: 0 5px; cursor: pointer; border: none; border-radius: 5px; font-size: 1em; }
        #clear { background-color: #f44336; color: white; }
        #submit { background-color: #4CAF50; color: white; }
        .response-message { margin-top: 20px; padding: 10px; border-radius: 5px; }
        .response-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .response-error { background-color: #ffebee; color: #c62828; border: 1px solid #f44336; }
    </style>
</head>
<body>

<div class="om-container">
    <h1>Ordre de Mission</h1>
    <p>Ce document est un ordre de mission ayant valeur de contrat, établi entre l'entreprise et le client.</p>

    <h2>1. Identité du Diagnostiqueur</h2>
    <div class="info-box">
        <p><strong>Dénomination sociale:</strong> <?= htmlspecialchars($diagnostiqueurInfo['denomination_sociale'] ?? '') ?></p>
        <p><strong>Siège social:</strong> <?= htmlspecialchars($diagnostiqueurInfo['siege_social'] ?? '') ?></p>
        <p><strong>SIRET:</strong> <?= htmlspecialchars($diagnostiqueurInfo['siret'] ?? '') ?></p>
        <p><strong>Assureur:</strong> <?= htmlspecialchars($diagnostiqueurInfo['assureur'] ?? '') ?> - <strong>Police:</strong> <?= htmlspecialchars($diagnostiqueurInfo['police_assurance'] ?? '') ?></p>
        <p><strong>Numéro de certifié:</strong> <?= htmlspecialchars($diagnostiqueurInfo['numero_certifie'] ?? '') ?> - <strong>Organisme:</strong> <?= htmlspecialchars($diagnostiqueurInfo['organisme_certification'] ?? '') ?></p>
    </div>

    <h2>2. Coordonnées du Client et du Bien</h2>
    <div class="info-box">
        <p><strong>Nom du client:</strong> <?= htmlspecialchars($clientInfo['nom'] ?? '') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($clientInfo['email'] ?? '') ?></p>
        <p><strong>Téléphone:</strong> <?= htmlspecialchars($clientInfo['telephone'] ?? '') ?></p>
        <p><strong>Adresse du bien:</strong> <?= htmlspecialchars($adresseBien['adresse'] ?? '') . ', ' . htmlspecialchars($adresseBien['codePostal'] ?? '') . ' ' . htmlspecialchars($adresseBien['ville'] ?? '') ?></p>
        <p><strong>Date de la mission:</strong> <?= htmlspecialchars($missionData['date'] ?? '') ?></p>
    </div>

    <h2>3. Nature des Diagnostics et Coût de la Mission</h2>
    <div class="info-box">
        <p><strong>Diagnostics demandés:</strong> <?= htmlspecialchars($diagnosticsList) ?></p>
        <p><strong>Prix total TTC:</strong> <?= htmlspecialchars(number_format($prixTotal, 2, ',', ' ')) ?> €</p>
        <p style="font-style: italic;">(Ce prix est conforme au devis accepté le <?= htmlspecialchars(date('d/m/Y', strtotime($missionData['date_creation'] ?? ''))) ?>)</p>
    </div>
    
    <h2>4. Conditions Générales et Clauses Légales</h2>
    <h3>Médiateur de la Consommation</h3>
    <div class="info-box">
        <p><strong>Nom du médiateur:</strong> <?= htmlspecialchars($diagnostiqueurInfo['mediateur_nom'] ?? '') ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($diagnostiqueurInfo['mediateur_email'] ?? '') . ' / ' . htmlspecialchars($diagnostiqueurInfo['mediateur_tel'] ?? '') ?></p>
        <p><strong>Site web:</strong> <a href="<?= htmlspecialchars($diagnostiqueurInfo['mediateur_site'] ?? '#') ?>"><?= htmlspecialchars($diagnostiqueurInfo['mediateur_site'] ?? '') ?></a></p>
    </div>

    <h3>Clause RGPD</h3>
    <div class="info-box">
        <p>Les informations collectées dans le cadre du présent ordre de mission sont nécessaires à la réalisation des diagnostics immobiliers demandés. Elles font l’objet d’un traitement informatique par <strong><?= htmlspecialchars($diagnostiqueurInfo['nom_entreprise'] ?? '') ?></strong>, responsable de traitement, afin d’exécuter la mission contractuelle et de répondre aux obligations légales.</p>
        <p>Ces données sont conservées pendant la durée légale de conservation des rapports (10 ans) et ne sont transmises qu’aux destinataires suivants : client, notaire, agence immobilière, organisme certificateur et, le cas échéant, administrations concernées. Conformément au Règlement (UE) 2016/679 dit RGPD et à la loi Informatique et Libertés modifiée, vous disposez d’un droit d’accès, de rectification, d’effacement, de limitation et d’opposition au traitement de vos données. Vous pouvez exercer ces droits en écrivant à : **<?= htmlspecialchars($diagnostiqueurInfo['nom_entreprise'] ?? '') ?>** – **<?= htmlspecialchars($diagnostiqueurInfo['siege_social'] ?? '') ?>** – **<?= htmlspecialchars($diagnostiqueurInfo['email'] ?? '') ?>**. En cas de litige, vous pouvez saisir la CNIL (www.cnil.fr).</p>
    </div>
    
    <div class="signature-section">
        <h2>Consentement et Signature du client</h2>
        <p>En signant, vous reconnaissez avoir pris connaissance et accepté les termes de cet ordre de mission.</p>
        <div class="signature-pad-container">
            <canvas id="signaturePad"></canvas>
        </div>
        <div class="buttons">
            <button id="clear">Effacer</button>
            <button id="submit">Valider et Signer</button>
        </div>
        <p id="responseMessage" class="response-message"></p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signaturePad');
    const signaturePad = new SignaturePad(canvas);
    const clearButton = document.getElementById('clear');
    const submitButton = document.getElementById('submit');
    const responseMessage = document.getElementById('responseMessage');

    // Mettre à l'échelle le canvas pour une meilleure qualité
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear(); // Réinitialise la signature après le redimensionnement
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    clearButton.addEventListener('click', () => {
        signaturePad.clear();
        responseMessage.textContent = '';
        responseMessage.className = 'response-message';
    });

    submitButton.addEventListener('click', () => {
        if (signaturePad.isEmpty()) {
            responseMessage.textContent = 'Veuillez apposer votre signature avant de valider.';
            responseMessage.className = 'response-message response-error';
            return;
        }

        const dataURL = signaturePad.toDataURL('image/png');
        const missionId = '<?= htmlspecialchars($missionId) ?>';

        fetch('validate-mission-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                missionId: missionId,
                signature: dataURL
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                responseMessage.textContent = 'Signature validée ! L\'ordre de mission vous sera envoyé par e-mail.';
                responseMessage.className = 'response-message response-success';
                submitButton.disabled = true;
                clearButton.disabled = true;
            } else {
                responseMessage.textContent = data.message;
                responseMessage.className = 'response-message response-error';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            responseMessage.textContent = 'Une erreur est survenue lors de l\'envoi de la signature.';
            responseMessage.className = 'response-message response-error';
        });
    });
});
</script>

</body>
</html>