<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/helpers.php';

// Only load PHPMailer if available
$phpmailerAvailable = false;
try {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $phpmailerAvailable = true;
    }
} catch (Throwable $e) {}

function sendConfirmationEmail(string $to, string $name, int $reservationId): void
{
    global $phpmailerAvailable;

    if (!$phpmailerAvailable) {
        error_log('PHPMailer not installed. Run: composer install');
        return;
    }

    $smtpHost = getenv('SMTP_HOST') ?: '';
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASS') ?: '';
    $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
    $fromEmail = getenv('FROM_EMAIL') ?: 'noreply@geekclub.tn';
    $fromName = getenv('FROM_NAME') ?: 'Geek Club';

    if (empty($smtpHost) || empty($smtpUser)) {
        error_log('SMTP not configured. Set SMTP_HOST, SMTP_USER, SMTP_PASS environment variables.');
        return;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmation de votre reservation Geek Club #' . $reservationId;
        $mail->Body = sprintf(
            '<h2>Bonjour %s !</h2>' .
            '<p>Votre reservation <strong>n°%d</strong> est confirmee.</p>' .
            '<p>Merci de votre confiance et a tres bientot au Geek Club !</p>' .
            '<hr><p style="color:#666;font-size:12px;">Geek Club - Reservation</p>',
            htmlspecialchars($name),
            $reservationId
        );

        $mail->send();
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
    }
}

function notifyOwnerOfReservation(array $reservation, array $place): void
{
    global $phpmailerAvailable;

    if (!$phpmailerAvailable) {
        error_log('PHPMailer not installed. Run: composer install');
        return;
    }

    $ownerEmail = getenv('OWNER_EMAIL') ?: '';
    $smtpHost = getenv('SMTP_HOST') ?: '';
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASS') ?: '';
    $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
    $fromEmail = getenv('FROM_EMAIL') ?: 'noreply@geekclub.tn';
    $fromName = getenv('FROM_NAME') ?: 'Geek Club';

    if (empty($ownerEmail) || empty($smtpHost) || empty($smtpUser)) {
        error_log('Owner email or SMTP not configured.');
        return;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($ownerEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle reservation Geek Club #' . ($reservation['id'] ?? 'N/A');
        $mail->Body = sprintf(
            '<h2>Nouvelle reservation !</h2>' .
            '<ul>' .
            '<li><strong>Parent:</strong> %s</li>' .
            '<li><strong>Enfant:</strong> %s</li>' .
            '<li><strong>Club:</strong> %s</li>' .
            '<li><strong>Date:</strong> %s a %s</li>' .
            '<li><strong>Personnes:</strong> %d</li>' .
            '<li><strong>Table:</strong> %s</li>' .
            '</ul>',
            htmlspecialchars($reservation['nomparent'] ?? ''),
            htmlspecialchars($reservation['nomenfant'] ?? ''),
            htmlspecialchars($reservation['geekclub'] ?? ''),
            htmlspecialchars($reservation['dateres'] ?? ''),
            htmlspecialchars(isset($reservation['heureres']) ? substr($reservation['heureres'], 0, 5) : ''),
            (int) ($reservation['nbpersone'] ?? 0),
            htmlspecialchars($place['nom'] ?? '')
        );

        $mail->send();
    } catch (Exception $e) {
        error_log('Owner notification failed: ' . $e->getMessage());
    }
}
