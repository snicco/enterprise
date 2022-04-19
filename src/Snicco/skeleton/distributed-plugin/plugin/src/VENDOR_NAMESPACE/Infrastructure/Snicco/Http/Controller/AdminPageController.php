<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;

final class AdminPageController extends Controller
{
    public function __invoke(): ViewResponse
    {
        return $this->respondWith()
            ->view('admin.pages.main');
    }

    public function support(): ViewResponse
    {
        return $this->respondWith()
            ->view('admin.pages.support');
    }
}
