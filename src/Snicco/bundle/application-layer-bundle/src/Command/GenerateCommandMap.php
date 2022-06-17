<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Webmozart\Assert\Assert;

use function array_filter;
use function count;
use function sprintf;

/**
 * @interal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\ApplicationLayer
 */
final class GenerateCommandMap
{
    /**
     * @var class-string[]
     */
    private array $application_services = [];

    /**
     * @param class-string[] $application_services
     */
    public function __construct(iterable $application_services)
    {
        $this->application_services = $application_services;
    }

    /**
     * @throws ReflectionException
     *
     * @return array<class-string,array{0:class-string, 1: string}>
     */
    public function __invoke(): array
    {
        $map = [];

        foreach ($this->application_services as $application_service) {
            $reflection_class = new ReflectionClass($application_service);
            /** @var ReflectionMethod[] $public_methods */
            $public_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);

            $public_methods = array_filter(
                $public_methods,
                fn (ReflectionMethod $method): bool => 1 === $method->getNumberOfParameters() && ! $method->isConstructor()
            );

            foreach ($public_methods as $public_method) {
                $params = $public_method->getParameters();
                Assert::true(
                    1 === count($params) && isset($params[0]),
                    sprintf('%s method must accept exactly one argument.', $public_method->name)
                );

                $command_type = $params[0]->getType();
                Assert::isInstanceOf($command_type, ReflectionNamedType::class);

                if ($command_type->isBuiltin()) {
                    continue;
                }

                $name = $command_type->getName();

                Assert::keyNotExists(
                    $map,
                    $name,
                    sprintf('Command %s can not be handled by two application services.', $name)
                );
                $map[$name] = [$application_service, $public_method->getName()];
            }
        }

        return $map;
    }
}
