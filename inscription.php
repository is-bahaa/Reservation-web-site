<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
session_start();

$error = $_SESSION['form_error'] ?? null;
unset($_SESSION['form_error']);

if (isset($_GET['pack']) && ctype_digit($_GET['pack'])) {
    $_SESSION['pack_id'] = (int) $_GET['pack'];
}

$packs = [];
try {
    $packs = $pdo->query('SELECT id, nom, description, prix, image, duree FROM pack WHERE actif = 1 ORDER BY id')->fetchAll();
} catch (PDOException $e) {
    $packs = [];
}

$packId = $_SESSION['pack_id'] ?? null;

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$monthName = date('F Y');

$packImageMap = [
    'Pack PS5'          => 'img/pack-ps5.jpg',
    'Pack Parc'         => 'img/pack-parc.jpg',
    'Pack Laser'        => 'img/pack-full.jpg',
    'Pack Laser Tag'    => 'img/pack-full.jpg',
    'Pack Anniversaire' => 'img/neon10.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geek Club — Inscription</title>
  <link rel="stylesheet" href="assets/css/style.css">
<style>
  /* === PACK CARDS: SAME LINE GRID LIKE INDEX === */
  .pack-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-top: 0.5rem;
  }
  .pack-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--surface);
    border: 2px solid var(--border);
    border-radius: 16px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
  }
  .pack-option:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }
  .pack-option.selected {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3), 0 10px 30px rgba(0,0,0,0.2);
  }
  .pack-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }
  .pack-option img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 0.75rem;
  }
  .pack-option h4 {
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    color: var(--text);
  }
  .pack-option .pack-price {
    font-weight: 700;
    font-size: 1.1rem;
    background: var(--gradient-warm);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.25rem;
  }
  .pack-option .pack-duration {
    font-size: 0.8rem;
    color: var(--text-muted);
  }
  @media (max-width: 480px) {
    .pack-selection {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }
    .pack-option {
      padding: 0.75rem;
    }
    .pack-option img {
      height: 80px;
    }
    .pack-option h4 {
      font-size: 0.85rem;
    }
  }
</style>
</head>
<body class="scanlines grid-bg">

<div class="page-center">
  <div class="frm fade-in-scale">
    <div class="logo">
      <img src="img/logo.png" alt="Geek Club" data-fallback>
      <h1>Inscription</h1>
    </div>

    <?php if ($error): ?>
      <p class="alert alert-error"><?= e($error) ?></p>
    <?php endif; ?>

    <form action="traitement_client.php" method="POST" id="inscriptionForm" novalidate>

      <div class="input_group">
        <label>Choisissez votre Pack</label>
        <div class="pack-selection">
          <?php foreach ($packs as $pack):
            $packImg = $packImageMap[$pack['nom']] ?? fixImagePath($pack['image']);
          ?>
            <label class="pack-option <?= ($packId == $pack['id']) ? 'selected' : '' ?>" data-pack-id="<?= (int) $pack['id'] ?>">
              <input type="radio" name="pack_id" value="<?= (int) $pack['id'] ?>" <?= ($packId == $pack['id']) ? 'checked' : '' ?> required>
              <img src="<?= e($packImg) ?>" alt="<?= e($pack['nom']) ?>" data-fallback>
              <h4><?= e($pack['nom']) ?></h4>
              <span class="pack-price"><?= number_format((float) $pack['prix'], 2) ?> DT</span>
              <div class="pack-duration">&#9201; <?= (int) $pack['duree'] ?>h</div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="input_group">
        <label>Date de reservation (<?= e($monthName) ?>)</label>
        <div class="month-header"><?= e($monthName) ?></div>
        <div class="date-picker-grid" id="datePicker">
          <?php
          $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
          foreach ($days as $d): ?>
            <div class="day-label"><?= e($d) ?></div>
          <?php endforeach; ?>

          <?php
          $firstDayOfMonth = date('N', strtotime("$currentYear-$currentMonth-01"));
          $today = (int) date('j');

          for ($i = 1; $i < $firstDayOfMonth; $i++): ?>
            <div class="date-cell disabled"></div>
          <?php endfor;

          for ($day = 1; $day <= $daysInMonth; $day++):
            $isPast = $day < $today;
            $cellClass = $isPast ? 'past' : '';
            $dataDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
          ?>
            <div class="date-cell <?= e($cellClass) ?>" data-date="<?= e($dataDate) ?>" <?= $isPast ? '' : 'onclick="selectDate(this)"' ?>>
              <?= (int) $day ?>
            </div>
          <?php endfor; ?>
        </div>
        <div id="selectedDateDisplay"></div>
        <input type="hidden" name="dateres" id="dateresInput" required>
      </div>

      <div class="input_group">
        <label for="cin">Numero CIN</label>
        <input type="text" name="cin" id="cin" placeholder="12345678" required pattern="\d{8}" maxlength="8" inputmode="numeric">
        <span class="field-hint">8 chiffres exactement</span>
      </div>

      <div class="input_group">
        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" placeholder="Votre nom" required maxlength="50">
      </div>

      <div class="input_group">
        <label for="prenom">Prenom</label>
        <input type="text" name="prenom" id="prenom" placeholder="Votre prenom" required maxlength="50">
      </div>

      <div class="input_group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="exemple@email.com" required maxlength="100">
      </div>

      <div class="input_group">
        <label for="phone">Telephone</label>
        <input type="text" name="phone" id="phone" placeholder="12345678" required pattern="\d{8}" maxlength="8" inputmode="numeric">
        <span class="field-hint">8 chiffres</span>
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="btnn">
        Continuer &rarr;
      </button>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.pack-option').forEach(option => {
  option.addEventListener('click', function() {
    document.querySelectorAll('.pack-option').forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    this.querySelector('input[type="radio"]').checked = true;
  });
});

function selectDate(cell) {
  if (cell.classList.contains('past') || cell.classList.contains('disabled')) return;
  document.querySelectorAll('.date-cell').forEach(c => c.classList.remove('selected'));
  cell.classList.add('selected');
  const date = cell.dataset.date;
  document.getElementById('dateresInput').value = date;
  const dateObj = new Date(date + 'T00:00:00');
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  document.getElementById('selectedDateDisplay').textContent = dateObj.toLocaleDateString('fr-FR', options);
}

document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
  const packSelected = document.querySelector('input[name="pack_id"]:checked');
  if (!packSelected) {
    e.preventDefault();
    alert('Veuillez choisir un pack.');
    return false;
  }
  const dateSelected = document.getElementById('dateresInput').value;
  if (!dateSelected) {
    e.preventDefault();
    alert('Veuillez choisir une date de reservation.');
    return false;
  }
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
