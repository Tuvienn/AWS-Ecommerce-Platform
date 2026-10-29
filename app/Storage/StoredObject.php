<?php

final class StoredObject
{
    public function __construct(
        public readonly string $key,
        public readonly int $size,
        public readonly string $mimeType,
    ) {
    }
}
