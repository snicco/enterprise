<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Http\ErrorHandling;

use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Throwable;
use VENDOR_NAMESPACE\Domain\Model\Common\EntityNotFound;
use VENDOR_NAMESPACE\Domain\Model\Common\UserFacingDomainError;
use VENDOR_NAMESPACE\Infrastructure\WordPress\Translation\Translator;

final class DomainExceptionTransformer implements ExceptionTransformer
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function transform(Throwable $e): Throwable
    {
        $not_found = ($e instanceof EntityNotFound);

        if ($e instanceof UserFacingDomainError) {
            $http_exception = HttpException::fromPrevious($not_found ? 404 : 500, $e);

            $translated = $this->translator->translate($e->translationID(), $e->translationParameters());

            return new UserFacingHttpException($translated, '', $http_exception);
        }

        if ($not_found) {
            return HttpException::fromPrevious(404, $e);
        }

        return $e;
    }
}
