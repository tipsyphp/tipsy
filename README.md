<img align="right" height="300px" src="http://tipsy.la/images/cocktail.png">
<img height="100px" src="http://tipsy.la/images/logo.png">

Tipsy is an MVW (Model, View, Whatever) PHP micro framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use interface for websites, rest apis, and dependency injection.


[![Latest Stable Version](https://poser.pugx.org/arzynik/tipsy/v/stable)](https://packagist.org/packages/arzynik/tipsy)
[![Build Status](https://travis-ci.org/arzynik/tipsy.svg?branch=master)](https://travis-ci.org/arzynik/tipsy)
[![Coverage Status](https://coveralls.io/repos/arzynik/tipsy/badge.svg?branch=master&service=github)](https://coveralls.io/github/arzynik/tipsy?branch=master)
[![Slack Status](https://tipsy-slack.herokuapp.com/badge.svg)](https://tipsy-slack.herokuapp.com/)

---


### Example Usage

See [Examples](https://github.com/arzynik/tipsy/wiki/Examples) for more detailed examples. See [Documentation](https://github.com/arzynik/tipsy/wiki) for more information.

#### View Template Example

###### index.php
```php
$tipsy->router()
    ->home(function($Scope, $View) {
        $Scope->user = 'Mai Tai';
        $View->display('hello');
    });
```

###### hello.phtml
```phtml
<h1>Hello <?=$user?>!</h1>
```

#### API Example

###### index.php

```php
$tipsy->router()
    ->delete('api/maitai/:id', function($Params) {
        echo json_encode([message => $Params->id]);
    });
```

###### DELETE /api/maitai/1
```
{"message": 1}
```

---


### Installation
To install using composer use the command below. For additional installation information see [Installation](https://github.com/arzynik/tipsy/wiki/Installation).

```sh
composer require arzynik/tipsy
```
