[The Utilities Package](../README.md)
# IpHelper

## Using IpHelper

### Reading the client address

```php
use Joomla\Utilities\IpHelper;

$ip = IpHelper::getIp();
```

`getIp()` reads `REMOTE_ADDR`. If the value is a comma- or space-separated list, the first entry is
used. The address must be a valid IPv4 or IPv6 address; otherwise `getIp()` returns an empty string.
The result is cached for the rest of the request; `IpHelper::setIp()` replaces it.

### Clients behind a proxy

Behind a reverse proxy or load balancer, `REMOTE_ADDR` is the proxy's address. To read the client's
address from the `X-Forwarded-For` header instead, allow overrides and name the proxies you trust:

```php
IpHelper::setAllowIpOverrides(true);
IpHelper::setTrustedProxies(['192.0.2.10', '198.51.100.0/24']);
```

`setTrustedProxies()` takes the same expressions as `IPinList()`: addresses, ranges and CIDR blocks.
The header is then only used when the request comes from one of these proxies. It is read from the
right: entries added by trusted proxies are skipped, and the first other entry is the client's
address. Entries further left were written by the client and cannot be trusted. If an entry is not a
valid IP address, the walk stops at the last trusted proxy.

Without trusted proxies, `setAllowIpOverrides(true)` uses the first entry of `X-Forwarded-For`,
whoever sent the header. Any client can therefore choose its own address, so only do this if the
proxy in front of the application overwrites the header. A header value that is not a valid IP
address is ignored and `REMOTE_ADDR` is used instead.

Reading the address from the `Client-Ip` header is deprecated and will be removed in 5.0. Use
`X-Forwarded-For` together with `setTrustedProxies()`.
