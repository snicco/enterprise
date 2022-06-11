<?php

declare(strict_types=1);

add_action( 'phpmailer_init', function (\PHPMailer\PHPMailer\PHPMailer $mailer) {
    $mailer->Host = 'mailhog';
    $mailer->Port = 1025;
    $mailer->IsSMTP();
});

