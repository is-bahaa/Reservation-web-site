<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
session_start();

if (empty($_SESSION['client_id'])) {
    header('Location: inscription.php');
    exit;
}
$error = $_SESSION['form_error'] ?? null;
unset($_SESSION['form_error']);

$pack = null;
$isAnniversaire = false;
$packActivities = [];

if (!empty($_SESSION['pack_id'])) {
    $stmt = $pdo->prepare('SELECT id, nom, prix, activites, duree FROM pack WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['pack_id']]);
    $pack = $stmt->fetch() ?: null;
    if ($pack) {
        // ONLY show decoration for Pack Anniversaire (id 4)
        $isAnniversaire = (stripos($pack['nom'], 'Anniversaire') !== false)
                       || $pack['id'] === 4;
        // Parse activities from pack
        $packActivities = array_filter(explode('-', $pack['activites'] ?? ''));
    }
}
$packDuree = $pack ? (int) $pack['duree'] : 3;

$selectedDate = $_SESSION['selected_dateres'] ?? date('Y-m-d');

// All available activities
$allActivities = [
    'ps5'   => ['icon' => '&#127918;', 'label' => 'PS5 Gaming'],
    'parc'  => ['icon' => '&#127906;', 'label' => 'Parc Aventure'],
    'laser' => ['icon' => '&#128299;', 'label' => 'Laser Tag'],
    'vr'    => ['icon' => '&#129302;', 'label' => 'Realite Virtuelle'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geek Club — Reservation</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .custom-deco-container {
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease 0.1s, padding 0.3s ease;
      padding: 0 0.5rem;
    }
    .custom-deco-container.show {
      max-height: 300px;
      opacity: 1;
      padding: 0.5rem;
    }
    .custom-deco-container textarea {
      width: 100%;
      min-height: 120px;
      padding: 1rem;
      border: 2px solid var(--border);
      border-radius: 12px;
      background: var(--surface);
      color: var(--text);
      font-family: inherit;
      font-size: 1rem;
      resize: vertical;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      outline: none;
    }
    .custom-deco-container textarea:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
    }
    .custom-deco-container label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--primary-light);
      font-weight: 600;
    }
    .date-display {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      text-align: center;
      color: var(--primary-light);
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .hidden-section {
      display: none !important;
    }
  </style>
</head>
<body class="scanlines grid-bg">

<div class="page-center">
  <div class="frm fade-in-scale">
    <div class="logo">
      <img src="img/logo.png" alt="Geek Club" data-fallback>
      <h1>Reservation</h1>
    </div>

    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

    <p class="welcome-text">Bienvenue, <strong><?= e($_SESSION['client_name']) ?></strong> ! &#127881;</p>

    <?php if ($pack): ?>
      <div class="summary-box">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
          <span>Pack choisi : <strong><?= e($pack['nom']) ?></strong> (<?= $packDuree ?>h)</span>
          <span style="font-weight:800;background:var(--gradient-warm);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
            <?= number_format((float) $pack['prix'], 2) ?> DT
          </span>
        </div>
      </div>
    <?php endif; ?>

    <div class="date-display">
      &#128197; Date choisie : <?= e(date('d/m/Y', strtotime($selectedDate))) ?>
    </div>

    <form action="traitement_reservation.php" method="POST" id="reservationForm" novalidate>

      <input type="hidden" name="dateres" value="<?= e($selectedDate) ?>">
      <?php
      // Validate date is not in the past
      $today = date('Y-m-d');
      if ($selectedDate < $today) {
          $_SESSION['form_error'] = 'La date de reservation ne peut pas etre dans le passe.';
          header('Location: inscription.php');
          exit;
      }
      ?>
      <!-- Activities auto-filled from pack -->
      <?php foreach ($packActivities as $act): ?>
      <input type="hidden" name="activite[]" value="<?= e($act) ?>">
      <?php endforeach; ?>

      <div class="input_grp">
        <label for="club">Geek Club</label>
        <select name="club" id="club">
          <option value="CITE NASIR">CITE NASIR</option>
          <option value="GAMMARTH">GAMMARTH</option>
          <option value="LA MARSA">LA MARSA</option>
          <option value="SOUSSE">SOUSSE</option>
          <option value="SFAX">SFAX</option>
        </select>
      </div>

      <div class="input_grp">
        <label for="nomenfant">Nom de l'enfant</label>
        <input type="text" name="nameef" id="nomenfant" placeholder="Prenom de l'enfant" required maxlength="50">
      </div>

      <div class="input_grp">
        <label for="heurres">Heure de reservation</label>
        <input type="time" name="heurres" id="heurres" required min="10:00" max="23:59" value="10:00">
        <span class="field-hint">De 10h00 a 23h59 (duree : <?= $packDuree ?>h)</span>
      </div>

      <div class="input_grp">
        <label for="dateaniv">Date d'anniversaire</label>
        <input type="date" name="dateaniv" id="dateaniv" required min="2000-01-01" max="<?= date('Y-m-d') ?>">
        <span class="field-hint">Date de naissance de l'enfant</span>
      </div>

      <div class="input_grp">
        <label for="nbpersone">Nombre de personnes</label>
        <input type="number" name="nbpersone" id="nbpersone" placeholder="5" required min="1" max="99">
      </div>

      <!-- DECORATION - Only for Pack Anniversaire -->
      <fieldset id="decoSection" class="<?= $isAnniversaire ? '' : 'hidden-section' ?>">
        <legend>Decoration</legend>
        <label class="radio-label" for="decoStandard">
          <input type="radio" name="deco" value="standard" checked id="decoStandard" onchange="toggleCustomDeco()"> &#10024; STANDARD
        </label>
        <label class="radio-label" for="decoPerso">
          <input type="radio" name="deco" value="personnel" id="decoPerso" onchange="toggleCustomDeco()"> &#127912; PERSONNALISEE
        </label>

        <div class="custom-deco-container" id="customDecoContainer">
          <label for="customDecoration">Decrivez votre decoration personnalisee :</label>
          <textarea name="customDecoration" id="customDecoration" placeholder="Ex: Theme Spiderman, couleurs rouge et bleu, ballons, banderoles..."></textarea>
        </div>
      </fieldset>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem;">Suivant &rarr;</button>
    </form>
  </div>
</div>

<script>
function toggleCustomDeco() {
  const container = document.getElementById('customDecoContainer');
  const isPerso = document.getElementById('decoPerso').checked;
  if (isPerso) {
    container.classList.add('show');
    setTimeout(() => { document.getElementById('customDecoration').focus(); }, 300);
  } else {
    container.classList.remove('show');
  }
}

document.getElementById('reservationForm').addEventListener('submit', function(e) {
  const decoSection = document.getElementById('decoSection');
  if (decoSection && getComputedStyle(decoSection).display !== 'none') {
    const isPerso = document.getElementById('decoPerso').checked;
    if (isPerso) {
      const customDeco = document.getElementById('customDecoration').value.trim();
      if (!customDeco) {
        e.preventDefault();
        alert('Veuillez decrire votre decoration personnalisee.');
        document.getElementById('customDecoration').focus();
        return false;
      }
    }
  }
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
