# Updating from v3 to v4

Release 4.0.0 raises the PHP requirement. Nothing else changed in `src/`.

## At a glance

| | v3 (3.0.1) | v4 (4.0.0) |
|---|---|---|
| PHP | `^8.1.0` | `^8.3.0` |
| Public API | — | unchanged |

## Minimum supported PHP version raised

All Framework packages now require **PHP 8.3** or newer.

## No API changes

`git diff 3.0.1 HEAD -- src/` is empty. `ArrayHelper`, `IpHelper` and `RegEx` are byte-for-byte
identical to 3.0.1.

## Dependency changes

| Package | v3 (3.0.1) | v4 (4.0.0) |
|---|---|---|
| `php` | `^8.1.0` | `^8.3.0` |
| `joomla/string` | `^3.0` | `^4.0` |

## Worth doing while you are here

If your application calls `IpHelper::IPinList()` for access control, this is a good moment to
review it — the comparisons it performs are not type-safe, and distinct addresses can compare
equal. See [ip-helper.md](ip-helper.md#known-limitations).
