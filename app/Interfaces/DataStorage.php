<?

namespace Robin\Interfaces;

interface DataStorage
{
    public function save(string $object_id, array $values): bool;
    public function remove(string $object_id): bool;
    public function read(string $object_id);
}