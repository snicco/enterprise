<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks;

use function number_format;

/**
 * @psalm-immutable
 */
final class EbookForCustomer
{
    private string $title;

    private string $description;

    private int    $price;

    private string $id;

    public function __construct(string $id, string $title, string $description, int $price)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function formattedPrice(): string
    {
        return '$' . number_format(($this->price / 100), 2, '.', ' ');
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array{id: string, title: string, description: string, price: int}
     */
    public function asArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
        ];
    }
}
