Tipsy is an MVW (Model, View, Whatever) PHP framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use framework, capable of handling most tasks.



**This is a dev version**. I am currently pulling in features from [Cana](http://cana.la/) and building unit tests as I go.


[![Build Status](https://travis-ci.org/arzynik/Tipsy.svg?branch=master)](https://travis-ci.org/arzynik/Tipsy)




### Example Usage

See [Documentation](https://github.com/arzynik/Tipsy/wiki) for more information.

###### hello.php

```php
require_once 'Tipsy.php';
use Tipsy\Tipsy;

$tipsy = new Tipsy;

$tipsy->router()
	->home(function($View) {
		$View->display('home');
	});
	->when('user', function($Scope, $View) {
		$Scope->user = 'Devin';
		$View->display('user');
	})
	->otherwise(function() {
		echo '404';
	});

$tipsy->start();
```

###### user.phtml

```phtml
<div class="content">
	<h1>Welcome <?=$user?>!</h1>
</div>
```


### Installation

See [Installation](https://github.com/arzynik/Tipsy/wiki/Installation) for instructions on how to install.
