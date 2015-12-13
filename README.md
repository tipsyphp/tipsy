<img align="right" height="300px" src="http://tipsy.la/images/cocktail.png">
<img height="100px" src="http://tipsy.la/images/logo.png">

Tipsy is an MVW (Model, View, Whatever) PHP micro framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use interface for websites, rest apis, and dependency injection.


[![Latest Stable Version](https://poser.pugx.org/tipsyphp/tipsy/v/stable)](https://packagist.org/packages/tipsyphp/tipsy)
[![Build Status](https://travis-ci.org/tipsyphp/tipsy.svg?branch=master)](https://travis-ci.org/tipsyphp/tipsy)
[![Coverage Status](https://coveralls.io/repos/tipsyphp/tipsy/badge.svg?branch=master&service=github)](https://coveralls.io/github/tipsyphp/tipsy?branch=master)
[![Slack Status](https://tipsy-slack.herokuapp.com/badge.svg)](https://tipsy-slack.herokuapp.com/)

---


### Example Usage

See [Examples](https://github.com/tipsyphp/tipsy/wiki/Examples) for more detailed examples. See [Documentation](https://github.com/tipsyphp/tipsy/wiki) for more information.

###### index.php
```php
$app->home(function($View) {
    $View->display('index', [user => 'crystal']);
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
composer require tipsyphp/tipsy
```
