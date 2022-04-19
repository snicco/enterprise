<?php

// Extends: admin.layout

declare(strict_types=1);

/**
 * @var Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator $url
 */
?>

<div style="height: 400px; padding-top: 25px">
    <h1>
        VENDOR_TITLE main page content goes here
    </h1>
	<a href="<?= $url->toRoute(
    'admin.VENDOR_SLUG.support'
); ?>">This is how you can reach us.</a>
</div>
