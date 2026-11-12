## Updating from Version 4 to Version 5

The following changes were made to the Utilities package between version 4 and version 5.

### `IpHelper::workaroundIPIssues()` removed

The method overwrote `$_SERVER['REMOTE_ADDR']` with the detected address and kept the original in
`$_SERVER['JOOMLA_REMOTE_ADDR']`. It was deprecated in 4.x. Pass the address from `IpHelper::getIp()` to
the code that needs it instead:

```php
// Before
IpHelper::workaroundIPIssues();
$ip = $_SERVER['REMOTE_ADDR'];

// After
$ip = IpHelper::getIp();
```

### The `Client-Ip` header is no longer read

With `IpHelper::setAllowIpOverrides(true)`, `getIp()` used the `Client-Ip` header when there was no
`X-Forwarded-For` header. No legitimate proxy sets `Client-Ip`, so any client could choose its address
with it. It was deprecated in 4.x and is now ignored. Configure your proxy to send `X-Forwarded-For`.
