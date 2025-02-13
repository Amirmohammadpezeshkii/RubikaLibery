<p align="center">
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/dt/rubi/lib" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/v/rubi/lib" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/l/rubi/lib" alt="License"></a>
</p>

# Rubika-PHP
*Rubika-PHP is a PHP Library for interaction with rubika (social network)

## Installation
```
composer require rubi/lib:dev-main
```
## Usage
```php
set_time_limit(0);
require_once __DIR__ . '/vendor/autoload.php';

use rubi\rubika;

$account = new rubika(989123456789); // Only without zero and with area code 98
$account->onUpdate(function (array $update) use ($account) {
    if (isset($update['message_updates'])) {
        $message = $update['message_updates'];
        // other code
    }
});
```
## Example
* coming soon...

## About Us
This library can be used for easy interaction with Rubika just like official applications.

## Disclaimer


<b>This library is free and can not be sold.</b>


<b>The responsibility for using this library lies with the individual</b>


## License
Rubika-PHP is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
