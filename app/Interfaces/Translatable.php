<?

namespace Robin\Interfaces;

interface Translatable
{
    public function setLocale(string $locale): void;
    public function getLocale(): string;
    public function isTranslated($locale): bool;
    
    public function setDataHandler(\Robin\Keeper $data_handler): void;
}