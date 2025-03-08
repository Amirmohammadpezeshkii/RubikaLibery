<p align="center">
<a href='https://web.rubika.ir' target="_blank">
<img src='https://rubika.ir/static/images/logo.svg'></img></a></p>
<br />
<p align="center">
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/dt/rubi/lib" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/v/rubi/lib" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/l/rubi/lib" alt="License"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/packagist/stars/rubi/lib" alt="Stars"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/v/release/Amirmohammadpezeshkii/RubikaLibery" alt="Release"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/v/tag/Amirmohammadpezeshkii/RubikaLibery" alt="Tag"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/directory-file-count/amirmohammadpezeshkii/RubikaLibery/rubika" alt="Files count"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/repo-size/amirmohammadpezeshkii/RubikaLibery" alt="Files size"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/discussions/amirmohammadpezeshkii/RubikaLibery" alt="Discussions"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/sponsors/amirmohammadpezeshkii" alt="Sponsors"></a>
<a href="https://packagist.org/packages/rubi/lib"  target="_blank"><img src="https://img.shields.io/github/created-at/amirmohammadpezeshkii/RubikaLibery" alt="Created-at"></a>
</p>

# Rubika-PHP
* Rubika-PHP is a PHP Library for interaction with rubika (social network)

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
* This library can be used for easy interaction with Rubika just like official applications.
[support](https://t.me/amirMohamadPezeshki)

## Disclaimer


<b>This library is free and can not be sold.</b>


<b>The responsibility for using this library lies with the individual</b>


## License
* Rubika-PHP is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
