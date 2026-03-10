<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Exceptions;

use InvalidArgumentException;

final class UnsupportedProviderDriverException extends InvalidArgumentException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf('Unsupported provider driver "%s".', $driver));
    }
}
