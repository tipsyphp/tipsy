Tipsy is an MVW (Model, View, Whatever) PHP framework inspired by [AngularJS](https://angularjs.org/). It provides a very lightweight, easy to use framework, capable of handling most tasks.



**This is a dev version**. I am currently pulling in features from [Cana](http://cana.la/) and building unit tests as I go.


[![Build Status](https://travis-ci.org/arzynik/Tipsy.svg?branch=master)](https://travis-ci.org/arzynik/Tipsy)




### Example Usage

This is an example of a personal website and blog. See [Documentation](https://github.com/arzynik/Tipsy/wiki) for more information.

###### hello.php

```php
require_once 'Tipsy.php';
use Tipsy\Tipsy;

$tipsy = new Tipsy;

$tipsy->model('Tipsy\DBO/Blog', [
	permalink => function($permalink) {
		return $this->q('select * from blog where permalink=?',$permalink);
	},
	posts => function() {
		return $this->q('select * from blog limit 10');
	},
	id => 'id',
	table => 'blog'
]);

$tipsy->router()
	->home(function($View) {
		$View->display('home');
	});
	->when('about', function($View) {
		$View->display('about');
	})
	->when('blog', function($Blog, $Scope, $View) {
		$Scope->posts = $Blog->posts();
		$View->display('blog');
	})
	->when('blog/:id', function($Params, $Blog, $Scope, $View) {
		$Scope->post = $Blog->permalink($Params->id);
		$View->display('post');
	})
	->otherwise(function() {
		echo '404';
	});

$tipsy->start();
```

###### layout.phtml

```phtml
<title>This is my awesome website!</title>
<body>
	<div class="content">
		<?=$this->content?>
	</div>
</body>
```

###### home.phtml

```phtml
<h1>Welcome to my awesome website!</h1>

```

###### about.phtml

```phtml
<p>I am an awesome guy!</p>
```


###### blog.phtml

```phtml
<div class="posts">
	<? foreach ($posts as $post) : ?>
		<a href="/blog/<?=$post->permalink?>"><?=$post->title?></a>
	<? endforeach ; ?>
</div>
```

###### post.phtml

```phtml
<div class="post">
	<h1><?=$post->title?></h1>
	<p><?=$post->content?></p>
</div>
```


### Installation

See [Installation](https://github.com/arzynik/Tipsy/wiki/Installation) for instructions on how to install.
