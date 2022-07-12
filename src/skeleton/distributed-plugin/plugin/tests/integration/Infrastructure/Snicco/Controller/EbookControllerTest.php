<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\integration\Infrastructure\Snicco\Controller;

use Ramsey\Uuid\Uuid;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\FakeCommandBus;
use Snicco\Middleware\WPNonce\VerifyWPNonce;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookDescription;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookPrice;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;
use VENDOR_NAMESPACE\Infrastructure\ServiceContainer;
use VENDOR_NAMESPACE\Tests\integration\IntegrationTestServiceContainer;

use function spl_object_hash;

/**
 * @note This test does run against an in-memory database.
 *       You could also run it against a real database instead, but this test should verify that the integration
 *       between your code and Snicco works, not that you can talk to a mysql database. That's what contract tests
 *       and end-to-end tests are for.
 *
 * @internal
 */
final class EbookControllerTest extends ControllerTestCase
{
    private IntegrationTestServiceContainer $container;

    private TestableEventDispatcher         $testable_dispatcher;

    private FakeCommandBus                  $fake_bus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new IntegrationTestServiceContainer(
            $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher())
        );
        $this->swapInstance(ServiceContainer::class, $this->container);
        $this->fake_bus = new FakeCommandBus();
        $this->swapInstance(CommandBus::class, $this->fake_bus);
    }

    /**
     * @test
     */
    public function that_the_index_route_displays_all_ebooks(): void
    {
        $this->container->availableEbooksAre([
            $this->aNewEbook('Ebook 1 title'),
            $this->aNewEbook('Ebook 2 title'),
        ]);

        $browser = $this->getBrowser();
        $crawler = $browser->request('GET', '/ebooks');

        $this->assertSame('Available Ebooks (2)', $crawler->filter('h1')->innerText());

        $response = $browser->lastResponse();
        $response->assertOk();
        $response->assertSeeText('Ebook 1 title');
        $response->assertSeeText('Ebook 2 title');
    }

    /**
     * @test
     */
    public function that_unauthorized_users_can_not_archive_ebooks(): void
    {
        $this->withoutMiddleware([VerifyWPNonce::class]);
        $this->container->availableEbooksAre([$ebook = $this->aNewEbook('Ebook 1 title')]);

        $browser = $this->getBrowser();
        $browser->request('PATCH', "/ebooks/{$ebook->id()->asString()}/archive");

        $this->assertSame([], $this->fake_bus->commands);

        $browser->lastResponse()
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function that_admins_can_archive_ebooks(): void
    {
        $this->withoutMiddleware([VerifyWPNonce::class]);
        $this->container->availableEbooksAre([$ebook = $this->aNewEbook('Ebook 1 title')]);

        $this->loginAs($this->createAdmin());

        $browser = $this->getBrowser();
        $crawler = $browser->request('GET', '/ebooks');
        $this->assertSame('Available Ebooks (1)', $crawler->filter('h1')->innerText());

        $browser->followRedirects(false);
        $browser->request('PATCH', "/ebooks/{$ebook->id()->asString()}/archive");

        $response = $browser->lastResponse();
        $response->assertRedirectPath('/ebooks', 302);

        $this->assertEquals([new ArchiveEbook($ebook->id()->asString())], $this->fake_bus->commands);
    }

    private function aNewEbook(string $title): Ebook
    {
        $id = Uuid::uuid4();

        return Ebook::createNew(
            EbookId::fromString($id->toString()),
            new EbookTitle($title),
            new EbookDescription(spl_object_hash($id)),
            new EbookPrice(1000)
        );
    }
}
