<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
session_start();

if (empty($_SESSION['reservation_id'])) {
    header('Location: inscription.php');
    exit;
}

$error       = $_SESSION['form_error'] ?? null;
$place       = null;
$reservation = null;

unset($_SESSION['form_error']);

// If a place ID is selected, process reservation
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $placeId = (int) $_GET['id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, nom, source, statut FROM place WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $placeId]);
        $selectedPlace = $stmt->fetch();

        if (!$selectedPlace || $selectedPlace['statut'] !== 'libre') {
            $pdo->rollBack();
            $_SESSION['form_error'] = "Cette place vient d'etre reservee, merci d'en choisir une autre.";
            header('Location: choisir_place.php');
            exit;
        }

        $upd = $pdo->prepare('UPDATE reserver SET place = :place_id WHERE id = :id');
        $upd->execute([':place_id' => $placeId, ':id' => $_SESSION['reservation_id']]);

        $updPlace = $pdo->prepare("UPDATE place SET statut = 'reserve' WHERE id = :id");
        $updPlace->execute([':id' => $placeId]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['form_error'] = 'Une erreur est survenue, veuillez reessayer.';
        header('Location: choisir_place.php');
        exit;
    }

    $place = $selectedPlace;

    $stmt = $pdo->prepare(
        'SELECT r.*, p.nom AS place_nom, p.source AS place_source
         FROM reserver r LEFT JOIN place p ON p.id = r.place
         WHERE r.id = :id'
    );
    $stmt->execute([':id' => $_SESSION['reservation_id']]);
    $reservation = $stmt->fetch();

    $reservationId = $_SESSION['reservation_id'];
    $clientName    = $_SESSION['client_name'] ?? '';
    $clientEmail   = $_SESSION['client_email'] ?? '';
    $clientPhone   = $_SESSION['client_phone'] ?? '';
    $placePhoto    = fixImagePath($place['source']);

    // Send emails
    require_once __DIR__ . '/send_mail.php';
    try {
        notifyOwnerOfReservation($reservation ?: [], $place ?: []);
    } catch (Throwable $e) {}
    if ($clientEmail) {
        try {
            sendConfirmationEmail($clientEmail, $clientName, $reservationId);
        } catch (Throwable $e) {}
    }

    // Send WhatsApp SMS
    $smsSent = false;
    if (!empty($clientPhone)) {
        $message = sprintf(
            "Bonjour %s ! Votre reservation Geek Club n %d est confirmee.\n"
            . "Club : %s\n"
            . "Date : %s a %s\n"
            . "Enfant : %s\n"
            . "Personnes : %d\n"
            . "Activites : %s\n"
            . "Decoration : %s\n"
            . "Table : %s\n"
            . "Merci de votre confiance !",
            $clientName,
            $reservationId,
            $reservation['geekclub'] ?? '',
            $reservation['dateres'] ?? '',
            isset($reservation['heureres']) ? substr($reservation['heureres'], 0, 5) : '',
            $reservation['nomenfant'] ?? '',
            (int) ($reservation['nbpersone'] ?? 0),
            str_replace('-', ', ', $reservation['activite'] ?? ''),
            $reservation['deecoration'] ?? '',
            $place['nom']
        );

        $phone = preg_replace('/[^0-9]/', '', $clientPhone);
        if (strlen($phone) === 8) {
            $phone = '216' . $phone;
        }

        $apiKey = getenv('CALLMEBOT_APIKEY');
        if ($apiKey) {
            $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
                'phone'  => $phone,
                'text'   => $message,
                'apikey' => $apiKey,
            ]);
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $response = @file_get_contents($url, false, $ctx);
            $smsSent = ($response !== false && strpos($response, 'Message queued') !== false);
        }
    }

    // Save confirmation data
    $_SESSION['confirmation_data'] = [
        'reservation_id' => $reservationId,
        'client_name'    => $clientName,
        'client_email'   => $clientEmail,
        'client_phone'   => $clientPhone,
        'place_nom'      => $place['nom'],
        'place_photo'    => $placePhoto,
        'geekclub'       => $reservation['geekclub'] ?? '',
        'dateres'        => $reservation['dateres'] ?? '',
        'heureres'       => isset($reservation['heureres']) ? substr($reservation['heureres'], 0, 5) : '',
        'nomenfant'      => $reservation['nomenfant'] ?? '',
        'nbpersone'      => (int) ($reservation['nbpersone'] ?? 0),
        'activite'       => str_replace('-', ', ', $reservation['activite'] ?? ''),
        'deecoration'    => $reservation['deecoration'] ?? '',
        'sms_sent'       => $smsSent,
    ];

    // Clean sessions
    unset(
        $_SESSION['reservation_id'],
        $_SESSION['client_id'],
        $_SESSION['pack_id'],
        $_SESSION['selected_dateres'],
        $_SESSION['client_name'],
        $_SESSION['client_email'],
        $_SESSION['client_phone']
    );

    header('Location: confirmation.php');
    exit;
}

// Show available places
$places = [];
$stmt = $pdo->query("SELECT id, nom, source, statut, type FROM place WHERE statut = 'libre' ORDER BY type, id");
$places = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geek Club - Choisir une place</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .places-section-title {
      font-size: 1.4rem;
      color: var(--primary-light);
      margin: 2rem 0 1rem;
      text-align: center;
      position: relative;
    }
    .places-section-title::after {
      content: '';
      display: block;
      width: 60px;
      height: 3px;
      background: var(--gradient-warm);
      margin: 0.5rem auto 0;
      border-radius: 2px;
    }
    .places-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .place-card {
      background: var(--surface);
      border-radius: 16px;
      overflow: hidden;
      border: 2px solid var(--border);
      transition: all 0.3s ease;
      text-decoration: none;
      color: inherit;
    }
    .place-card:hover {
      transform: translateY(-8px);
      border-color: var(--primary);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .place-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      display: block;
    }
    .place-card-body {
      padding: 1rem;
      text-align: center;
    }
    .place-card-body h3 {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
      color: var(--text);
    }
    .place-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
    }
    .place-badge.laser {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }
    .place-badge.standard {
      background: rgba(34, 197, 94, 0.2);
      color: #22c55e;
    }
    .place-card .btn {
      width: 100%;
      margin-top: 0.5rem;
    }
    .laser-section {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
      border-radius: 20px;
      padding: 1rem 0 2rem;
      margin: 2rem auto;
      max-width: 1200px;
    }
    .laser-icon {
      font-size: 2rem;
      margin-right: 0.5rem;
    }
  </style>
</head>
<body class="scanlines grid-bg">

  <header class="site-header">
    <div class="logo"><img src="img/logo.png" alt="Geek Club" data-fallback></div>
  </header>

  <main style="padding-top:100px;padding-bottom:4rem;">
    <h1 class="section-title reveal">Choisissez votre table</h1>

    <?php if ($error): ?>
      <p class="alert alert-error" style="max-width:500px;margin:0 auto 2rem;text-align:center;"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (!$places): ?>
      <p class="alert alert-error" style="max-width:500px;margin:0 auto;text-align:center;">
        Desole, aucune place n'est disponible actuellement. Merci de nous contacter directement.
      </p>
    <?php else: ?>

      <!-- LASER TAG SECTION -->
      <div class="laser-section">
        <h2 class="places-section-title"><span class="laser-icon">&#128299;</span> Laser Tag</h2>
        <div class="places-grid">
          <?php
          $laserPlaces = array_filter($places, fn($p) => ($p['type'] ?? 'standard') === 'laser');
          foreach ($laserPlaces as $i => $p):
          ?>
            <div class="place-card tilt-card reveal" style="animation-delay: <?= $i * 0.12 ?>s">
              <img src="<?= e(fixImagePath($p['source'])) ?>" alt="<?= e($p['nom']) ?>" data-fallback loading="lazy">
              <div class="place-card-body">
                <span class="place-badge laser">&#128299; LASER TAG</span>
                <h3><?= e($p['nom']) ?></h3>
                <a href="choisir_place.php?id=<?= (int) $p['id'] ?>" class="btn btn-primary">Reserver</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- STANDARD TABLES SECTION -->
      <h2 class="places-section-title">&#127918; Tables Standard</h2>
      <div class="places-grid">
        <?php
        $standardPlaces = array_filter($places, fn($p) => ($p['type'] ?? 'standard') !== 'laser');
        foreach ($standardPlaces as $i => $p):
        ?>
          <div class="place-card tilt-card reveal" style="animation-delay: <?= $i * 0.12 ?>s">
            <img src="<?= e(fixImagePath($p['source'])) ?>" alt="<?= e($p['nom']) ?>" data-fallback loading="lazy">
            <div class="place-card-body">
              <span class="place-badge standard">&#9989; DISPONIBLE</span>
              <h3><?= e($p['nom']) ?></h3>
              <a href="choisir_place.php?id=<?= (int) $p['id'] ?>" class="btn btn-primary">Reserver cette table</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

<script src="assets/js/main.js"></script>
</body>
</html>
