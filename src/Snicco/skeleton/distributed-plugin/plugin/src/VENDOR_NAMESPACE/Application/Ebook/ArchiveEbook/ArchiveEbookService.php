<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook;

use VENDOR_NAMESPACE\Application\DomainEvents;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

final class ArchiveEbookService
{
    private EbookRepository $ebook_repository;

    private DomainEvents    $domain_events;

    private AvailableEbooks $available_ebooks;

    public function __construct(
        EbookRepository $ebook_repository,
        AvailableEbooks $available_ebooks,
        DomainEvents $domain_events
    ) {
        $this->ebook_repository = $ebook_repository;
        $this->domain_events = $domain_events;
        $this->available_ebooks = $available_ebooks;
    }

    public function __invoke(ArchiveEbook $archive_ebook): void
    {
        $this->archiveSingleEbooks($archive_ebook->ebook_id);
    }

    public function archiveAll(ArchiveAllEbooks $command): void
    {
        foreach ($this->available_ebooks->forCustomers() as $ebook) {
            $this->archiveSingleEbooks($ebook->id());
        }
    }

    private function archiveSingleEbooks(string $ebook_id): void
    {
        $ebook = $this->ebook_repository->getById(EbookId::fromString($ebook_id));

        $ebook->archive();

        $this->ebook_repository->save($ebook);

        $this->domain_events->dispatchAll($ebook->releaseEvents());
    }
}
