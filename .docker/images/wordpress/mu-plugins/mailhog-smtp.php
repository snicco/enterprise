<?php

declare(strict_types=1);

/*
 * Plugin Name: Docker MailHog
 * Plugin Description: Send all wp_mail() emails the MailHog docker container.
 */
add_action( 'phpmailer_init', function (\PHPMailer\PHPMailer\PHPMailer $mailer) {
    $mailer->Host = 'mailhog';
    $mailer->Port = 1025;
    $mailer->IsSMTP();
});

