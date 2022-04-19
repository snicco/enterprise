<?php

declare(strict_types=1);

/** @psalm-var  Snicco\Enterprise\Component\Asset\AssetFactory $asset */
/** @var string $__content */

$title = (isset($title) && \is_string($title)) ? $title : \get_bloginfo('name');


?><!DOCTYPE html>
<html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv='X-UA-Compatible' content='ie=edge'>
        <title> <?= \esc_html($title); ?></title>
	    <link  rel="stylesheet" href="<?= esc_attr($asset('css/public.css')) ?>">
    </head>
    <body>
	
	    <div class='container mx-auto pt-8 px-4 sm:px-6 lg:px-8'>
		    <?= $__content; ?>
	    </div>
	   
    </body>
	<script src="<?= esc_attr($asset('js/frontend.js')) ?>"></script>
</html>
