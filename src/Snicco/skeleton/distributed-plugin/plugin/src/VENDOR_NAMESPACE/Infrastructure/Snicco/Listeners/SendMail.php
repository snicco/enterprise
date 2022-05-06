<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Listeners;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;

use function __;
use function strtr;
use function wp_mail;

final class SendMail implements EventSubscriber
{
    private UrlGenerator $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    public static function subscribedEvents(): array
    {
        return [
            EbookWasCreated::class => 'onEbookCreated',
            EbookWasArchived::class => 'onEbookArchived',
        ];
    }

    public function onEbookCreated(EbookWasCreated $ebook_was_created): void
    {
        $id = $ebook_was_created->ebook_id;
        $title = $ebook_was_created->title;

        $subject = __('A new ebook was created', 'VENDOR_TEXTDOMAIN');
        $message = __('Hi, a new ebook with title {ebook.title} was just created. {ebook.url}', 'VENDOR_TEXTDOMAIN');

        $message = strtr($message, [
            '{ebook.url}' => $this->url->toRoute('ebook.show', [
                'id' => $id,
            ], UrlGenerator::ABSOLUTE_URL),
            '{ebook.title}' => $title,
        ]);

        wp_mail('contact@example.com', $subject, $message);
    }

    public function onEbookArchived(EbookWasArchived $event): void
    {
        $subject = __('An ebook was archived', 'VENDOR_TEXTDOMAIN');
        $message = __('Hi, the ebook was id {ebook.id} was archived.', 'VENDOR_TEXTDOMAIN');

        $message = strtr($message, [
            '{ebook.id}' => $event->id->asString(),
        ]);

        wp_mail('contact@example.com', $subject, $message);
    }
}
