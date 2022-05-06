<?php
/*
 * Extends: layout.php
 */

declare(strict_types=1);

/**
 * @var VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\EbookForCustomer $ebook
 * @var Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator          $url
 * @var Snicco\Middleware\WPNonce\WPNonce $wp_nonce
 */

?>

<form method='POST' action="<?= \esc_html($route_url = $url->toRoute('ebook.archive', ['id' => $ebook->id()])); ?>" class='max-w-sm space-y-8 divide-y divide-gray-200'>
	<input type="hidden" name="_method" value="PATCH">
	<?= $wp_nonce($route_url) ?>
	<div class='space-y-8 divide-y divide-gray-200'>
		<div>
			<div>
				<h3 class='text-lg leading-6 font-medium text-gray-900'><?= \esc_html($ebook->title()); ?></h3>
			</div>
			<div class="mt-2">
				<p> <?= \esc_html($ebook->description()); ?></p>
			</div>
			<div class="mt-2">
				<p>This ebooks will be available soon for <b><?= esc_html($ebook->formattedPrice()); ?></b></p>
			</div>
		</div>
	
	</div>
	<div class='pt-5'>
		<div class='flex-col justify-start'>
			<button
					type='submit'
					class='inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
			>Archive this ebook
			</button>
			<p class="text-sm mt-2">(only possible as an admin but displayed on purpose)</p>
		</div>
	</div>
</form>

<div>
	<a class='underline text-indigo-600 mt-4 inline-block' href="<?= $url->toRoute('ebook.index'); ?>">Back to all ebooks</a>
</div>


