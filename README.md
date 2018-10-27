# MultiAuthCommand-BackPack

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Scrutinizer Code Quality][ico-code-quality]][link-code-quality]
[![Build Status](https://scrutinizer-ci.com/g/iMokhles/MultiAuthCommand-BackPack/badges/build.png?b=master)](https://scrutinizer-ci.com/g/iMokhles/MultiAuthCommand-BackPack/build-status/master)
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

create multi authentication guards with backpack panel

## Before Install

1. install Backpack CRUD/Base by following this Doc [Backpack-Installation](https://backpackforlaravel.com/docs/3.4/installation)
2. go to `config/backpack/base.php` and apply the following changes

* `route_prefix => '' ( emove it's value should be empty )`
* `setup_auth_routes => true ( make it false)`
* `setup_dashboard_routes => true ( make it false)`
* `setup_my_account_routes => true ( make it false )`
* `user_model_fqn => '' ( remove it's value should be empty )`
* `middleware_class => '' ( remove it's value should be empty )`

## Install

1. In your terminal via composer:

``` bash
composer require imokhles/multi-backpack
```

2. Add this provider to your config/app.php ( no need for Laravel 5.5 and above ) :
```
iMokhles\MultiAuthBackPack\MultiAuthBackPackServiceProvider::class
```

## Usage

Example usage: 


``` bash
php artisan make:multi-backpack Admin --admin_theme="adminox" or --admin_theme="adminlte"
```

## Security

If you discover any security related issues, please email imokhles@imokhles.com instead of using the issue tracker.

## Credits

- [iMokhles](http://github.com/imokhles)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/imokhles/multi-backpack.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/imokhles/multi-backpack.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/iMokhles/MultiAuthCommand-BackPack.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/imokhles/multi-backpack
[link-downloads]: https://packagist.org/packages/imokhles/multi-backpack
[link-author]: https://github.com/imokhles
[link-code-quality]: https://scrutinizer-ci.com/g/iMokhles/MultiAuthCommand-BackPack

[![Beerpay](https://beerpay.io/iMokhles/MultiAuthCommand/badge.svg?style=beer-square)](https://beerpay.io/iMokhles/MultiAuthCommand)  [![Beerpay](https://beerpay.io/iMokhles/MultiAuthCommand/make-wish.svg?style=flat-square)](https://beerpay.io/iMokhles/MultiAuthCommand?focus=wish)
