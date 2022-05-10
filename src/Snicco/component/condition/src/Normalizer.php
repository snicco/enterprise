<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

use function array_filter;
use function array_values;
use function class_exists;
use function class_implements;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;

final class Normalizer
{
    public static function normalize(Condition $condition): array
    {
        return $condition->toArray();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function denormalize(array $condition): Condition
    {
        if (! self::hasCorrectStructure($condition)) {
            $as_json = self::jsonEncodeFailure($condition);

            throw new InvalidArgumentException("Invalid condition structure.\nJson: [{$as_json}]");
        }

        self::assertOnlyOneInterface($condition);

        $class = $condition[0];
        $args = $condition[1];

        if (self::isContainingCondition($class)) {
            return self::instantiateContainingCondition($class, $args);
        }

        if (self::isAggregateCondition($class)) {
            return self::instantiateAggregateCondition($class, $args);
        }

        return (new ReflectionClass($condition[0]))->newInstanceArgs($condition[1]);
    }

    /**
     * @psalm-assert-if-true array{0: class-string<Condition>, 1: list} $condition
     */
    private static function hasCorrectStructure(array $condition): bool
    {
        if (! isset($condition[0])) {
            return false;
        }

        if (! isset($condition[1])) {
            return false;
        }

        if (! is_string($condition[0])) {
            return false;
        }

        if (! self::isInterface($condition[0], Condition::class)) {
            return false;
        }

        if (2 !== count($condition)) {
            return false;
        }

        if (! is_array($condition[1])) {
            return false;
        }

        return array_values($condition[1]) === $condition[1];
    }

    /**
     * @param class-string<Condition> $class
     *
     * @psalm-assert-if-true class-string<ContainingCondition> $class
     */
    private static function isContainingCondition(string $class): bool
    {
        return self::isInterface($class, ContainingCondition::class);
    }

    /**
     * @param class-string<Condition> $class
     *
     * @psalm-assert-if-true class-string<AggregateCondition> $class
     */
    private static function isAggregateCondition(string $class): bool
    {
        return self::isInterface($class, AggregateCondition::class);
    }

    /**
     * @param class-string<ContainingCondition> $class
     * @param list                              $args
     *
     * @throws ReflectionException
     */
    private static function instantiateContainingCondition(string $class, array $args): ContainingCondition
    {
        if (! self::hasCorrectStructure($args)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Arguments of containing condition %s are not valid.\nJson: %s",
                    $class,
                    self::jsonEncodeFailure($args)
                )
            );
        }

        return (new ReflectionClass($class))->newInstance(self::denormalize($args));
    }

    /**
     * @param class-string<AggregateCondition> $class
     * @param list                             $args
     *
     * @throws ReflectionException
     */
    private static function instantiateAggregateCondition(string $class, array $args): AggregateCondition
    {
        $valid_child_conditions = array_filter(
            $args,
            fn ($c): bool => is_array($c) && self::hasCorrectStructure($c)
        );

        if ($valid_child_conditions !== $args) {
            throw new InvalidArgumentException(
                sprintf(
                    "Arguments of aggregate condition %s are not valid.\nJson: %s",
                    $class,
                    self::jsonEncodeFailure($args)
                )
            );
        }

        $child_conditions = [];

        /** @var array{0: class-string<Condition>, 1: list} $condition */
        foreach ($args as $condition) {
            $child_conditions[] = self::denormalize($condition);
        }

        return (new ReflectionClass($class))->newInstance($child_conditions);
    }

    /**
     * @param array<string, list> $condition
     *
     * @psalm-param array{0: class-string<Condition>, 1: list} $condition
     */
    private static function assertOnlyOneInterface(array $condition): void
    {
        $containing = self::isContainingCondition($condition[0]);
        $aggregate = self::isAggregateCondition($condition[0]);
        if (! $containing) {
            return;
        }

        if (! $aggregate) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Class %s can not implement both %s and %s.',
                $condition[0],
                AggregateCondition::class,
                ContainingCondition::class,
            )
        );
    }

    private static function jsonEncodeFailure(array $condition): string
    {
        return (string) @json_encode($condition, JSON_PRETTY_PRINT);
    }

    /**
     * @param class-string $expected_interface
     */
    private static function isInterface(string $class, string $expected_interface): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $interfaces = (array) class_implements($class);

        return in_array($expected_interface, $interfaces, true);
    }
}
