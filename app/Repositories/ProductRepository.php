<?php

namespace App\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param list<int> $productIds */
    public function findByIds(array $productIds): array
    {
        $productIds = array_values(array_filter(
            array_map('intval', $productIds),
            static fn (int $id): bool => $id > 0
        ));

        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $statement = $this->connection->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $statement->execute($productIds);

        return $statement->fetchAll();
    }

    public function findActiveStock(int $productId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, stock, is_active FROM products WHERE id = ? AND is_active = 1'
        );
        $statement->execute([$productId]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function findStockState(int $productId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT stock, is_active FROM products WHERE id = ?'
        );
        $statement->execute([$productId]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }
}
