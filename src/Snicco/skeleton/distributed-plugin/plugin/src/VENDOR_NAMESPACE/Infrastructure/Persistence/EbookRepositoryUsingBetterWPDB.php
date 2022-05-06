<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Persistence;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

use function is_int;
use function sprintf;

final class EbookRepositoryUsingBetterWPDB implements EbookRepository
{
    private BetterWPDB $better_wpdb;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(BetterWPDB $better_wpdb, string $table_name)
    {
        $this->better_wpdb = $better_wpdb;
        $this->table_name = $table_name;
    }

    public function save(Ebook $ebook): void
    {
        if ($this->exists($ebook->id())) {
            $this->better_wpdb->update($this->table_name, [
                'id' => $ebook->id()
                    ->asString(),
            ], $ebook->state());
        } else {
            $this->better_wpdb->insert($this->table_name, $ebook->state());
        }
    }

    public function getById(EbookId $ebook_id): Ebook
    {
        try {
            /** @var non-empty-string $query */
            $query = sprintf('select * from `%s` where `id` = ?', $this->table_name);

            $data = $this->better_wpdb->selectRow($query, [$ebook_id->asString()]);

            if (isset($data['archived']) && is_int($data['archived'])) {
                $data['archived'] = (bool) $data['archived'];
            }

            return Ebook::fromState($data);
        } catch (NoMatchingRowFound $e) {
            throw CouldNotFindEbook::withId($ebook_id);
        }
    }

    public function exists(EbookId $ebook_id): bool
    {
        return $this->better_wpdb->exists($this->table_name, [
            'id' => $ebook_id->asString(),
        ]);
    }
}
