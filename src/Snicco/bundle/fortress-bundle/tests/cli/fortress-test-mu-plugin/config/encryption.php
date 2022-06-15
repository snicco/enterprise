<?php

declare(strict_types=1);

use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\Option\EncryptionOption;

return [
    EncryptionOption::KEY_ASCII => DefuseEncryptor::randomAsciiKey(),
];
