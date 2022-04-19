<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Http\ErrorHandling;

use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\UserFacing;
use Throwable;

final class UserFacingHttpException extends HttpException implements UserFacing
{
    private string $title;

    private string $user_message;

    public function __construct(string $title, string $user_message, Throwable $wrapped_exception)
    {
        $this->title = $title;
        $this->user_message = $user_message;

        if ($wrapped_exception instanceof HttpException) {
            $status_code = $wrapped_exception->statusCode();
            $headers = $wrapped_exception->headers();
        } else {
            $status_code = 500;
            $headers = [];
        }

        parent::__construct(
            $status_code,
            $wrapped_exception->getMessage(),
            $headers,
            (int) $wrapped_exception->getCode(),
            $wrapped_exception
        );
    }

    public function title(): string
    {
        return $this->title;
    }

    public function safeMessage(): string
    {
        return $this->user_message;
    }
}
