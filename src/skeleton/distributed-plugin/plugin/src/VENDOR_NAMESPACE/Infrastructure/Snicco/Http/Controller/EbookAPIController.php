<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\EbookForCustomer;

use function array_map;

final class EbookAPIController extends Controller
{
    private AvailableEbooks     $available_ebooks;

    public function __construct(AvailableEbooks $available_ebooks)
    {
        $this->available_ebooks = $available_ebooks;
    }

    public function listForCustomers(): Response
    {
        return $this->respondWith()
            ->json(
                array_map(fn (EbookForCustomer $ebook) => $ebook->asArray(), $this->available_ebooks->forCustomers())
            );
    }
}
