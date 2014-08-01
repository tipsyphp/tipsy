Tipsy is an MVW (Model, View, Whatever) PHP framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use framework, capable of handling most tasks.



**This is a dev version**. I am currently pulling in features from [Cana](http://cana.la/) and building unit tests as I go.


[![Build Status](https://travis-ci.org/arzynik/Tipsy.svg?branch=master)](https://travis-ci.org/arzynik/Tipsy)




### Example Usage

See [Examples](https://github.com/arzynik/Tipsy/wiki/Examples) for more detailed examples. See [Documentation](https://github.com/arzynik/Tipsy/wiki) for more information.

###### index.php

```php
require_once 'Tipsy.php';
use Tipsy\Tipsy;

$tipsy = new Tipsy;

$tipsy->router()
	->when('hello', function($Scope, $View) {
		$Scope->user = 'Devin';
		$View->display('hello');
	})
	->otherwise(function() {
		echo '404';
	});

$tipsy->start();
```

###### hello.phtml

```phtml
<h1>Welcome, <?=$user?>!</h1>
```


### Installation

See [Installation](https://github.com/arzynik/Tipsy/wiki/Installation) for instructions on how to install.
