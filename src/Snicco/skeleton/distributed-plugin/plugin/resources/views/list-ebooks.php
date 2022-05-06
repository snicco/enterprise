<?php

/*
 * Extends: layout.php
 */
declare(strict_types=1);

/**
 * @var VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\EbookForCustomer[] $ebooks
 * @var Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator $url
 */
?>

<!-- This example requires Tailwind CSS v2.0+ -->
<div class='px-4 sm:px-6 lg:px-8'>
	<div class='sm:flex sm:items-center'>
		<div class='sm:flex-auto'>
			<h1 class='text-xl font-semibold text-gray-900 mb-2'>Available Ebooks (<?= \count($ebooks); ?>)</h1>
			<a class="underline text-indigo-600" href="<?= $url->toRoute('api.ebooks.all'); ?>">See as JSON</a>
		</div>
		<div class='mt-4 sm:mt-0 sm:ml-16 sm:flex-none'>
			
			<a
					href="<?= $url->toRoute('ebook.create'); ?>"
					class='inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto'
			>
				Create a new ebook
			</a>
		
		</div>
	</div>
	<div class='mt-8 flex flex-col'>
		<div class='-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8'>
			<div class='inline-block min-w-full py-2 align-middle md:px-6 lg:px-8'>
				<div class='overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg'>
					<table class='min-w-full divide-y divide-gray-300'>
						<thead class='bg-gray-50'>
							<tr>
								<th
										scope='col'
										class='py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6'
								>
									Title
								</th>
								<th
										scope='col'
										class='px-3 py-3.5 text-left text-sm font-semibold text-gray-900'
								>
									Description
								</th>
								<th
										scope='col'
										class='px-3 py-3.5 text-left text-sm font-semibold text-gray-900'
								>
									Price
								</th>
								<th
										scope='col'
										class='relative py-3.5 pl-3 pr-4 sm:pr-6'
								>
									
									<span class='sr-only'>Details</span>
								</th>
							</tr>
						</thead>
						<tbody class='divide-y divide-gray-200 bg-white'>
							
							<?php
							foreach ($ebooks as $ebook) { ?>
								
								<tr>
									<td class='whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6'>
										<?= esc_html($ebook->title()) ?>
									</td>
									<td class='whitespace-nowrap px-3 py-4 text-sm text-gray-500'>
										<?= esc_html($ebook->description()) ?>
									</td>
									<td class='whitespace-nowrap px-3 py-4 text-sm text-gray-500'>
										<?= esc_html($ebook->formattedPrice()) ?>
									</td>
									
									<td class='relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6'>
										<a 	href="<?= $url->toRoute('ebook.show', [
												'id' => $ebook->id(),
										]); ?>" class='text-indigo-600 hover:text-indigo-900'>Details
											<span class='sr-only'>Details</span>
										</a>
									</td>
								</tr>
								
								<?php
							} ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

