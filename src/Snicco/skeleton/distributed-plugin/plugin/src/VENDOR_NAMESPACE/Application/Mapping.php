<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application;

final class Mapping
{
    public static function asString(array $data, string $key): string
    {
        if (! isset($data[$key])) {
            return '';
        }
        if ('' === $data[$key]) {
            return '';
        }

        return (string) $data[$key];
    }

    public static function asStringOrNull(array $data, string $key): ?string
    {
        if (! isset($data[$key])) {
            return null;
        }
        if ('' === $data[$key]) {
            return null;
        }

        return (string) $data[$key];
    }

    public static function asInt(array $data, string $key): int
    {
        if (! isset($data[$key])) {
            return 0;
        }
        if ('' === $data[$key]) {
            return 0;
        }

        return (int) $data[$key];
    }

    public static function asIntOrNull(array $data, string $key): ?int
    {
        if (! isset($data[$key])) {
            return null;
        }
        if ('' === $data[$key]) {
            return null;
        }

        return (int) $data[$key];
    }

    public static function asBool(array $data, string $key): bool
    {
        if (! isset($data[$key])) {
            return false;
        }
        if ('' === $data[$key]) {
            return false;
        }

        return (bool) $data[$key];
    }

    public static function asBoolOrNull(array $data, string $key): ?bool
    {
        if (! isset($data[$key])) {
            return null;
        }
        if ('' === $data[$key]) {
            return null;
        }

        return (bool) $data[$key];
    }
}
