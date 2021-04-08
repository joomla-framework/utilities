[The Utilities Package](../README.md)
# RegEx

Regular expressions can get quite complicated at times.
The RegEx package is designed to make them easier to create, more readable and easier to maintain.

## Using RegEx

As an example of the simple handling of RegEx, an implementation of a parser for HTTP URLs
according to [RFC 1738](https://www.ietf.org/rfc/rfc1738.txt) is shown here.
You find the BNF ([Backus-Naur-Form](https://en.wikipedia.org/wiki/Backus%E2%80%93Naur_form))
definition from Section 5 of the RFC in the comment for each element.

```php
use Joomla\Utilities\RegEx;

// For character classes, the native way is simplest
$digit      = '[0-9]';
$alpha      = '[a-zA-Z]';
$alphadigit = '[a-zA-Z0-9]';
$hex        = '[0-9a-fA-F]';
$safe       = '[$\-_.+]';
$extra      = '[!*\'(),]';

// BNF: digits = 1*digit
$digits = RegEx::oneOrMore($digit);

// BNF: unreserved = alpha | digit | safe | extra
$unreserved = RegEx::anyOf($alpha, $digit, $safe, $extra);

// BNF: escape = "%" hex hex
$escape = '%' . $hex . $hex;

// BNF: uchar = unreserved | escape
$uchar = RegEx::anyOf($unreserved, $escape);


// BNF: domainlabel = alphadigit | alphadigit *[ alphadigit | "-" ] alphadigit
$domainlabel = RegEx::anyOf(
    $alphadigit,
    $alphadigit . RegEx::noneOrMore(RegEx::anyOf(array($alphadigit, '-'))) . $alphadigit
);

// BNF: toplabel = alpha | alpha *[ alphadigit | "-" ] alphadigit
$toplabel = RegEx::anyOf(
    $alpha,
    $alpha . RegEx::noneOrMore(RegEx::anyOf(array($alphadigit, '-'))) . $alphadigit
);

// Add the toplabel to the result with key 'tld'
$toplabel = RegEx::capture($toplabel, 'tld');

// BNF: hostname = *[ domainlabel "." ] toplabel
$hostname = RegEx::noneOrMore($domainlabel . '\.') . $toplabel;

// Add the hostname to the result with key 'domain'
$hostname = RegEx::capture($hostname, 'domain');

// BNF: hostnumber = digits "." digits "." digits "." digits
$hostnumber = $digits . '\.' . $digits . '\.' . $digits . '\.' . $digits;

// Add the hostnumber to the result with key 'ip'
$hostnumber = RegEx::capture($hostnumber, 'ip');

// BNF: host = hostname | hostnumber
$host = RegEx::anyOf($hostname, $hostnumber);

// Add the host to the result with key 'host'
$host = RegEx::capture($host, 'host');

// BNF: port = digits
$port = $digits;

// Add the port to the result with key 'port'
$port = RegEx::capture($port, 'port');

// BNF: hostport = host [ ":" port ]
$hostport = $host . RegEx::optional(':' . $port);

// BNF: hsegment = *[ uchar | ";" | ":" | "@" | "&" | "=" ]
$hsegment = RegEx::noneOrMore(RegEx::anyOf($uchar, '[;:@&=]'));

// BNF: hpath = hsegment *[ "/" hsegment ]
$hpath = $hsegment . RegEx::noneOrMore('/' . $hsegment);

// Add the hpath to the result with key 'path'
$hpath = RegEx::capture($hpath, 'path');

// BNF: search = *[ uchar | ";" | ":" | "@" | "&" | "=" ]
$search = RegEx::noneOrMore(RegEx::anyOf(array($uchar, '[;:@&=]')));

// Add the search to the result with key 'query'
$search = RegEx::capture($search, 'query');

// BNF: httpurl = "http://" hostport [ "/" hpath [ "?" search ]]
$httpurl = 'http://' . $hostport . RegEx::optional('/' . $hpath) . RegEx::optional('\?' . $search);

$regex = '~^' . $httpurl . '$~';
$subject = 'http://www.example.com:8080/index.php?foo=bar';

$parts = RegEx::match($regex, $subject);
print_r($parts);
```

Result:

```
Array
(
    [host] => www.example.com
    [domain] => www.example.com
    [tld] => com
    [port] => 8080
    [path] => index.php
    [query] => foo=bar
)
```
### match

As you can see from the example above, `RegEx::match()` returns the matches that have been
appropriately marked using `RegEx::capture()`.
Only the matches that have a value are returned.
If the Regular Expression does not match, the result is an empty array.

### capture

Assign a key to an expression.

```php
use Joomla\Utilities\RegEx;

$regex = RegEx::capture('[0-9]+', 'number');
print_r(RegEx::match($regex, 'abc123def'));
```
Result:
```
Array
(
    [number] => 123
)
```

### optional

Add a 'zero or one' quantifier to an expression.

```php
use Joomla\Utilities\RegEx;

print(RegEx::optional('regex')); // (?:regex)?
```

### oneOrMore

Add a 'one or more' quantifier to an expression.

```php
use Joomla\Utilities\RegEx;

print(RegEx::oneOrMore('regex')); // (?:regex)+
```

### noneOrMore

Add a 'zero or more' quantifier to an expression.

```php
use Joomla\Utilities\RegEx;

print(RegEx::noneOrMore('regex')); // (?:regex)*
```

### anyOf

Define a list of alternative expressions.

```php
use Joomla\Utilities\RegEx;

print(RegEx::anyOf('a', 'b', 'c')); // (?:a|b|c)

$array = array('a', 'b', 'c');
print(RegEx::anyOf($array)); // (?:a|b|c)
```
