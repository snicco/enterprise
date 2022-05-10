<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class IsAdminEntryPoint implements Condition
{
    /**
     * @var string[]
     */
    private array $entry_points;

    /**
     * @param string[] $entry_points A list of file names inside the /wp-admin folder.
     *                               E.G: [themes.php, edit.php]
     */
    public function __construct(array $entry_points)
    {
        $this->entry_points = $entry_points;
    }

    public function isTruthy(Context $context): bool
    {
        $script = $context->scriptName();

        foreach ($this->entry_points as $entry_point) {
            if (Str::endsWith($script, '/wp-admin/' . $entry_point)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [self::class, [$this->entry_points]];
    }
}
