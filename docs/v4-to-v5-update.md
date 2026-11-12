## Updating from Version 4 to Version 5

The following changes were made to the Utilities package between version 4 and version 5.

### Native parameter, return and property types

All public methods of `ArrayHelper`, `IpHelper` and `RegEx` now declare native parameter and return
types, and `IpHelper`'s properties are typed. Values the methods already documented keep working.
Values they did not document now raise a `TypeError` instead of being coerced or failing later:

| Method | Change |
|---|---|
| `ArrayHelper::getValue()` | `$array` must be an array or `ArrayAccess`. Anything else throws a `TypeError` instead of an `InvalidArgumentException`. `$name` is `string\|int`. |
| `ArrayHelper::fromObject()` | `$recurse` is `bool`; `null` is no longer accepted. `$regex` is `?string`. |
| `ArrayHelper::toObject()`, `toString()`, `arraySearch()`, `flatten()` | Typed `$class`, `$recursive`, `$keepOuterKey`, `$needle`, `$caseSensitive`, `$separator` and `$prefix`. `arraySearch()` returns `string\|int\|false`. |
| `ArrayHelper::addColumn()`, `dropColumn()`, `getColumn()`, `pivot()`, `sortObjects()` | Column and key names are `string\|int` (nullable where `null` has a meaning). `sortObjects()` takes `string\|int\|array $k`, `int\|array $direction`, `bool\|array $caseSensitive` and `string\|array\|bool $locale`. |
| `IpHelper::IPinList()` | `IPinList(string $ip, array\|string $ipTable = '')`. Passing `null` as the address throws a `TypeError` instead of returning `false`. |
| `IpHelper::getIp()`, `setIp()`, `isIPv6()`, `setAllowIpOverrides()` | `getIp(): string`, `setIp(?string $ip)`, `isIPv6(string $ip)`, `setAllowIpOverrides(bool $newState)`. |
| `RegEx` | All methods take and return `string`; `match()` returns `array`; `capture()` takes `?string $as`. |
| `RegEx::anyOf()` | Now variadic: `anyOf(string\|array ...$regexList)`. Calls are unchanged: `anyOf('a', 'b')` and `anyOf(['a', 'b'])` both still work. A subclass that overrides `anyOf()` must declare a variadic parameter as well. |
