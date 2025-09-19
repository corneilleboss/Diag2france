<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Sécurité à ajuster en production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fonction pour se connecter à la base de données
function getDbConnection() {
    $servername = "localhost";
    $username = "root"; 
    $password = "root";
    $dbname = "hubhabitat";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        http_response_code(500);
        die(json_encode(["error" => "Erreur de connexion à la base de données: " . $e->getMessage()]));
    }
}

// Récupérer les données de la requête POST
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

switch ($action) {
    case 'get_diagnostics_and_prices':
        // Logique pour déterminer les diagnostics et les prix...
        // Cette partie semble déjà fonctionner, donc je la laisse intacte
        $anneeConstruction = $data['anneeConstruction'] ?? null;
        $gazInstallation = $data['gazInstallation'] ?? null;
        $elecInstallation = $data['elecInstallation'] ?? null;
        $copropriete = $data['copropriete'] ?? null;
        $inseeCode = $data['inseeCode'] ?? null;
        $surface = $data['surface'] ?? null;
        
        $diagnostics = [];
        $tarifs = [
            'moins-20' => ['normal' => [110, 140, 170, 200, 230, 265, 290]],
            'moins-40' => ['normal' => [130, 165, 200, 235, 270, 305, 340]],
            'moins-60' => ['normal' => [155, 195, 235, 275, 315, 355, 395], 'promo' => 550],
            'moins-80' => ['normal' => [185, 230, 275, 320, 365, 410, 455], 'promo' => 250],
            'moins-100' => ['normal' => [220, 270, 320, 370, 420, 470, 520], 'promo' => 470],
            'moins-120' => ['normal' => [260, 315, 370, 425, 480, 535, 590], 'promo' => 535],
            'moins-140' => ['normal' => [295, 330, 395, 450, 505, 575, 625], 'promo' => 355],
            'moins-160' => ['normal' => [335, 355, 420, 485, 550, 615, 680]],
            'moins-180' => ['normal' => [360, 405, 475, 545, 615, 685, 755], 'promo' => 2160],
            'moins-200' => ['normal' => [405, 455, 530, 605, 680, 755, 830]],
            'moins-220' => ['normal' => [455, 510, 585, 660, 735, 810, 885]],
        ];
        
        if ($anneeConstruction === 'avant-1949') {
            $diagnostics = ['DPE', 'AMIANTE', 'PLOMB', 'CARREZE BOUTIN', 'ERP'];
        } else if ($anneeConstruction === 'apres-1949') {
            $diagnostics = ['DPE', 'AMIANTE', 'ERP', 'THERMITES', 'CARREZE BOUTIN'];
        } else if ($anneeConstruction === 'apres-1997') {
            $diagnostics = ['DPE', 'ERP', 'THERMITES', 'CARREZE BOUTIN'];
        }
        
        if ($gazInstallation === 'plus-15ans') {
            $diagnostics[] = 'GAZ';
        }
        if ($elecInstallation === 'plus-15ans') {
            $diagnostics[] = 'ELECTRICITE';
        }
        if ($copropriete === 'Oui') {
            $diagnostics[] = 'CARREZE BOUTIN';
        }

        $isTermiteZone = ($inseeCode === '75001' || $inseeCode === '33063'); 
        if ($isTermiteZone) {
            $diagnostics[] = 'TERMITES';
        }

        $diagnostics = array_values(array_unique($diagnostics));
        
        $prixBaseNormal = 0;
        $prixBaseFinal = 0;
        $checkedDiagnosticsCount = count($diagnostics);

        if (isset($tarifs[$surface]) && $checkedDiagnosticsCount >= 2) {
            $prixList = $tarifs[$surface]['normal'];
            
            if (($checkedDiagnosticsCount - 2) < count($prixList)) {
                $prixBaseNormal = $prixList[$checkedDiagnosticsCount - 2];
            }
            if (($checkedDiagnosticsCount == count($prixList) + 1) && isset($tarifs[$surface]['promo'])) {
                $prixBaseFinal = $tarifs[$surface]['promo'];
            } else {
                $prixBaseFinal = $prixBaseNormal;
            }
        }
        
        echo json_encode([
            "diagnostics" => $diagnostics,
            "prixNormal" => $prixBaseNormal,
            "prixFinal" => $prixBaseFinal
        ]);
        break;

    case 'save_mission':
        $conn = getDbConnection();
        $conn->beginTransaction();

        try {
            // Vérification des données
            if (!isset($data['client']) || !isset($data['bien']) || !isset($data['diagnostics']) || !isset($data['prixTotal'])) {
                throw new Exception("Données de mission incomplètes.");
            }

            // Extraction et validation des données client
            $client = $data['client'];
            if (empty($client['nom']) || empty($client['prenom']) || empty($client['email']) || empty($client['telephone'])) {
                throw new Exception("Informations client manquantes.");
            }

            // Insertion du client
            $stmt = $conn->prepare("INSERT INTO Clients (nom, prenom, email, telephone) VALUES (:nom, :prenom, :email, :telephone)");
            $stmt->execute([
                'nom' => $client['nom'],
                'prenom' => $client['prenom'],
                'email' => $client['email'],
                'telephone' => $client['telephone']
            ]);
            $clientId = $conn->lastInsertId();

            // Extraction et validation des données du bien
            $bien = $data['bien'];
            if (empty($bien['adresse']) || empty($bien['surface']) || empty($bien['anneeConstruction']) || empty($bien['typeBien']) || empty($bien['transactionType'])) {
                throw new Exception("Informations du bien manquantes.");
            }

            // Insertion de la mission
            $stmt = $conn->prepare("INSERT INTO Missions (adresse_bien, superficie, annee_construction, type_bien, transaction_type, client_id, prix_total) VALUES (:adresse_bien, :superficie, :annee_construction, :type_bien, :transaction_type, :client_id, :prix_total)");
            $stmt->execute([
                'adresse_bien' => $bien['adresse'],
                'superficie' => $bien['surface'],
                'annee_construction' => $bien['anneeConstruction'],
                'type_bien' => $bien['typeBien'],
                'transaction_type' => $bien['transactionType'],
                'client_id' => $clientId,
                'prix_total' => $data['prixTotal']
            ]);
            $missionId = $conn->lastInsertId();

            // Insertion des diagnostics
            $diagnostics = $data['diagnostics'];
            if (!is_array($diagnostics) || empty($diagnostics)) {
                 throw new Exception("Aucun diagnostic à enregistrer.");
            }
            $stmt = $conn->prepare("INSERT INTO Services_Missions (mission_id, service_nom) VALUES (:mission_id, :service_nom)");
            foreach ($diagnostics as $diagnostic) {
                $stmt->execute([
                    'mission_id' => $missionId,
                    'service_nom' => $diagnostic
                ]);
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Mission enregistrée avec succès.", "mission_id" => $missionId]);

        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Erreur lors de l'enregistrement de la mission: " . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Action non valide."]);
        break;
}
?>