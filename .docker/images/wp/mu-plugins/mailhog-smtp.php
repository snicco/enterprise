<?php

declare(strict_types=1);

/*
 * Plugin Name: Docker MailHog
 * Description: Send all wp_mail() emails to the MailHog docker container.
 */
add_action( 'phpmailer_init', function (\PHPMailer\PHPMailer\PHPMailer $mailer) {
    $mailer->Host = 'mailhog';
    $mailer->Port = 1025;
    $mailer->IsSMTP();
});

