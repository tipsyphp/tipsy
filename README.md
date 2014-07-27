Tipsy
-----

MVW (Model, View, Whatever) PHP framework inspired by [AngularJS](https://angularjs.org/).


**This is a dev version**. I am currently pulling in features from [Cana](http://cana.la/) and building unit tests as I go.


[![Build Status](https://travis-ci.org/arzynik/Tipsy.svg?branch=master)](https://travis-ci.org/arzynik/Tipsy)


---

### Usage

```php
$t = new Tipsy;
$t->router()
  ->when('/', function($Scope, $View) {
    $Scope->kitteh = 'meow';
    $View->display('home');
  });
```

### Instalation

#### Using Composer
Download [Composer](https://getcomposer.org/) if you havent already.



1. Add tipsy to your composer.son
```javascript
"require": {
  "arzynik/tipsy": "dev-master"
}
```

2. run the composer install command
```sh
composer install
```

#### Manual

1. Copy **Tipsy.php** to your library path.

2. Include the Tipsy library:

```php
require_once 'Tipsy.php';
```

3. Do shit.
