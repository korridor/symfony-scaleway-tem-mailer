# Symfony Scaleway TEM mailer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/korridor/symfony-scaleway-tem-mailer?style=flat-square)](https://packagist.org/packages/korridor/symfony-scaleway-tem-mailer)
[![License](https://img.shields.io/packagist/l/korridor/symfony-scaleway-tem-mailer?style=flat-square)](license.md)
[![Supported PHP versions](https://img.shields.io/packagist/php-v/korridor/symfony-scaleway-tem-mailer?style=flat-square)](https://packagist.org/packages/korridor/symfony-scaleway-tem-mailer)
![GitHub Workflow Tests Status](https://img.shields.io/github/actions/workflow/status/korridor/symfony-scaleway-tem-mailer/unittests.yml?label=tests&style=flat-square)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/korridor/symfony-scaleway-tem-mailer/lint.yml?label=lint&style=flat-square)

## Installation

You can install the package via composer with following command:

```bash
composer require korridor/symfony-scaleway-tem-mailer
```

### Requirements

This package is tested for the following Laravel and PHP versions:

- 9.* (PHP 8.1)

## Usage examples

### Laravel

Add the following code to the `AppServiceProvider`:

```php
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayApiTransport;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewaySmtpTransport;

    // ..

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // ...
        Mail::extend('scaleway-api', function (array $config = []) {
            return new ScalewayApiTransport($config['token'], $config['region'], $config['project_id']);
        });
        Mail::extend('scaleway-smtp', function (array $config = []) {
            return new ScalewaySmtpTransport($config['token'], $config['region'], $config['project_id']);
        }); 
    }

```

Now add the following lines to the `config/mail.php` file in the `mailers` array:

```php
'scaleway' => [
    'transport' => 'scaleway-api',
    'region' => env('MAIL_SCALEWAY_REGION', 'fr-par'),
    'token' => env('MAIL_SCALEWAY_TOKEN'),
    'project_id' => env('MAIL_SCALEWAY_PROJECT_ID'),
],
```

If you want to use the SMTP integration instead use following lines:

```php
'scaleway' => [
    'transport' => 'scaleway-smtp',
    'region' => env('MAIL_SCALEWAY_REGION', 'fr-par'),
    'token' => env('MAIL_SCALEWAY_TOKEN'),
    'project_id' => env('MAIL_SCALEWAY_PROJECT_ID'),
],
```

### Symfony

Add the following lines to the `config/services.yaml` file:

```yaml
mailer.transport_factory.scaleway:
    class: Korridor\SymfonyScalewayTemMailer\Transport\ScalewayTransportFactory
    parent: mailer.transport_factory.abstract
    tags:
        - {name: mailer.transport_factory}
```

Then `MAILER_DSN` environment variable for example like this:

```dotenv
MAILER_DSN=scaleway+api://ACCESS_ID:SECRET@api.scaleway.com
```

## Contributing

I am open for suggestions and contributions. Just create an issue or a pull request.

### Local docker environment

The `docker` folder contains a local docker environment for development.
The docker workspace has composer and xdebug installed.

```bash
docker-compose run workspace bash
```

### Testing

The `composer test` command runs all tests with [phpunit](https://phpunit.de/).
The `composer test-coverage` command runs all tests with phpunit and creates a coverage report into the `coverage`
folder.

### Codeformatting/Linting

The `composer fix` command formats the code with [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).
The `composer lint` command checks the code with [phpcs](https://github.com/squizlabs/PHP_CodeSniffer).

## Credits

The structure of the repository is inspired by the project [symfony/postmark-mailer](https://github.com/symfony/postmark-mailer).

## License

This package is licensed under the MIT License (MIT). Please see [license file](license.md) for more information.
