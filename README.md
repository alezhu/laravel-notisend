# laravel-notisend

[Notisend](https://notisend.ru) Laravel driver

[![License](https://poser.pugx.org/alezhu/laravel-notisend/license)](https://packagist.org/packages/alezhu/laravel-notisend) 
[![Code coverage](../code_coverage_bages/coverage/coverage.main.svg)](https://packagist.org/packages/alezhu/laravel-notisend)
[![GitHub Tag](https://img.shields.io/github/v/tag/alezhu/laravel-notisend?filter=v12*&label=version)](https://packagist.org/packages/alezhu/laravel-notisend) 
[![Total Downloads](https://poser.pugx.org/alezhu/laravel-notisend/downloads)](https://packagist.org/packages/alezhu/laravel-notisend) 
[![PHP Version Require](https://poser.pugx.org/alezhu/laravel-notisend/require/php)](https://packagist.org/packages/alezhu/laravel-notisend)
[![GitHub branch status](https://img.shields.io/github/checks-status/alezhu/laravel-notisend/main)](https://packagist.org/packages/alezhu/laravel-notisend)
[![Packagist Stars](https://img.shields.io/packagist/stars/alezhu/laravel-notisend)](https://packagist.org/packages/alezhu/laravel-notisend)
[![CI](https://github.com/alezhu/laravel-notisend/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/alezhu/laravel-notisend/actions/workflows/php.yml)

# Table of Contents

* [Installation](#installation)
* [Usage](#usage)
* [Support and Feedback](#support-and-feedback)
* [License](#license)

<a name="installation"></a>

# Installation

## Requirements

- Laravel 12.0+
- PHP 8.2+
- An API Key from [Notisend](https://app.notisend.ru/mailer/automation/api/messages)

**For Laravel version below 12 see corresponding branches**

## Setup

You can install the package via composer:

```bash
composer require alezhu/laravel-notisend
```

After that, you need to set `NOTISEND_API_TOKEN` in your `.env` file:

```dotenv
NOTISEND_API_TOKEN=
```

And set environment variable `MAIL_MAILER` in your `.env` file

```dotenv
MAIL_MAILER=mailersend
```

Or you can leave the default driver value and use 'notisend' via:

```php
$mailer = Mail::mailer('notisend');
```

Also, double check that your `FROM` data is filled in `.env`:

```dotenv
MAIL_FROM_ADDRESS=app@yourdomain.com
MAIL_FROM_NAME="App Name"
```

### Not necessary

Add Notisend as a Laravel Mailer in `config/mail.php` in `mailers` array:

```php
'notisend' => [
    'transport' => 'notisend',
],
```

Also, you can config driver via `mailers.notisend` in `config/mail.php`:

```php
 'mailers' => [
    ...
    'notisend' => [
        'transport' => 'notisend',
        'api_token' => env('NOTISEND_API_TOKEN'),
        'host' => env('NOTISEND_API_HOST', 'https://api.notisend.ru/v1'),
        'payment' => env('NOTISEND_PAYMENT', 'credit_priority'),    
    ],
    ...
  ]
```

Or you can publish configuration file `notisend.php`  via Artisan:

```bash
php artisan vendor:publish --tag=notisend-config
```

and then change parameters in `config/notisend.php` file

Parameters in `config/mail.php` has priority before `config/notisend.php`

<a name="usage"></a>

# Usage

```php
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExampleTestManual extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_notisend(): void
    {
        Mail::mailer('notisend')
            ->raw('Test mail', function (Message $message) {
            $message->to('info@example.com');
            $message->subject('Test mail');
        });
    }
}
```

Please refer to [Laravel Mail documenation](https://laravel.com/docs/12.x/mail)
and [NotiSend API documentation](https://notisend.ru/dev/email/api/) for more information.

<a name="support-and-feedback"></a>

# Support and Feedback

In case you find any bugs, submit an issue directly here in GitHub.

***The author of this repository is in no way affiliated with Notisend.***

<a name="license"></a>

# License

[The MIT License (MIT)](LICENSE.md)