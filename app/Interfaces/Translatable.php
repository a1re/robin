<?

namespace Robin\Interfaces;

interface Translatable
{    
    public function getId(): string;
    
    public function setTranslation(string $language, $mixed_attributes, string $value = ""): void;

    public function getOriginalLanguage(): string;
    public function getAttributes(): array;
    public function getTranslation(string $language, string $attrubute): ?string;
    public function getTranslationsList(string $language): array;
    
    public function composeOriginal(): void;
    public function applyTranslation(string $language = ""): bool;
    public function saveTranslation(string $folder = ""): bool;
    public function readTranslation(string $language, string $folder = ""): bool;
}