<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use BadMethodCallException;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Snicco\Enterprise\Component\Condition\AggregateCondition;
use Snicco\Enterprise\Component\Condition\All;
use Snicco\Enterprise\Component\Condition\Any;
use Snicco\Enterprise\Component\Condition\ContainingCondition;
use Snicco\Enterprise\Component\Condition\Context;
use Snicco\Enterprise\Component\Condition\HTTP\ExactPath;
use Snicco\Enterprise\Component\Condition\None;
use Snicco\Enterprise\Component\Condition\Normalizer;
use Snicco\Enterprise\Component\Condition\Not;
use Snicco\Enterprise\Component\Condition\WP\AdminPageStartsWith;
use Snicco\Enterprise\Component\Condition\WP\IsFrontend;
use stdClass;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class NormalizerTest extends Unit
{
    /**
     * @test
     */
    public function that_simple_conditions_can_be_serialized_and_deserialized(): void
    {
        $simple = new ExactPath('/foo');
        $array = Normalizer::normalize($simple);
        $this->assertEquals($simple, Normalizer::denormalize($array));

        $with_array_args = new AdminPageStartsWith(['/foo', '/bar']);
        $array = Normalizer::normalize($with_array_args);
        $this->assertEquals($with_array_args, Normalizer::denormalize($array));

        $with_array_args = new AdminPageStartsWith([]);
        $array = Normalizer::normalize($with_array_args);
        $this->assertEquals($with_array_args, Normalizer::denormalize($array));

        $no_args = new IsFrontend();
        $array = Normalizer::normalize($no_args);
        $this->assertEquals($no_args, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_a_negated_condition_can_be_serialized_and_deserialized(): void
    {
        $negated = new Not(new ExactPath('/foo'));
        $array = Normalizer::normalize($negated);
        $this->assertEquals($negated, Normalizer::denormalize($array));

        $negated = new Not(new AdminPageStartsWith(['/foo', '/bar']));
        $array = Normalizer::normalize($negated);
        $this->assertEquals($negated, Normalizer::denormalize($array));

        $negated = new Not(new IsFrontend());
        $array = Normalizer::normalize($negated);
        $this->assertEquals($negated, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_it_works_with_aggregate_conditions(): void
    {
        $condition = new All([new ExactPath('/foo'), new ExactPath('/bar')]);

        $array = Normalizer::normalize($condition);
        $this->assertEquals($condition, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_it_works_with_nested_aggregate_conditions(): void
    {
        $condition = new All([
            new ExactPath('/foo'),
            new ExactPath('/bar'),
            new All([new ExactPath('/baz'), new ExactPath('/biz')]),
        ]);

        $array = Normalizer::normalize($condition);
        $this->assertEquals($condition, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_it_works_with_an_aggregate_condition_inside_a_contained_condition(): void
    {
        $condition = new Not(new All([new ExactPath('/foo'), new ExactPath('/bar')]));

        $array = Normalizer::normalize($condition);
        $this->assertEquals($condition, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_it_works_with_very_complex_conditions(): void
    {
        $tree = [
            new ExactPath('/foo'),
            new AdminPageStartsWith('/bar'),
            new Not(new ExactPath('/baz')),
            new None([new ExactPath('/boo')]),
        ];

        $condition = new Any([new All($tree), new All([new Any($tree), new Not(new All($tree))])]);

        $array = Normalizer::normalize($condition);

        $this->assertEquals($condition, Normalizer::denormalize($array));

        $json = json_encode($array, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $condition,
            Normalizer::denormalize((array) json_decode($json, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * @test
     */
    public function that_conditions_without_constructor_work(): void
    {
        $condition = new IsFrontend();

        $array = Normalizer::normalize($condition);
        $this->assertEquals($condition, Normalizer::denormalize($array));
    }

    /**
     * @test
     */
    public function that_a_condition_can_not_implement_multiple_interfaces(): void
    {
        $condition = new InvalidCondition();
        $array = Normalizer::normalize($condition);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can not implement both');
        Normalizer::denormalize($array);
    }

    /**
     * @test
     */
    public function that_invalid_condition_structure_throws_an_exception(): void
    {
        $invalid_structure = [stdClass::class, ['foo', 'bar']];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid condition structure.');
        Normalizer::denormalize($invalid_structure);
    }

    /**
     * @test
     */
    public function that_invalid_containing_structure_throws_an_exception(): void
    {
        $invalid_structure = [Not::class, ['foo', 'bar']];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Arguments of containing condition');
        Normalizer::denormalize($invalid_structure);
    }

    /**
     * @test
     */
    public function that_invalid_aggregate_structure_throws_an_exception(): void
    {
        $invalid_structure = [All::class, [[ExactPath::class, ['foo']], [ExactPath::class, ['foo']], ['foo']]];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Arguments of aggregate condition');
        Normalizer::denormalize($invalid_structure);
    }
}

final class InvalidCondition implements ContainingCondition, AggregateCondition
{
    public function isTruthy(Context $context): bool
    {
        throw new BadMethodCallException(__METHOD__);
    }

    /**
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function toArray(): array
    {
        return [self::class, []];
    }
}
