<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Persistence;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\EbookForCustomer;
use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use Webmozart\Assert\Assert;

use function array_map;
use function is_int;
use function is_string;
use function sprintf;

final class AvailableEbooksUsingBetterWPDB implements AvailableEbooks
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

    public function forCustomers(): array
    {
        /** @var non-empty-string $query */
        $query = sprintf('select * from `%s` where `archived` = ?', $this->table_name);

        $rows = $this->better_wpdb->selectAll($query, [false]);

        return array_map(fn (array $row): EbookForCustomer => $this->instantiateEbookFromRecord($row), $rows);
    }

    public function getEbookForCustomer(EbookId $id): EbookForCustomer
    {
        try {
            /** @var non-empty-string $query */
            $query = sprintf('select * from `%s` where `id` = ? and `archived` = ?', $this->table_name);

            $row = $this->better_wpdb->selectRow($query, [$id->asString(), false]);

            return $this->instantiateEbookFromRecord($row);
        } catch (NoMatchingRowFound $e) {
            throw CouldNotFindEbook::withId($id);
        }
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function instantiateEbookFromRecord(array $row): EbookForCustomer
    {
        Assert::true(isset($row['id']) && is_string($row['id']), 'Invalid id column returned from database.');
        Assert::true(isset($row['title']) && is_string($row['title']), 'Invalid title column returned from database.');
        Assert::true(
            isset($row['description']) && is_string($row['description']),
            'Invalid description column returned from database.'
        );
        Assert::true(isset($row['price']) && is_int($row['price']), 'Invalid price column returned from database.');

        return new EbookForCustomer($row['id'], $row['title'], $row['description'], $row['price']);
    }
}
