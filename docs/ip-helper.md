[The Utilities Package](../README.md)
# IpHelper

## Using IpHelper

### Overwriting `REMOTE_ADDR`

`IpHelper::workaroundIPIssues()` writes the address from `getIp()` into `$_SERVER['REMOTE_ADDR']` and
keeps the original value in `$_SERVER['JOOMLA_REMOTE_ADDR']`. The method is deprecated and will be
removed in 5.0: overwriting `REMOTE_ADDR` changes what every other component in the process sees.
Pass the address from `getIp()` to the code that needs it instead.
