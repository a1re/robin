<?

namespace Robin\Interfaces;

interface Translatable
{
    public $language;
    public $translations;
    
    public function getId(): string;
    
    public function setTranslation(string $language, $mixed_attributes, string $value = ""): void;

    public function getOriginalLanguage(): string;
    public function getAttributes(): array;
    public function getTranslation(string $language, string $attrubute): ?string;
    public function getTranslationsList(string $language): array;
    
    public function composeOriginal(): string;
    public function applyTranslation(string $language = ""): bool;
    public function saveTranslation(string $folder = ""): void;
    public function readTranslation(string $folder = ""): void;
}