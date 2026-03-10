<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Throwable;

final class EncryptedJsonCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $json = Crypt::decryptString((string) $value);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('The "%s" attribute must be an array.', $key));
        }

        try {
            $json = json_encode($value, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Unable to encode provider config JSON.', previous: $exception);
        }

        return Crypt::encryptString($json);
    }
}
