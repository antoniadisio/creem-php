<?php

declare(strict_types=1);

namespace Creem\Internal\Hydration;

use BackedEnum;
use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\ExpandableValue;
use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;
use Creem\Exception\HydrationException;
use DateTimeImmutable;
use DateTimeInterface;

use function array_filter;
use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function sprintf;

final class Payload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function string(array $payload, string $key, ?string $dto = null, bool $required = false): ?string
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (! self::isStrict($dto, $required)) {
            return null;
        }

        throw HydrationException::invalidField(self::dtoName($dto), $key, 'string', $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function number(array $payload, string $key, ?string $dto = null, bool $required = false): int|float|null
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_numeric($value)) {
            if (! self::isStrict($dto, $required)) {
                return null;
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'int|float', $value);
        }

        if (self::isStrict($dto, $required)) {
            throw HydrationException::invalidField(self::dtoName($dto), $key, 'int|float', $value);
        }

        return is_float($value + 0) ? (float) $value : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function bool(array $payload, string $key, ?string $dto = null, bool $required = false): ?bool
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (! self::isStrict($dto, $required)) {
            return null;
        }

        throw HydrationException::invalidField(self::dtoName($dto), $key, 'bool', $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function object(array $payload, string $key, ?string $dto = null, bool $required = false): ?StructuredObject
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            if (! self::isStrict($dto, $required)) {
                return null;
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'object', $value);
        }

        return StructuredObject::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function list(array $payload, string $key, ?string $dto = null, bool $required = false): StructuredList
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return StructuredList::fromArray([]);
        }

        if (! is_array($value) || ! array_is_list($value)) {
            if (! self::isStrict($dto, $required)) {
                return StructuredList::fromArray([]);
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'list', $value);
        }

        return StructuredList::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function expandable(array $payload, string $key): ?ExpandableValue
    {
        return ExpandableValue::fromValue($payload[$key] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function integer(array $payload, string $key, string $dto, bool $required = false): ?int
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_int($value)) {
            throw HydrationException::invalidField($dto, $key, 'int', $value);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function decimal(array $payload, string $key, string $dto, bool $required = false): ?float
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw HydrationException::invalidField($dto, $key, 'float', $value);
        }

        return (float) $value;
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param  array<string, mixed>  $payload
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    public static function enum(
        array $payload,
        string $key,
        string $dto,
        string $enumClass,
        bool $required = false,
    ): ?BackedEnum {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw HydrationException::invalidField($dto, $key, sprintf('valid %s', $enumClass), $value);
        }

        $enum = $enumClass::tryFrom($value);

        if ($enum === null) {
            throw HydrationException::invalidField($dto, $key, sprintf('valid %s', $enumClass), $value);
        }

        return $enum;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function dateTime(array $payload, string $key, string $dto, bool $required = false): ?DateTimeImmutable
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (! is_string($value)) {
            throw HydrationException::invalidField($dto, $key, 'date-time string', $value);
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw HydrationException::forField($dto, $key, 'expected a valid date-time string', $value);
        }
    }

    /**
     * @template TObject of object
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TObject  $mapper
     * @return TObject|null
     */
    public static function typedObject(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): ?object {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'object', $value);
        }

        return $mapper($value);
    }

    /**
     * @template TItem
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(mixed): TItem  $mapper
     * @return list<TItem>
     */
    public static function typedList(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): array {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return [];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'list', $value);
        }

        $mapped = [];

        foreach ($value as $item) {
            $mapped[] = $mapper($item);
        }

        return $mapped;
    }

    /**
     * @template TObject of object
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TObject  $mapper
     * @return ExpandableResource<TObject>|null
     */
    public static function expandableResource(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): ?ExpandableResource {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return ExpandableResource::fromId($value);
        }

        if (! is_array($value) || array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'expandable resource string or object', $value);
        }

        $id = self::string($value, 'id', $dto, true);

        if ($id === null) {
            throw HydrationException::missingField($dto, $key);
        }

        return ExpandableResource::expanded($id, $mapper($value));
    }

    /**
     * @template TItem
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TItem  $mapper
     * @return Page<TItem>
     */
    public static function page(array $payload, callable $mapper): Page
    {
        $items = $payload['items'] ?? [];
        $mapped = [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                /** @var array<string, mixed> $item */
                $mapped[] = $mapper($item);
            }
        }

        return new Page($mapped, self::pagination($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function pagination(array $payload): ?Pagination
    {
        $pagination = self::typedObject(
            $payload,
            'pagination',
            Pagination::class,
            static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        );

        if (! $pagination instanceof StructuredObject) {
            return null;
        }

        $values = array_filter(
            $pagination->all(),
            static fn (mixed $value): bool => true,
        );

        /** @var array<string, mixed> $values */
        return new Pagination(
            self::integer($values, 'total_records', Pagination::class),
            self::integer($values, 'total_pages', Pagination::class),
            self::integer($values, 'current_page', Pagination::class),
            self::integer($values, 'next_page', Pagination::class),
            self::integer($values, 'prev_page', Pagination::class),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function value(array $payload, string $key, ?string $dto, bool $required): mixed
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            if ($required) {
                throw HydrationException::missingField(self::dtoName($dto), $key);
            }

            return null;
        }

        return $payload[$key];
    }

    private static function dtoName(?string $dto): string
    {
        return $dto ?? 'payload';
    }

    private static function isStrict(?string $dto, bool $required): bool
    {
        return $dto !== null || $required;
    }
}
