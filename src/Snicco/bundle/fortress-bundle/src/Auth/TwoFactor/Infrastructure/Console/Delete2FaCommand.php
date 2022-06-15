<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Console;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Delete2Fa\Delete2FaSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserProvider;
use Webmozart\Assert\Assert;
use function sprintf;

final class Delete2FaCommand extends Command
{
    protected static string $name = '2fa:delete';

    private CommandBus $command_bus;

    private UserProvider $user_provider;

    public function __construct(
        CommandBus $command_bus,
        UserProvider $user_provider
    ) {
        $this->command_bus = $command_bus;
        $this->user_provider = $user_provider;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()
            ->with([
                new InputArgument(
                    'user',
                    'A valid user_id, user email or user login.'
                ),
            ]);
    }

    public function execute(Input $input, Output $output): int
    {
        $style = new SniccoStyle($input, $output);

        $user_passed = $input->getArgument('user');

        Assert::stringNotEmpty($user_passed);

        $user = $this->user_provider->getUserByIdentifier($user_passed);

        /** @var positive-int $id */
        $id = $user->ID;

        $this->command_bus->handle(
            new Delete2FaSettings(
                $id,
            )
        );

        $style->success(
            sprintf('Two-Factor-Authentication settings have been deleted for user with id [%d].', $id)
        );

        return self::SUCCESS;
    }
}
