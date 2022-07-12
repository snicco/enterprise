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
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;
use Snicco\Enterprise\Fortress\Auth\User\Domain\UserProvider;
use Webmozart\Assert\Assert;

use function sprintf;

final class Initialize2FaCommand extends Command
{
    protected static string $name = '2fa:initialize';

    private CommandBus $command_bus;

    private UserProvider $user_provider;

    private TwoFactorSecretGenerator $secret_generator;

    private string $application_name;

    public function __construct(
        CommandBus $command_bus,
        UserProvider $user_provider,
        TwoFactorSecretGenerator $secret_generator,
        string $application_name
    ) {
        $this->command_bus = $command_bus;
        $this->user_provider = $user_provider;
        $this->secret_generator = $secret_generator;
        $this->application_name = $application_name;
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
        $secret = $this->secret_generator->generate();
        $codes = BackupCodes::generate();

        $this->command_bus->handle(
            new Initialize2Fa(
                $id,
                $secret,
                $codes
            )
        );

        $style->success(sprintf('Two-Factor-Authentication has been setup for user with id [%d].', $id));

        $style->note([
            'Copy the below secret into your Two-Factor-Authentication app.',
            sprintf("Then run the '%s 2fa:complete' command to finish your setup.", $this->application_name),
            'You should copy the backup codes to a secure location. You will not be able to see them again.',
        ]);

        $output->writeln('Secret: ' . $secret);
        $output->newLine();
        $output->writeln('Backup-Codes: ');

        foreach ($codes as $code) {
            $output->writeln($code);
        }

        return self::SUCCESS;
    }
}
