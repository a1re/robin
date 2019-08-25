<?

namespace Robin\Interfaces;
    
interface ParsingEngine
{
    public function getElement(string $name, $args = false);
    public function getMethods(): array;
}