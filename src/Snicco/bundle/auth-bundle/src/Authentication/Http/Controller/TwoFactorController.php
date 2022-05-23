<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\Http\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Controller\Controller;

final class TwoFactorController extends Controller
{
    
    public function challenge(Request $request) :Response
    {
        return $this->respondWith()->html('<h1>Challenge</h1>');
    }
    
}