# Updating from v2 to v3

Release 3.0.0 raises the PHP requirement, reformats the codebase and tightens two parameter types.

## At a glance

| | v2 (2.0.x) | v3 (3.0.0) |
|---|---|---|
| PHP | `^7.2.5 \| ^8.0` | `^8.1.0` |
| `ArrayHelper::getValue()` `$type` | untyped | `string` |
| `ArrayHelper::toString()` glue parameters | untyped | `string` |
| Coding style | Joomla Coding Standard | PSR-12 |

## Minimum supported PHP version raised

All Framework packages now require **PHP 8.1** or newer.

## Enforced parameter types

Two `ArrayHelper` methods now declare `string` where they previously accepted anything:

```php
// v2
public static function getValue($array, $name, $default = null, $type = '')
public static function toString(array $array, $innerGlue = '=', $outerGlue = ' ', $keepOuterKey = false)

// v3
public static function getValue($array, $name, $default = null, string $type = '')
public static function toString(array $array, string $innerGlue = '=', string $outerGlue = ' ', $keepOuterKey = false)
```

Callers that passed a string already — the normal case — are unaffected. Passing `null` explicitly
now raises a `TypeError`:

```php
// Fails in v3
ArrayHelper::getValue($data, 'key', null, null);

// Use the default instead
ArrayHelper::getValue($data, 'key', null);
```

## No other API changes

`IpHelper` and `RegEx` are unchanged, as is the rest of `ArrayHelper`.

## Codebase converted to PSR-12

The package was reformatted from the Joomla Coding Standard to PSR-12. This touches nearly every
line and changes no behaviour.

## Dependency changes

| Package | v2 (2.0.x) | v3 (3.0.0) |
|---|---|---|
| `php` | `^7.2.5 \| ^8.0` | `^8.1.0` |
| `joomla/string` | `^1.3 \| ^2.0` | `^3.0` |
