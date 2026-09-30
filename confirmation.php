<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/helpers.php';
session_start();

if (empty($_SESSION['confirmation_data'])) {
    header('Location: index.php');
    exit;
}

$data = $_SESSION['confirmation_data'];
$smsSent = $data['sms_sent'] ?? false;

// Clear confirmation data
unset($_SESSION['confirmation_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geek Club - Merci !</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="scanlines grid-bg">

<div class="page-center">
  <div class="frm confirmation-card">

    <!-- THANK YOU MESSAGE -->
    <div class="thank-you-message">
      <div class="thank-you-icon">&#127881;</div>
      <h2 class="thank-you-title">Merci pour votre reservation !</h2>
      <p class="thank-you-subtitle">Votre aventure Geek Club commence bientot</p>
    </div>

    <!-- Success checkmark -->
    <div class="success-icon">&#10003;</div>

    <h1>Reservation confirmee</h1>

    <p class="fade-in-up" style="animation-delay:0.2s">
      Merci <strong><?= e($data['client_name']) ?></strong>, votre reservation
      n°<?= (int) $data['reservation_id'] ?> est confirmee.
    </p>

    <!-- Place photo -->
    <img src="<?= e($data['place_photo']) ?>" alt="<?= e($data['place_nom']) ?>" class="confirmation-photo" data-fallback>

    <!-- Reservation details -->
    <div class="summary-box fade-in-up" style="animation-delay:0.4s">
      <div style="text-align:center;margin-bottom:1rem;">
        <span style="font-size:1.2rem;font-weight:700;color:var(--primary-light);">Details de votre reservation</span>
      </div>
      <div style="display:grid;grid-template-columns:auto 1fr;gap:0.5rem 1rem;text-align:left;">
        <strong style="color:var(--primary-light);">Table :</strong>
        <span><?= e($data['place_nom']) ?></span>

        <strong style="color:var(--primary-light);">Club :</strong>
        <span><?= e($data['geekclub']) ?></span>

        <strong style="color:var(--primary-light);">Date :</strong>
        <span><?= e($data['dateres']) ?> a <?= e($data['heureres']) ?></span>

        <strong style="color:var(--primary-light);">Enfant :</strong>
        <span><?= e($data['nomenfant']) ?></span>

        <strong style="color:var(--primary-light);">Personnes :</strong>
        <span><?= (int) $data['nbpersone'] ?></span>

        <strong style="color:var(--primary-light);">Activites :</strong>
        <span><?= e($data['activite']) ?></span>

        <strong style="color:var(--primary-light);">Decoration :</strong>
        <span><?= e($data['deecoration']) ?></span>
      </div>
    </div>

    <!-- SMS notification status -->
    <?php if (!empty($data['client_phone'])): ?>
      <div class="fade-in-up" style="animation-delay:0.6s;margin-bottom:1.5rem;">
        <?php if ($smsSent): ?>
          <div class="alert alert-success" style="text-align:center;">
            &#128241; Un SMS de confirmation a ete envoye au <strong><?= e($data['client_phone']) ?></strong>
          </div>
        <?php else: ?>
          <div class="alert alert-error" style="text-align:center;">
            &#128241; Le SMS n'a pas pu etre envoye. Verifiez votre configuration CallMeBot.
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($data['client_email'])): ?>
      <p class="fade-in-up" style="color:var(--text-secondary);animation-delay:0.5s;margin-bottom:1.5rem;">
        Un email de confirmation a ete envoye a <strong><?= e($data['client_email']) ?></strong>.
      </p>
    <?php endif; ?>

    <a href="index.php" class="btn btn-primary fade-in-up" style="animation-delay:0.7s">Retour a l'accueil</a>
  </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
