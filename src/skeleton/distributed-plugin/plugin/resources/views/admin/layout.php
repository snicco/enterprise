<?php

declare(strict_types=1);

use Snicco\Component\Templating\TemplateEngine;

/**
 * @var string         $__content
 * @var TemplateEngine $view
 */
?>

<?= $view->render('admin.header'); ?>

<?= $__content; ?>

<?= $view->render('admin.footer'); ?>



