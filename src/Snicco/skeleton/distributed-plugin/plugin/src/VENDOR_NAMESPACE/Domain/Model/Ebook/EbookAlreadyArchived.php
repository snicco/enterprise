<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook;

use LogicException;
use VENDOR_NAMESPACE\Domain\Model\Common\UserFacingDomainError;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

final class EbookAlreadyArchived extends LogicException implements UserFacingDomainError
{
    private string $id;

    public function __construct(EbookId $id)
    {
        $this->id = $id->asString();
        parent::__construct(
            $this->id,
        );
    }

    public static function withId(EbookId $id): self
    {
        return new self($id);
    }

    public function translationID(): string
    {
        return 'ebook.already_archived';
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
