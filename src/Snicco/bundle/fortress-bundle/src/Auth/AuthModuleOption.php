<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth;

final class AuthModuleOption
{
    /**
     * @var string
     */
    public const TWO_FACTOR_SETTINGS_TABLE_BASENAME = '2fa_settings_table';

    /**
     * @var string
     */
    public const TWO_FACTOR_CHALLENGES_TABLE_BASENAME = '2fa_challenges_table';

    /**
     * @var string
     */
    public const TWO_FACTOR_CHALLENGE_HMAC_KEY = '2fa_challenges_hmac_key';
}
