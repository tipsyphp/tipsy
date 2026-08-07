<img align="right" height="300px" src="https://tipsyphp.github.io/tipsy/images/cocktail.png">
<img height="100px" src="https://tipsyphp.github.io/tipsy/images/logo.png">

Tipsy is an MVW (Model, View, Whatever) PHP micro framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use interface for websites, REST APIs, and dependency injection.

**Tipsy 2.0** targets **PHP 8.3+** with a typed core, **zero runtime Composer dependencies**, and a **PostgreSQL-first** database layer (MySQL and SQLite still supported). See [UPGRADING.md](UPGRADING.md) for breaking changes from 0.11.x.

[![Latest Stable Version](https://poser.pugx.org/tipsyphp/tipsy/v/stable)](https://packagist.org/packages/tipsyphp/tipsy)
[![CI](https://github.com/tipsyphp/tipsy/actions/workflows/ci.yml/badge.svg)](https://github.com/tipsyphp/tipsy/actions/workflows/ci.yml)

---


### Example Usage

See [examples/](examples/) in this repo for runnable apps. See [Documentation](https://github.com/tipsyphp/tipsy/wiki) for more information.

###### index.php
```php
$app->home(function($View) {
    $View->display('index', ['user' => 'crystal']);
});
```

###### index.phtml
```phtml
<h1>Hello <?=$user?></h1>
```

---


### Installation
To install using composer use the command below. For additional installation information see [Installation](https://github.com/tipsyphp/tipsy/wiki/Installation).

```sh
composer require tipsyphp/tipsy:^2.0
```

### Development

```sh
composer install
composer test
```

CI runs PHP 8.3/8.4 × MySQL/PostgreSQL, then on `master`/`main` can open a GitHub prerelease (promote it to publish to Packagist). See [docs/RELEASE.md](docs/RELEASE.md). Performance notes live in [benchmarks/](benchmarks/).
