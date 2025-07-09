## Updating from Version 2 to Version 3

The following changes were made to the Utilities package between version 2 and version 3.

### Minimum supported PHP version raised

All Framework packages now require PHP 8.1 or newer.

### Enforce input types

* The `Joomla\Utilities\ArrayHelper::getValue()` method now requires the `$type` parameter to be a string.
* The `Joomla\Utilities\ArrayHelper::toString()` method now requires the `$innerGlue` and `$outerGlue` parameters to be strings.
