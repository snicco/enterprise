<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress\Translation;

use function array_map;
use function strtr;

final class PHPFileTranslator implements Translator
{
    /**
     * @var array<string,string>
     */
    private array $translations;

    /**
     * @param array<string,string> $translations
     */
    public function __construct(array $translations)
    {
        $this->translations = $translations;
    }

    public function translate(string $id, array $translation_params = []): string
    {
        if (! isset($this->translations[$id])) {
            MissingTranslationID::forId($id);
        }

        $translated = $this->translations[$id];

        $translation_params = array_map(fn ($param): string => '{' . (string) $param . '}', $translation_params);

        return strtr($translated, $translation_params);
    }
}
