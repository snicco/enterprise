<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook;

use RuntimeException;
use VENDOR_NAMESPACE\Domain\Model\Common\EntityNotFound;
use VENDOR_NAMESPACE\Domain\Model\Common\UserFacingDomainError;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

final class CouldNotFindEbook extends RuntimeException implements UserFacingDomainError, EntityNotFound
{
    private string $id;

    public function __construct(EbookId $id)
    {
        $this->id = $id->asString();
        parent::__construct(
            $this->translationID(),
        );
    }

    public static function withId(EbookId $ebook_id): self
    {
        return new self($ebook_id);
    }

    public function translationID(): string
    {
        return 'ebook.not_found';
    }

    /**
     * @return array{ebook_id: string}
     */
    public function translationParameters(): array
    {
        return [
            'ebook_id' => $this->id,
        ];
    }
}
