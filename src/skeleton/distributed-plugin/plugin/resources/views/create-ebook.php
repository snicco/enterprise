<?php
/*
 * Extends: layout.php
 */

declare(strict_types=1);

use Snicco\Middleware\WPNonce\WPNonce;

/**
 * @var WPNonce $wp_nonce
 */

?>

<form id="create-ebook-form" method="POST" class='max-w-sm space-y-8 divide-y divide-gray-200'>
	<?= $wp_nonce() ?>
	<div class='space-y-8 divide-y divide-gray-200'>
		<div>
			<div>
				<h3 class='text-lg leading-6 font-medium text-gray-900'>Create your new ebook</h3>
				<p class='mt-1 text-sm text-gray-500'>
					This information will be displayed publicly for your ebook
				</p>
			</div>
			
			<div class='mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6'>
				<div class='sm:col-span-3'>
					<label for='first-name' class='block text-sm font-medium text-gray-700'> Ebook title </label>
					<div class='mt-1'>
						<input
								type='text'
								name='title'
								id='title'
								class='shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md'
						>
					</div>
				</div>
				<div class='sm:col-span-3'>
					<label for='last-name' class='block text-sm font-medium text-gray-700'> Price in cents </label>
					<div class='mt-1'>
						<input
								type='number'
								min="1000"
								name='price'
								id='price'
								class='shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md'
						>
					</div>
				</div>
				<div class='sm:col-span-6'>
					<label for='about' class='block text-sm font-medium text-gray-700'> Description </label>
					<div class='mt-1'>
						<textarea
								id='description'
								name='description'
								rows='3'
								class='shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md'
						></textarea>
					</div>
					<p class='mt-2 text-sm text-gray-500'>Write a few sentences about your ebook.</p>
				</div>
			</div>
		</div>
		
	</div>
	<div class="pt-5">
		<div class="flex justify-end">
			<button
					type="submit"
					class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
			>Create ebook
			</button>
		</div>
	</div>
</form>

