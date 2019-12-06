<?

namespace Robin\Interfaces;

interface Translatable
{
    public function setLocale(string $locale): bool;
    public function getLocale(): string;
    public function isTranslated($locale): bool;
    
    public function setDataHandler(\Robin\Keeper $data_handler): void;
}