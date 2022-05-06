<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller;

use Ramsey\Uuid\Uuid;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbook;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbook;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

use function user_can;

final class EbookController extends Controller
{
    private AvailableEbooks $available_ebooks;

    private CommandBus      $command_bus;

    public function __construct(AvailableEbooks $available_ebooks, CommandBus $command_bus)
    {
        $this->available_ebooks = $available_ebooks;
        $this->command_bus = $command_bus;
    }

    public function listForCustomers(): ViewResponse
    {
        return $this->respondWith()
            ->view('list-ebooks', [
                'ebooks' => $this->available_ebooks->forCustomers(),
            ]);
    }

    public function show(string $ebook_id): ViewResponse
    {
        $id = EbookId::fromString($ebook_id);

        return $this->respondWith()
            ->view('show-ebook', [
                'ebook' => $this->available_ebooks->getEbookForCustomer($id),
            ]);
    }

    public function create(Request $request): Response
    {
        if ($request->isGet()) {
            return $this->respondWith()
                ->view('create-ebook');
        }

        $id = Uuid::uuid4()->toString();

        $this->command_bus->handle(CreateEbook::fromRequestData($id, (array) $request->post()));

        return $this->respondWith()
            ->redirectToRoute('ebook.show', [
                'id' => $id,
            ]);
    }

    public function archive(Request $request, string $ebook_id): RedirectResponse
    {
        $user_id = $request->userId();

        if (! $user_id || ! user_can($user_id, 'manage_options')) {
            throw new HttpException(403, "User {$user_id} does not have permissions to delete ebooks.");
        }

        $this->command_bus->handle(new ArchiveEbook($ebook_id));

        return $this->respondWith()
            ->redirectToRoute('ebook.index');
    }
}
