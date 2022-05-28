<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Middleware\Negotiation\NegotiateContent;
use Snicco\Middleware\Payload\JsonToArray;

final class AcceptsJsonOnly extends Middleware
{
    
    private NegotiateContent $negotiate_content;
    private JsonToArray $json_payload;
    
    public function __construct(NegotiateContent $negotiate_content) {
        $this->negotiate_content = $negotiate_content;
        $this->json_payload = new JsonToArray();
    }
    
    protected function handle(Request $request, NextMiddleware $next) :ResponseInterface
    {
        if($request->isReadVerb()){
            return $next($request);
        }
        
        return $this->negotiate_content->process($request, new NextMiddleware(
            fn(Request $request) => $this->json_payload->process($request,$next)
        ));
    }
    
}