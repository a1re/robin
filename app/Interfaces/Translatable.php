<?

namespace Robin\Interfaces;

interface Translatable
{
    public function setLanguage(string $language): void;
    public function getLanguage(): string;
    public function isTranslated($language): bool;
    
    public function setDataHandler(\Robin\Keeper $data_handler): void;
}