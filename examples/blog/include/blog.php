<?

require_once __DIR__ . '/../../../vendor/autoload.php';


$tipsy = new Tipsy\Tipsy;

echo $_REQUEST['__url'];

$tipsy->config('../config/*.ini');

$tipsy->service('Tipsy\Resource/Blog', [
	permalink => function($permalink) {
		return $this->q('select * from blog where permalink=?',$permalink);
	},
	posts => function() {
		return $this->q('select * from blog limit 10');
	},
	_id => 'id',
	_table => 'blog'
]);

//$tipsy->model()

$tipsy->router()
	->home(function($View) {
		$View->display('home');
	})
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