<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
session_start();

if (empty($_SESSION['client_id'])) {
    header('Location: inscription.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservation.php');
    exit;
}

$club       = trim($_POST['club'] ?? '');
$nomenfant  = trim($_POST['nameef'] ?? '');
$dateres    = $_POST['dateres'] ?? '';
$heurres    = $_POST['heurres'] ?? '';
$dateaniv   = $_POST['dateaniv'] ?? '';
$nbpersone  = (int) ($_POST['nbpersone'] ?? 0);
$activites  = $_POST['activite'] ?? [];
$decoChoice = $_POST['deco'] ?? 'standard';
$customDeco = trim($_POST['customDecoration'] ?? '');

$errors = [];
if ($nomenfant === '') $errors[] = "Nom de l'enfant requis.";
if (!$dateres)          $errors[] = 'Date de reservation requise.';
if (!$heurres)           $errors[] = 'Heure de reservation requise.';
if (!$dateaniv)          $errors[] = "Date d'anniversaire requise.";
if ($nbpersone < 1)      $errors[] = 'Nombre de personnes invalide.';
// Activities are auto-filled from pack, but validate just in case
if (empty($activites))   $errors[] = 'Erreur: aucune activite associee au pack.';

$allowedClubs = ['CITE NASIR', 'GAMMARTH', 'LA MARSA', 'SOUSSE', 'SFAX'];
if (!in_array($club, $allowedClubs, true)) {
    $errors[] = 'Club invalide.';
}

$allowedActivites = ['ps5', 'parc', 'laser', 'vr'];
$activites = array_values(array_intersect((array) $activites, $allowedActivites));

$decoration = $decoChoice === 'personnel' ? $customDeco : 'Standard';
if ($decoChoice === 'personnel' && $customDeco === '') {
    $errors[] = 'Merci de decrire la decoration personnalisee.';
}

if ($errors) {
    $_SESSION['form_error'] = implode(' ', $errors);
    header('Location: reservation.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO reserver
            (nomparent, dateres, heureres, dateaniv, nomenfant, geekclub, nbpersone, activite, deecoration, pack_id, client_id, place)
         VALUES
            (:nomparent, :dateres, :heureres, :dateaniv, :nomenfant, :geekclub, :nbpersone, :activite, :deecoration, :pack_id, :client_id, NULL)'
    );
    $stmt->execute([
        ':nomparent'   => $_SESSION['client_name'],
        ':dateres'     => $dateres,
        ':heureres'    => $heurres,
        ':dateaniv'    => $dateaniv,
        ':nomenfant'   => $nomenfant,
        ':geekclub'    => $club,
        ':nbpersone'   => $nbpersone,
        ':activite'    => implode('-', $activites),
        ':deecoration' => $decoration,
        ':pack_id'     => $_SESSION['pack_id'] ?? null,
        ':client_id'   => $_SESSION['client_id'],
    ]);

    $reservationId = (int) $pdo->lastInsertId();
    $pdo->commit();

    $_SESSION['reservation_id'] = $reservationId;
    header('Location: choisir_place.php');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['form_error'] = 'Une erreur est survenue. Si le probleme persiste, assurez-vous d\'avoir execute migration.sql sur la base de donnees.';
    header('Location: reservation.php');
    exit;
}
