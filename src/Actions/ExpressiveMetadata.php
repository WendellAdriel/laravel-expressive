<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use WendellAdriel\Expressive\Attributes\Map;
use WendellAdriel\Expressive\Attributes\Relationship;
use WendellAdriel\Expressive\Attributes\Virtual;
use WendellAdriel\Expressive\DTOs\PropertyMetadata;
use WendellAdriel\Expressive\Exceptions\InvalidExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\InvalidExpressiveMappingException;
use WendellAdriel\Expressive\Exceptions\NonNullablePropertyException;
use WendellAdriel\Expressive\Expressive;

final class ExpressiveMetadata
{
    /**
     * @return list<PropertyMetadata>
     */
    public function handle(string $class): array
    {
        if (! is_subclass_of($class, Expressive::class)) {
            throw InvalidExpressiveClassException::forClass($class);
        }

        $reflection = new ReflectionClass($class);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $this->ensureMappingAttributesArePublic($reflection->getName(), $property);

            if (! $property->isPublic() || $property->isStatic()) {
                continue;
            }

            $relationship = $property->getAttributes(Relationship::class) !== [];
            $virtual = $property->getAttributes(Virtual::class) !== [];
            $nullable = $this->isNullable($property);

            if (($relationship || $virtual) && ! $nullable) {
                throw NonNullablePropertyException::forProperty($reflection->getName(), $property->getName());
            }

            $map = $property->getAttributes(Map::class)[0] ?? null;
            $key = $map === null
                ? $this->defaultKey($property->getName(), $relationship)
                : $map->newInstance()->key;

            $properties[] = new PropertyMetadata(
                name: $property->getName(),
                key: $key,
                property: $property,
                relationship: $relationship,
                virtual: $virtual,
                nullable: $nullable,
            );
        }

        return $properties;
    }

    private function ensureMappingAttributesArePublic(string $class, ReflectionProperty $property): void
    {
        if ($property->isPublic()) {
            return;
        }

        if ($property->getAttributes(Map::class) !== []
            || $property->getAttributes(Relationship::class) !== []
            || $property->getAttributes(Virtual::class) !== []) {
            throw InvalidExpressiveMappingException::nonPublicProperty($class, $property->getName());
        }
    }

    private function defaultKey(string $property, bool $relationship): string
    {
        return $relationship
            ? $property
            : str($property)->snake()->toString();
    }

    private function isNullable(ReflectionProperty $property): bool
    {
        $type = $property->getType();

        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->allowsNull();
        }

        return $type instanceof ReflectionUnionType
            ? $type->allowsNull()
            : true;
    }
}
