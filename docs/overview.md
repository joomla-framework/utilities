# Overview

The Utilities package holds three unrelated static helper classes that did not warrant a package
each.

```bash
composer require joomla/utilities
```

| Class | Purpose | Detail page |
|---|---|---|
| `Joomla\Utilities\ArrayHelper` | Array and object-list manipulation | [array-helper.md](array-helper.md) |
| `Joomla\Utilities\IpHelper` | Reading and matching client IP addresses | [ip-helper.md](ip-helper.md) |
| `Joomla\Utilities\RegEx` | Small building blocks for regular expressions | [regex.md](regex.md) |

All three are static-only; `ArrayHelper` and `RegEx` have a private constructor.

## `ArrayHelper` at a glance

```php
use Joomla\Utilities\ArrayHelper;

ArrayHelper::toInteger($input);                       // cast every element to int
ArrayHelper::toObject($array);                        // array to stdClass, recursively
ArrayHelper::fromObject($object);                     // object to array, recursively
ArrayHelper::toString($array, '=', ' ');              // 'key="value" key2="value2"'

ArrayHelper::getValue($array, 'key', 'default', 'string');
ArrayHelper::getColumn($rows, 'title', 'id');         // one column, optionally keyed
ArrayHelper::addColumn($rows, $values, 'new');
ArrayHelper::dropColumn($rows, 'unwanted');
ArrayHelper::pivot($rows, 'category');                // group by a key
ArrayHelper::invert($map);                            // values become keys
ArrayHelper::sortObjects($objects, 'title');
ArrayHelper::arraySearch($needle, $haystack, false);  // case-insensitive search
ArrayHelper::flatten($nested, '.');                   // 'a.b.c' => value
ArrayHelper::mergeRecursive($a, $b, $c);
```

See [array-helper.md](array-helper.md) for the full signatures and examples.

## `IpHelper` at a glance

```php
use Joomla\Utilities\IpHelper;

IpHelper::getIp();                                    // the client address
IpHelper::isIPv6($ip);
IpHelper::IPinList($ip, '10.0.0.0/8, 192.168.1.5');   // allow/deny list matching
```

See [ip-helper.md](ip-helper.md), and read the caveats below before using `IPinList()` for access
control.

## `RegEx` at a glance

```php
use Joomla\Utilities\RegEx;

$pattern = RegEx::capture('\d+', 'id');       // '(?P<id>\d+)'
$pattern = RegEx::optional($pattern);         // '(?:…)?'
$pattern = RegEx::anyOf('foo', 'bar');        // '(?:foo|bar)'

$values = RegEx::match('#^/items/' . RegEx::capture('\d+', 'id') . '$#', $path);
```

See [regex.md](regex.md).

## Things to know before you build on this

**`IpHelper::IPinList()` compares binary addresses loosely.** The single-address and range branches
use `==`, `>=` and `<=` on the binary strings returned by `inet_pton()`. PHP compares two strings
numerically when both look like numbers, and binary IP data often does, so distinct addresses can
compare equal:

```php
inet_pton('49.101.51.48') == inet_pton('49.69.51.48');   // true - different addresses
```

If you use the function for an allowlist or a denylist, treat that as a real risk and compare
yourself with `strcmp()`, or use a maintained IP library.

**`IpHelper::getIp()` trusts `X-Forwarded-For` when overrides are on.** With
`IpHelper::setAllowIpOverrides(true)`, the header is returned as-is — the whole comma-separated
chain, unvalidated, with no notion of which proxies are trusted. Any client can therefore choose
its own address. Leave overrides off unless you terminate the chain yourself, and never build
access control on the result without a trusted-proxy list.

**`IpHelper::workaroundIPIssues()` rewrites `$_SERVER['REMOTE_ADDR']`.** After that call, every
other piece of code in the process — logging, sessions, third-party libraries — sees the value this
package chose. The original is preserved in `$_SERVER['JOOMLA_REMOTE_ADDR']`, which nothing else
knows about.

**`ArrayHelper::getValue()` with a type breaks on array values.** The `int`/`float` paths run
`preg_match()` on the value, which raises a `TypeError` when the value is an array. Check the type
before asking for a cast if the source can hold nested data.

**`ArrayHelper::arrayUnique()` round-trips through `serialize()`.** Objects in the array are
therefore re-instantiated — `__wakeup()` and later `__destruct()` run — and closures or resources
make the call throw. Use it on scalar arrays only.

**`RegEx::match()` drops captures that are `'0'` or empty.** The result is filtered with
`!empty($value)`, so a group that legitimately matched `0` disappears from the returned array.
