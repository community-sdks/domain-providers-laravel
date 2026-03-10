<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Support;

final class TldNormalizer
{
    public static function normalize(string $extension): string
    {
        return ltrim(strtolower(trim($extension)), '.');
    }
}
