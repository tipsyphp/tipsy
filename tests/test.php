<?
/*
class TestScope {
	private $_properties;
	
	public function __construct() {
		$this->_properties = [];
	}

	public function &__get($name) {
		return $this->_properties[$name];
	}

	public function __set($name, $value) {
		return $this->_properties[$name] = $value;
	}
	
	public function &properties() {
		return $this->_properties;
	}
}

$test = [
	'bacon' => 'yes'
];
$test2 = new TestScope;
$test2->bacon = 'YES';


class Tester {
	function test1(&$test) {
		extract($test, EXTR_REFS);
		print_r($test);
		$bacon = 'no';
		print_r($test);
	}
	function test2(&$test) {
		
	}
}

//$t = new Tester();
//$t->test1($test1);
//print_r($test);

$t = new Tester();
$t->test1($test2->properties());
print_r($test2->properties());
exit;

*/


//require_once('../Tipsy/Tipsy.php');
require_once '../src/Tipsy/Tipsy.php';

$tipsy = new Tipsy\Tipsy;

$tipsy->config('config.ini');


$tipsy->controller('ViewController', function($View, $Scope) {
	$Scope->test = 'ONE';

	$View->display('ScopeTest');
	echo $Scope->test;
});

$tipsy->router()
	->when('/', [
		'controller' => 'ViewController',
		'view' => 'test'
	]);
$tipsy->start();

