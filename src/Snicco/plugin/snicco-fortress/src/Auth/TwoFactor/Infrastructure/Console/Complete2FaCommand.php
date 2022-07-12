<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\Console;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\Fortress\Auth\User\Domain\UserProvider;
use Webmozart\Assert\Assert;

use function sprintf;

final class Complete2FaCommand extends Command
{
    protected static string $name = '2fa:complete';

    private CommandBus $command_bus;

    private UserProvider $user_provider;

    public function __construct(CommandBus $command_bus, UserProvider $user_provider)
    {
        $this->command_bus = $command_bus;
        $this->user_provider = $user_provider;
    }

    public function execute(Input $input, Output $output): int
    {
        $user_id = $input->getArgument('user');
        $otp = $input->getArgument('otp');

        Assert::stringNotEmpty($user_id);
        Assert::stringNotEmpty($otp);

        $user = $this->user_provider->getUserByIdentifier($user_id);

        /** @var positive-int $id */
        $id = $user->ID;

        $this->command_bus->handle(
            new Complete2FaSetup($id, $otp)
        );

        $style = new SniccoStyle($input, $output);

        $style->success(sprintf('Two-Factor setup has been completed for user [%s].', $user_id));

        return self::SUCCESS;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()
            ->with([
                new InputArgument(
                    'user',
                    'A valid user_id, user email or user login.'
                ),
                new InputArgument(
                    'otp',
                    'The current one-time-password from the authenticator app.'
                ),
            ]);
    }
}
