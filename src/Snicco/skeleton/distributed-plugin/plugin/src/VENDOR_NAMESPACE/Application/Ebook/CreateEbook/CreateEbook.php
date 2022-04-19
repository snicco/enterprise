<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\CreateEbook;

use VENDOR_NAMESPACE\Application\Mapping;

/**
 * @psalm-immutable
 */
final class CreateEbook
{
    public string  $title;

    public string  $description;

    public int     $price;

    public string $ebook_id;

    public function __construct(string $ebook_id, string $title, string $description, int $price)
    {
        $this->ebook_id = $ebook_id;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
    }

    public static function fromRequestData(string $ebook_id, array $data): self
    {
        $title = Mapping::asString($data, 'title');
        $description = Mapping::asString($data, 'description');
        $price = Mapping::asInt($data, 'price');

        return new self($ebook_id, $title, $description, $price);
    }
}
