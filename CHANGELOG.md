# Changelog

## Unreleased (Next Major)

### Breaking Changes
- Request DTOs now use spec-backed enums and typed date objects for closed-set and temporal fields instead of raw strings or loose numeric unions.
- Response DTOs now expose enums, `DateTimeImmutable`, typed nested DTOs, typed lists, and `ExpandableResource<T>` instead of `StructuredObject`, `StructuredList`, `ExpandableValue`, and `int|float` unions.
- Response hydration now fails fast with `Creem\Exception\HydrationException` when required payload fields are missing or malformed.
- `CreateProductRequest` and `CreateCheckoutRequest` no longer expose the deprecated `customField` alias; use `customFields`.

### Migration Notes
- Replace request string literals such as currency codes, billing types, and stats intervals with the matching `Creem\Enum\*` cases.
- Expect typed response properties where raw strings or generic containers were previously returned.
- Update error handling if you relied on malformed response payloads being silently coerced to `null`.
