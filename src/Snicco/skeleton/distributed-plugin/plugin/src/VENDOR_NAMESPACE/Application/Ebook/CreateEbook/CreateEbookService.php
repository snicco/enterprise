<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\CreateEbook;

use VENDOR_NAMESPACE\Application\DomainEvents;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookDescription;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookPrice;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;

final class CreateEbookService
{
    private EbookRepository $ebook_repository;

    private DomainEvents    $domain_events;

    public function __construct(EbookRepository $ebook_repository, DomainEvents $domain_events)
    {
        $this->ebook_repository = $ebook_repository;
        $this->domain_events = $domain_events;
    }

    public function __invoke(CreateEbook $create_ebook): void
    {
        $ebook = Ebook::createNew(
            EbookId::fromString($create_ebook->ebook_id),
            new EbookTitle($create_ebook->title),
            new EbookDescription($create_ebook->description),
            new EbookPrice($create_ebook->price)
        );

        $this->ebook_repository->save($ebook);

        $this->domain_events->dispatchAll($ebook->releaseEvents());
    }
}
