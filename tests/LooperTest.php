<?php

class LoopItem {
	public function test() {
		return 'win';
	}

	public function exports() {
		return ['test' => true];
	}

	public function __toString() {
		return 'STRING';
	}
}

class LoopItemJson {
	public function json() {
		return ['test' => true, 'other' => 'asd'];
	}
}

class LoopResource extends \Tipsy\Resource {
	public function __construct($d = null) {
		$this->idVar('id')->table('test_user2');
		parent::__construct($d);
	}
}


class LooperTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
		$this->setupDb($this->tip);
	}


	public function testLoop() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->scalar;
		});
		$this->assertEquals(6, $val);
	}

	public function testBreak() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->scalar;
			return \Tipsy\Looper::DONE;
		});
		$this->assertEquals(1, $val);
	}

	public function testObjects() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 1],
			(object)['a' => 2],
			(object)['a' => 3]
		]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(6, $val);
	}

	public function testRemove() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$loop->remove(2);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->scalar;
		});
		$this->assertEquals(3, $val);
	}

	public function testSet() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 1],
			(object)['a' => 2],
			(object)['a' => 3]
		]);
		$loop->set('a', 1);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(3, $val);
	}

	public function testFilter() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4, 'b' => 1],
			(object)['a' => 5, 'b' => 1],
			(object)['a' => 6, 'b' => 3]
		]);
		$loop = $loop->filter([
			'b' => 1
		]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(9, $val);
	}

	public function testFilterNot() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4, 'b' => 1],
			(object)['a' => 5, 'b' => 1],
			(object)['a' => 6, 'b' => 3]
		]);
		$loop = $loop->not([
			'b' => 1
		]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(6, $val);
	}

	public function testFilterFunc() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4, 'b' => 1],
			(object)['a' => 5, 'b' => 1],
			(object)['a' => 6, 'b' => 3]
		]);
		$loop = $loop->filter(function($item, $key) {
			return $item->b == 3 ? false : true;
		});
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(9, $val);
	}

	public function testFilterMulti() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4, 'b' => 1, 'c' => 1, 'd' => 1],
			(object)['a' => 5, 'b' => 2, 'c' => 2, 'd' => 1],
			(object)['a' => 6, 'b' => 3, 'c' => 2, 'd' => 1]
		]);
		$loop = $loop->filter(
			['d' => 1, 'c' => 1],
			['b' => 3, 'c' => 2]
		);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(10, $val);
	}

	public function testFilterSameShorthand() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4, 'b' => 1, 'c' => 1, 'd' => 1],
			(object)['a' => 5, 'b' => 2, 'c' => 2, 'd' => 1],
			(object)['a' => 6, 'b' => 3, 'c' => 2, 'd' => 1]
		]);
		$loop = $loop->filter(['d' => 1]);
		$val = 0;
		$loop->e(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(15, $val);
	}

	public function testEq() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$val = $loop->eq(-1);
		$this->assertEquals(3, $val);
	}

	public function testEqGet() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$val = $loop->eq(0);
		$this->assertEquals(1, $val);
	}

	public function testMultiArray() {
		$loop = new \Tipsy\Looper([1,2,3], [4,5,6]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->scalar;
		});
		$this->assertEquals(21, $val);
	}

	public function testMultiComplex() {
		$loop = (new \Tipsy\Looper([
			(object)['a' => 1, 'b' => 1, 'c' => 1],
			(object)['a' => 2, 'b' => 1, 'c' => 1],
			(object)['a' => 3, 'b' => 3, 'c' => 1]
		], [
			(object)['a' => 4, 'b' => 1, 'c' => 1],
			(object)['a' => 5, 'b' => 1, 'c' => 1],
			(object)['a' => 7, 'b' => 3, 'c' => 1]
		]))->filter(['b' => 3])
			->set('c', 2)
			->parent();
		$val = 0;

		$loop->each(function() use (&$val) {
			$val += $this->c;
		});
		$this->assertEquals(8, $val);
	}

	public function testCall() {
		$loop = new \Tipsy\Looper([new LoopItem]);
		$this->assertEquals($loop->test(), 'win');
	}

	public function testToString() {
		$loop = new \Tipsy\Looper([1,2,3], [4,5,6]);
		$this->assertEquals('123456', "".$loop);
	}

	public function testToStringObject() {
		$loop = new \Tipsy\Looper(new LoopItem, [4,5,6]);
		$this->assertEquals('STRING456', "".$loop);
	}

	public function testToJson() {
		$loop = new \Tipsy\Looper([1,"2"], new LoopItem);
		$this->assertEquals('[1,"2",{"test":true}]', $loop->json());
	}

	public function testLoopInLoop() {
		$loop = new \Tipsy\Looper([1,2], new \Tipsy\Looper([3,4], [5,6]));
		$this->assertEquals('123456', "".$loop);
	}

	public function testGet() {
		$loop = new \Tipsy\Looper([1,2]);
		$this->assertEquals(1, $loop->get(0));
	}

	public function testSlice() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4],
			(object)['a' => 5],
			(object)['a' => 6],
			(object)['a' => 7],
			(object)['a' => 8]
		]);
		$slice = $loop->slice(2, 2);
		$val = 0;
		$slice->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(13, $val);
	}

	public function testForEach() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4],
			(object)['a' => 5],
			(object)['a' => 6],
			(object)['a' => 7],
			(object)['a' => 8]
		]);
		foreach ($loop as $item) {
			$val += $item->a;
		}
		$this->assertEquals(30, $val);
	}

	public function testWhile() {
		$loop = new \Tipsy\Looper([
			(object)['a' => 4],
			(object)['a' => 5],
			(object)['a' => 6],
			(object)['a' => 7],
			(object)['a' => 8]
		]);
		$loop->rewind();

		while ($loop->valid()){
			$val += $loop->current()->a;
			$loop->next();
		}
		$this->assertEquals(30, $val);
	}

	public function testJsonFunction() {
		$loop = new \Tipsy\Looper(new LoopItemJson);
		$this->assertEquals('[{"test":true,"other":"asd"}]', $loop->json());
	}

	public function testCount() {
		$loop = new \Tipsy\Looper([4,5,6]);
		$this->assertEquals(3, $loop->count());
	}

	public function testWhileKey() {
		$loop = new \Tipsy\Looper([4,5,6]);
		$loop->rewind();

		while ($loop->valid()){
			$val = $loop->key();
			break;
		}
		$this->assertEquals(0, $val);
	}

	public function testResource() {
		$obj = new LoopResource(['test' => 3]);
		$loop = new \Tipsy\Looper($obj, $obj);

		$val = 0;
		foreach ($loop as $item) {
			$val += $item->test;
		}
		$this->assertEquals(6, $val);
	}
}
