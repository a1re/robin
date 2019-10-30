<?

namespace Robin\Interfaces;

interface Translatable
{
    public function setLanguage(string $language, bool $use_existing_values = false): void;
    public function isTranslated($language): bool;
}