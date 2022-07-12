<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure;

use VENDOR_NAMESPACE\Application\DomainEvents;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbookService;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbookService;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;

/**
 * This is a handwritten service container which only contains our core
 * services.
 *
 * This is an opinionated way to separate our code services from the services
 * that Snicco uses. Alternatively services could also be bound in the container
 * adapter that Snicco uses.
 */
abstract class ServiceContainer
{
    abstract public function availableEbooks(): AvailableEbooks;

    final public function archiveEbookService(): ArchiveEbookService
    {
        return new ArchiveEbookService($this->ebookRepository(), $this->availableEbooks(), $this->domainEvents(),);
    }

    final public function createEbookService(): CreateEbookService
    {
        return new CreateEbookService($this->ebookRepository(), $this->domainEvents(),);
    }

    abstract protected function ebookRepository(): EbookRepository;

    abstract protected function domainEvents(): DomainEvents;
}
