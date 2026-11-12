[The Utilities Package](index.md)

# IpHelper

`Joomla\Utilities\IpHelper` determines the client's IP address and matches an address against a
list of addresses, ranges and CIDR blocks. All methods are static.

## Reading the client address

```php
use Joomla\Utilities\IpHelper;

$ip = IpHelper::getIp();          // '203.0.113.7'
IpHelper::setIp('203.0.113.7');   // override, e.g. in tests
```

By default `getIp()` reads `$_SERVER['REMOTE_ADDR']`. The value is cached for the process after the
first call.

## Proxy headers

```php
IpHelper::setAllowIpOverrides(true);
```

With overrides enabled, `getIp()` prefers `X-Forwarded-For`, then `Client-Ip`, before falling back
to `REMOTE_ADDR`.

> **Only enable this if you terminate the proxy chain yourself.** The header is returned exactly as
> the client sent it: it is not split, not validated, and not checked against a list of trusted
> proxies. Any client can therefore choose the address the application sees, which makes every
> IP-based decision — rate limits, allowlists, audit logs — meaningless. `Client-Ip` in particular
> is a header no legitimate proxy sets.
>
> A correct implementation reads `X-Forwarded-For` from the right, discarding entries contributed
> by proxies you trust, and stops at the first address you do not. This package does not do that,
> so do it before calling `setIp()`:
>
> ```php
> $chain = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
> $client = array_pop($chain);
>
> while ($client !== null && IpHelper::IPinList($client, $trustedProxies)) {
>     $client = array_pop($chain);
> }
>
> IpHelper::setIp($client ?? $_SERVER['REMOTE_ADDR']);
> ```

## Overwriting `REMOTE_ADDR`

```php
IpHelper::workaroundIPIssues();
```

Writes the detected address into `$_SERVER['REMOTE_ADDR']` and preserves the original in
`$_SERVER['JOOMLA_REMOTE_ADDR']`.

This changes what every other component in the process sees, including code that has no idea this
package exists. Prefer passing the address explicitly to whatever needs it.

## Matching against a list

```php
IpHelper::IPinList('192.168.1.10', '192.168.1.0/24');
IpHelper::IPinList($ip, '10.0.0.1, 10.0.0.2, 172.16.0.0/12');
IpHelper::IPinList($ip, ['10.0.0.0/8', '192.168.1.1-192.168.1.50']);
IpHelper::IPinList($ip, '192.168.');            // partial IPv4 prefix
```

Accepted entry forms:

| Form | Example | Notes |
|---|---|---|
| Single address | `192.168.1.5` | IPv4 and IPv6 |
| Range | `192.168.1.1-192.168.1.50` | IPv4 only |
| CIDR | `10.0.0.0/8` | IPv4 and IPv6 |
| Netmask | `10.0.0.0/255.0.0.0` | IPv4 only |
| Partial prefix | `192.168.` | IPv4 only, trailing dot required |

The list may be a comma-separated string or an array. `0.0.0.0` and an empty address always return
`false`.

## Known limitations

**Comparisons are not type-safe.** The single-address and range branches use `==`, `>=` and `<=`
on the binary strings from `inet_pton()`. PHP compares two numeric-looking strings numerically, and
binary address data frequently is numeric-looking, so distinct addresses can compare equal and
range checks can give the wrong answer:

```php
inet_pton('49.101.51.48') == inet_pton('49.69.51.48');   // true - two different addresses
```

Because of this, do not use `IPinList()` as the only gate in front of something that matters. If
you need reliable matching, compare with `strcmp()` yourself or use a dedicated library such as
`symfony/http-foundation`'s `IpUtils`.

**A malformed entry can abort the call.** The CIDR branch passes the mask straight to `substr()`,
so a typo like `10.0.0.0/abc` raises a `TypeError` rather than being skipped. Validate the list
when it comes from configuration.

**IPv6 support is partial.** Single addresses and CIDR blocks work; ranges and partial prefixes are
IPv4 only.
