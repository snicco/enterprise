<?php

declare(strict_types=1);

/**
 * @var string $safe_details
 * @var string $safe_title
 * @var int    $status_code
 * @var string $identifier
 */
?>
<!DOCTYPE html>
<html lang="<?= \get_locale(); ?>">
	<head>
		<meta charset="UTF-8"/>
		<meta content='noindex,nofollow,noarchive' name='robots'/>
		<title><?= \esc_html((string) $status_code) . ' - ' . \esc_html($safe_title); ?></title>
		<style>
			body {
				background-color: #fff;
				color: #222;
				font: 16px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
				margin: 0;
			}
			
			.container {
				margin: 30px;
				max-width: 600px;
			}
			
			h1 {
				color: #dc3545;
				font-size: 24px;
			}
			
			h2 {
				font-size: 18px;
			}
		</style>
	</head>
	<body>
		<div class='container'>
			<h1><?= \esc_html('VENDOR_TITLE') . ': ' . \esc_html($safe_title); ?></h1>
			<h2><?= \esc_html($safe_details); ?></h2>
			<p>
				<?= \esc_html_x(
    'This occurrence has been logged and can be identified by the following number in your WordPress error log:',
    'Admin error template: log context',
    'VENDOR_TEXTDOMAIN'
);
                ?>
				<br> <em><?= \esc_html($identifier); ?></em>
			</p>
			<p>
				<?= \esc_html_x(
                    'You might want to take a note of this code and contact your WordPress administrator.',
                    'Admin error template: call to action',
                    'VENDOR_TEXTDOMAIN'
                );
                ?>
			</p>
			
			<a href="<?= \admin_url(); ?>">
				<?= \esc_html_x(
                    'Go back to the admin area',
                    'Admin error template: go back to homepage'
                ); ?>
			</a>
		</div>
	</body>
</html>
