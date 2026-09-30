<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inscription.php');
    exit;
}

$cin    = trim($_POST['cin'] ?? '');
$nom    = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$mail   = trim($_POST['email'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$packId = isset($_POST['pack_id']) && ctype_digit($_POST['pack_id']) ? (int) $_POST['pack_id'] : null;
$dateres = $_POST['dateres'] ?? '';

$errors = [];
if (!preg_match('/^\d{8}$/', $cin))            $errors[] = 'CIN invalide (8 chiffres).';
if ($nom === '')                               $errors[] = 'Nom requis.';
if ($prenom === '')                            $errors[] = 'Prenom requis.';
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
if (!preg_match('/^\d{8}$/', $phone))          $errors[] = 'Telephone invalide (8 chiffres).';
if (!$packId)                                  $errors[] = 'Veuillez choisir un pack.';
if (!$dateres)                                 $errors[] = 'Veuillez choisir une date.';

if ($errors) {
    $_SESSION['form_error'] = implode(' ', $errors);
    header('Location: inscription.php');
    exit;
}

$_SESSION['pack_id'] = $packId;
$_SESSION['selected_dateres'] = $dateres;

try {
    $stmt = $pdo->prepare(
        'INSERT INTO client (cn, nom, prenom, email, phone) VALUES (:cn, :nom, :prenom, :email, :phone)'
    );
    $stmt->execute([
        ':cn'     => $cin,
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':email'  => $mail,
        ':phone'  => $phone,
    ]);

    $_SESSION['client_id']    = (int) $pdo->lastInsertId();
    $_SESSION['client_name']  = $nom . ' ' . $prenom;
    $_SESSION['client_email'] = $mail;
    $_SESSION['client_phone'] = $phone;

    // FIXED: Redirect to reservation.php (recap.php doesn't exist!)
    header('Location: reservation.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['form_error'] = 'Une erreur est survenue, veuillez reessayer.';
    header('Location: inscription.php');
    exit;
}
