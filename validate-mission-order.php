<?php
// Inclure les bibliothèques
require 'vendor/autoload.php';
require 'fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Données entrantes (ID de la mission et signature)
$data = json_decode(file_get_contents('php://input'), true);
$missionId = $data['missionId'] ?? '';
$signatureData = $data['signature'] ?? '';

if (empty($missionId) || empty($signatureData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    exit;
}

try {
    // 1. Charger les données de la mission
    $missionJsonPath = "mission-orders/{$missionId}.json";
    if (!file_exists($missionJsonPath)) {
        throw new Exception("Fichier de mission non trouvé.");
    }
    $missionData = json_decode(file_get_contents($missionJsonPath), true);
    $diagnostiqueurInfo = $missionData['diagnostiqueur'] ?? [];
    $clientInfo = $missionData['client'] ?? [];
    $additionalInfo = $missionData['additionalInfo'] ?? [];

    // 2. Enregistrer la signature en tant qu'image PNG
    $signaturePath = "mission-orders/{$missionId}_signature.png";
    $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
    file_put_contents($signaturePath, $signatureImage);

    // 3. Génération du PDF
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(0, 10, 'ORDRE DE MISSION', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Document a valeur juridique - A conserver', 0, 1, 'C');
            $this->Ln(10);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
        function SectionTitle($title) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetFillColor(230, 230, 230);
            $this->Cell(0, 8, $title, 0, 1, 'L', true);
            $this->Ln(2);
        }
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetMargins(20, 15, 20);

    // Identité du diagnostiqueur
    $pdf->SectionTitle('Identite du Diagnostiqueur');
    $pdf->Cell(0, 6, 'Denomination sociale: ' . ($diagnostiqueurInfo['denomination_sociale'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Nom de l\'entreprise: ' . ($diagnostiqueurInfo['nom_entreprise'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Siege social: ' . ($diagnostiqueurInfo['siege_social'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Capital social: ' . ($diagnostiqueurInfo['capital_social'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Numero de SIRET: ' . ($diagnostiqueurInfo['siret'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Code APE: ' . ($diagnostiqueurInfo['code_ape'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Numero de TVA: ' . ($diagnostiqueurInfo['tva'] ?? ''), 0, 1);
    $pdf->Ln(5);
    
    // Références et Certifications
    $pdf->SectionTitle('Informations et Certifications');
    $pdf->Cell(0, 6, 'Nom de l\'assureur: ' . ($diagnostiqueurInfo['assureur'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Police d\'assurance: ' . ($diagnostiqueurInfo['police_assurance'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Numero de Certifie: ' . ($diagnostiqueurInfo['numero_certifie'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Organisme de certification: ' . ($diagnostiqueurInfo['organisme_certification'] ?? ''), 0, 1);
    $pdf->Ln(2);
    $pdf->Cell(0, 6, 'Niveau de certifications:', 0, 1);
    $pdf->Cell(0, 6, '- DPE: ' . ($diagnostiqueurInfo['certification_dpe'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, '- Amiante: ' . ($diagnostiqueurInfo['certification_amiante'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Audit Énergétique: ' . ($diagnostiqueurInfo['audit_energetique'] ?? 'Non précisé'), 0, 1);
    $pdf->Cell(0, 6, 'Suspensions: ' . ($diagnostiqueurInfo['suspensions'] ?? 'Aucune'), 0, 1);
    $pdf->Cell(0, 6, 'Machine a plomb: ' . ($diagnostiqueurInfo['machine_a_plomb'] ?? 'Non précisé'), 0, 1);
    $pdf->Ln(5);

    // Coordonnées du client et du bien
    $pdf->SectionTitle('Informations du Client et du bien');
    $pdf->Cell(0, 6, 'Nom du client: ' . ($clientInfo['nom'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Coordonnees client: ' . ($clientInfo['email'] ?? '') . ' / ' . ($clientInfo['telephone'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Adresse du bien: ' . ($missionData['adresse_bien']['adresse'] ?? '') . ', ' . ($missionData['adresse_bien']['codePostal'] ?? '') . ' ' . ($missionData['adresse_bien']['ville'] ?? ''), 0, 1);
    if (!empty($additionalInfo)) {
        $pdf->Cell(0, 6, 'Informations complementaires:', 0, 1);
        foreach ($additionalInfo as $key => $value) {
            $pdf->Cell(0, 6, '  - ' . $key . ': ' . $value, 0, 1);
        }
    }
    $pdf->Ln(5);

    // Nature des diagnostics
    $pdf->SectionTitle('Nature des diagnostics');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'Diagnostics demandes:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    foreach ($missionData['diagnostics'] as $diag) {
        $pdf->Cell(0, 6, '- ' . $diag, 0, 1);
    }
    foreach ($missionData['services'] as $service) {
        $pdf->Cell(0, 6, '- ' . $service['name'] . ' (Service additionnel)', 0, 1);
    }
    $pdf->Cell(0, 6, 'Prix: ' . ($missionData['prixTotal'] ?? '') . ' EUR TTC (Conformement au devis accepte)', 0, 1);
    $pdf->Ln(5);
    
    // Clause RGPD
    $pdf->SectionTitle('Clause RGPD');
    $rgpdText = "Les informations collectees dans le cadre du present ordre de mission sont necessaires a la realisation des diagnostics immobiliers demandes. Elles font l’objet d’un traitement informatique par " . ($diagnostiqueurInfo['nom_entreprise'] ?? 'l\'entreprise') . ", responsable de traitement, afin d’executer la mission contractuelle et de repondre aux obligations legales. Ces donnees sont conservees pendant la duree legale de conservation des rapports (10 ans) et ne sont transmises qu’aux destinataires suivants : client, notaire, agence immobiliere, organisme certificateur et, le cas echeant, administrations concernees. Conformement au Reglement (UE) 2016/679 dit RGPD et a la loi Informatique et Libertes modifiee, vous disposez d’un droit d’acces, de rectification, d’effacement, de limitation et d’opposition au traitement de vos donnees. Vous pouvez exercer ces droits en ecrivant a : " . ($diagnostiqueurInfo['nom_entreprise'] ?? '') . " – " . ($diagnostiqueurInfo['siege_social'] ?? '') . " – " . ($diagnostiqueurInfo['email'] ?? ''). ". En cas de litige, vous pouvez saisir la CNIL (www.cnil.fr).";
    $pdf->MultiCell(0, 6, $rgpdText);
    $pdf->Ln(5);

    // Médiateur de la consommation
    $pdf->SectionTitle('Médiateur de la consommation');
    $pdf->Cell(0, 6, 'Nom du mediateur: ' . ($diagnostiqueurInfo['mediateur_nom'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Telephone: ' . ($diagnostiqueurInfo['mediateur_tel'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Adresse mail: ' . ($diagnostiqueurInfo['mediateur_email'] ?? ''), 0, 1);
    $pdf->Cell(0, 6, 'Site internet: ' . ($diagnostiqueurInfo['mediateur_site'] ?? ''), 0, 1);
    $pdf->Ln(15);
    
    // Signature
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Consentement et Signature du client', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Je soussigne(e), ' . ($clientInfo['nom'] ?? '') . ', reconnais avoir pris connaissance et accepte les termes et conditions de cet ordre de mission.', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->Image($signaturePath, 80, $pdf->GetY(), 50, 20);
    $pdf->SetY($pdf->GetY() + 25);
    $pdf->Cell(0, 6, 'Fait a ' . ($missionData['adresse_bien']['ville'] ?? '') . ', le ' . date('d/m/Y'), 0, 1, 'C');

    // Sauvegarde du PDF final
    $pdfPath = "mission-orders/{$missionId}.pdf";
    $pdf->Output('F', $pdfPath);
    
    // 4. Mise à jour des données de la mission
    $missionData['status'] = 'signed';
    $missionData['signature_date'] = date('Y-m-d H:i:s');
    file_put_contents($missionJsonPath, json_encode($missionData, JSON_PRETTY_PRINT));
    
    // 5. Envoi du mail avec le PDF en pièce jointe
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.titan.email';
    $mail->SMTPAuth = true;
    $mail->Username = 'support@fratgo.com';
    $mail->Password = 'Ambission@2026';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('support@fratgo.com', 'Fratgo Diagnostics');
    $mail->addAddress($clientInfo['email'] ?? 'support@fratgo.com', $clientInfo['nom'] ?? 'Client');
    $mail->addAddress($diagnostiqueurInfo['email'] ?? 'support@fratgo.com', $diagnostiqueurInfo['nom_entreprise'] ?? 'Diagnostiqueur');
    $mail->isHTML(true);
    $mail->Subject = 'Confirmation de votre Ordre de Mission';
    $mail->Body = "Bonjour,<br><br>Votre Ordre de Mission a ete signe avec succes. Vous le trouverez en piece jointe de cet e-mail.<br><br>Cordialement,<br>L'equipe Fratgo Diagnostics";
    $mail->addAttachment($pdfPath, 'Ordre_de_Mission_' . $missionId . '.pdf');

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Ordre de Mission signe et envoye par email.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    error_log('Erreur validation ordre de mission: ' . $e->getMessage());
}