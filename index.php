<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$packs = $pdo->query('SELECT id, nom, description, prix, image, activites FROM pack WHERE actif = 1 ORDER BY id')
              ->fetchAll();

$places_count = $pdo->query("SELECT COUNT(*) FROM place WHERE statut = 'libre'")->fetchColumn();

// Map pack names to actual uploaded image files
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
  <title>Geek Club — Reservation</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="scanlines">

<header class="site-header">
  <div class="logo">
    <img src="img/logo.png" alt="Geek Club" onerror="this.style.display='none'">
  </div>
</header>

<main>
  <section class="hero">
    <div class="hero-decoration hero-decoration-1"></div>
    <div class="hero-decoration hero-decoration-2"></div>
    <h1 class="neon-text fade-in-up">Geek Club</h1>
    <p class="fade-in-up" style="animation-delay:0.2s">Reservez votre espace pour un anniversaire inoubliable, entre PS5 et parc d'aventure !</p>

    <div class="hero-stats fade-in-up" style="animation-delay:0.4s">
      <div class="stat-item">
        <span class="stat-number"><?= (int) $places_count ?></span>
        <span class="stat-label">Places disponibles</span>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?= count($packs) ?></span>
        <span class="stat-label">Packs exclusifs</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">5</span>
        <span class="stat-label">Clubs</span>
      </div>
    </div>
  </section>

  <section class="packs-section">
    <h2 class="section-title reveal">Nos Packs</h2>

    <?php if (!$packs): ?>
      <p style="text-align:center;color:var(--text-muted);max-width:500px;margin:0 auto;">
        Nos packs seront bientot disponibles, revenez vite !
      </p>
    <?php else: ?>
      <div class="packs-grid">
        <?php foreach ($packs as $i => $pack):
          $imgPath = $packImageMap[$pack['nom']] ?? fixImagePath($pack['image']);
        ?>
          <div class="pack-card-simple reveal" style="animation-delay: <?= $i * 0.15 ?>s">
            <div class="pack-img-wrap">
              <img src="<?= e($imgPath) ?>" alt="<?= e($pack['nom']) ?>"
                   onerror="this.onerror=null; this.src='img/logo.png'; this.style.objectFit='contain'; this.style.padding='2rem';">
            </div>
            <div class="pack-body">
              <h3><?= e($pack['nom']) ?></h3>
              <p><?= e($pack['description']) ?></p>
              <span class="pack-price-big"><?= number_format((float) $pack['prix'], 2) ?> DT</span>
              <a href="inscription.php?pack=<?= (int) $pack['id'] ?>" class="pack-btn">Choisir ce pack &rarr;</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="features-section">
    <h2 class="section-title reveal">Pourquoi nous choisir ?</h2>
    <div class="features-grid">
      <div class="feature-card reveal">
        <div class="feature-icon">&#127918;</div>
        <h3>PS5 Pro</h3>
        <p>Derniere generation avec ecran 4K et manettes sans fil</p>
      </div>
      <div class="feature-card reveal">
        <div class="feature-icon">&#127906;</div>
        <h3>Parc Aventure</h3>
        <p>Acces illimite aux attractions pour toute la duree</p>
      </div>
      <div class="feature-card reveal">
        <div class="feature-icon">&#127874;</div>
        <h3>Decoration VIP</h3>
        <p>Personnalisation complete selon vos preferences</p>
      </div>
      <div class="feature-card reveal">
        <div class="feature-icon">&#128241;</div>
        <h3>Confirmation SMS</h3>
        <p>Recevez votre confirmation directement sur WhatsApp</p>
      </div>
    </div>
  </section>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> Geek Club. Tous droits reserves.</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
