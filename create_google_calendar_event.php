<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Oauth2;

/**
 * Gère l'authentification OAuth 2.0 pour obtenir le jeton d'accès.
 *
 * @return array|string Les données du jeton ou un message d'erreur.
 */
function get_google_access_token() {
    $client = new Client();
    
    $client->setAuthConfig('client_secret.json'); 
    $client->setRedirectUri('https://customers.hubhabitats.com/diag2france/create_google_calendar_event.php'); 
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->addScope(Calendar::CALENDAR_EVENTS);
    $client->addScope(Oauth2::USERINFO_EMAIL); // Ajout d'un scope pour l'email
    
    $tokenPath = 'token.json';

    if (!file_exists($tokenPath)) {
        if (!isset($_GET['code'])) {
            $authUrl = $client->createAuthUrl();
            header("Location: " . $authUrl);
            exit;
        } else {
            $authCode = $_GET['code'];
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            file_put_contents($tokenPath, json_encode($accessToken));
            return "Le token de rafraîchissement a été généré avec succès. Vous pouvez maintenant utiliser ce script pour créer des événements.";
        }
    }

    return json_decode(file_get_contents($tokenPath), true);
}

// --- Logique principale pour la création d'événements ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        exit;
    }

    try {
        $client = new Client();
        $client->setAuthConfig('client_secret.json');
        $client->setScopes([Calendar::CALENDAR_EVENTS, Oauth2::USERINFO_EMAIL]);

        $accessToken = get_google_access_token();

        if (is_string($accessToken)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $accessToken]);
            exit;
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents('token.json', json_encode($client->getAccessToken()));
            } else {
                throw new Exception("Le jeton de rafraîchissement a expiré. Ré-exécutez le script manuellement pour le générer à nouveau.");
            }
        }
        
        $service = new Google_Service_Calendar($client);
        $calendarId = 'primary'; // L'ID du calendrier principal de l'utilisateur.

        // CORRECTION DE L'ERREUR : Récupère l'email de l'utilisateur via le service OAuth2
        $oauth2Service = new Oauth2($client);
        $userInfo = $oauth2Service->userinfo->get();
        $diagnostiqueurEmail = $userInfo->getEmail();

        $date = $data['date'];
        $time = $data['heure'];
        $clientName = $data['client']['nom'];
        $clientEmail = $data['client']['email'];
        $diagnosticsList = implode(', ', $data['diagnostics']);
        $fullAddress = $data['adresseFacturation']['adresse'] . ', ' . $data['adresseFacturation']['ville'] . ', ' . $data['adresseFacturation']['codePostal'];

        $startDateTime = new DateTimeImmutable("{$date} {$time}");
        $endDateTime = $startDateTime->add(new DateInterval('PT1H30M'));

        $description = "Mission de diagnostic pour {$clientName}\n\n"
                     . "Diagnostics : {$diagnosticsList}\n"
                     . "Contact client : {$clientEmail}\n"
                     . "Adresse : {$fullAddress}";

        $attendees = [];
        if (!empty($clientEmail)) {
            $attendees[] = new Google_Service_Calendar_EventAttendee(['email' => $clientEmail]);
        }
        if ($diagnostiqueurEmail) {
            $attendees[] = new Google_Service_Calendar_EventAttendee(['email' => $diagnostiqueurEmail]);
        }

        $event = new Google_Service_Calendar_Event([
            'summary' => "Rendez-vous de diagnostic avec {$clientName}",
            'location' => $fullAddress,
            'description' => $description,
            'start' => ['dateTime' => $startDateTime->format(DateTime::ATOM), 'timeZone' => 'Europe/Paris'],
            'end' => ['dateTime' => $endDateTime->format(DateTime::ATOM), 'timeZone' => 'Europe/Paris'],
            'attendees' => $attendees,
        ]);

        $createdEvent = $service->events->insert($calendarId, $event);

        echo json_encode(['success' => true, 'message' => 'Événement créé.']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        error_log('Erreur Google Calendar API: ' . $e->getMessage());
    }
} else {
    $response = get_google_access_token();
    if (is_string($response)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $response]);
    }
}
?>