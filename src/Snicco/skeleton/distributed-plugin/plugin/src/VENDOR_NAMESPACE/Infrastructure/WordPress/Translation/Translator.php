<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress\Translation;

interface Translator
{
    /**
     * @param array<string,string|int> $translation_params
     *
     * @throws MissingTranslationID if the translation id does not exist
     */
    public function translate(string $id, array $translation_params = []): string;
}
