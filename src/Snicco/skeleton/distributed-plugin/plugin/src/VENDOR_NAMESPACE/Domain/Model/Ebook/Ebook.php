<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook;

use VENDOR_NAMESPACE\Application\Mapping;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookDescription;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookPrice;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;

final class Ebook
{
    private EbookId $id;

    private EbookTitle $title;

    private EbookDescription $description;

    private EbookPrice $price;

    private bool $archived;

    /**
     * @var object[]
     */
    private array $events = [];

    private function __construct(
        EbookId $id,
        EbookTitle $title,
        EbookDescription $description,
        EbookPrice $price,
        bool $archived
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->archived = $archived;
    }

    public function id(): EbookId
    {
        return $this->id;
    }

    public static function createNew(
        EbookId $ebook_id,
        EbookTitle $title,
        EbookDescription $description,
        EbookPrice $ebook_price
    ): self {
        $ebook = new self($ebook_id, $title, $description, $ebook_price, false);

        $ebook->events[] = new EbookWasCreated(
            $ebook_id->asString(),
            $title->asString(),
            $description->asString(),
            $ebook_price->asInt()
        );

        return $ebook;
    }

    public function archive(): void
    {
        if ($this->archived) {
            throw EbookAlreadyArchived::withId($this->id);
        }

        $this->archived = true;
        $this->events[] = new EbookWasArchived($this->id);
    }

    /**
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    /**
     * @param array<string,scalar|null> $state
     */
    public static function fromState(array $state): self
    {
        return new self(
            EbookId::fromString(Mapping::asString($state, 'id')),
            new EbookTitle(Mapping::asString($state, 'title')),
            new EbookDescription(Mapping::asString($state, 'description')),
            new EbookPrice(Mapping::asInt($state, 'price')),
            Mapping::asBool($state, 'archived')
        );
    }

    /**
     * @return array{id: string, title: string, description: string, price: int, archived: bool}
     */
    public function state(): array
    {
        return [
            'id' => $this->id->asString(),
            'title' => $this->title->asString(),
            'description' => $this->description->asString(),
            'price' => $this->price->asInt(),
            'archived' => $this->archived,
        ];
    }
}
