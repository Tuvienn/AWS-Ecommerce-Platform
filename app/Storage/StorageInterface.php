<?php

interface StorageInterface
{
    /** @param array<string,mixed> $uploadedFile @param array<string,string|int> $metadata */
    public function putUploadedFile(array $uploadedFile, string $key, array $metadata = []): StoredObject;

    /** @param array<string,string|int> $metadata */
    public function putFile(string $sourcePath, string $key, array $metadata = []): StoredObject;

    public function read(string $key): string;

    public function url(string $key): string;

    public function exists(string $key): bool;

    public function delete(string $key): void;
}
