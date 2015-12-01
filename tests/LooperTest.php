<?php

class LoopItem {
	public function test() {
		return 'win';
	}

	public function exports() {
		return ['test' => true];
	}
}


class LooperTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
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
			(object)['a' => 1, 'b' => 1],
			(object)['a' => 2, 'b' => 1],
			(object)['a' => 3, 'b' => 3]
		]);
		$loop = $loop->filter([
			'b' => 1
		]);
		$val = 0;
		$loop->each(function() use (&$val) {
			$val += $this->a;
		});
		$this->assertEquals(3, $val);
	}

	public function testEq() {
		$loop = new \Tipsy\Looper([1,2,3]);
		$val = $loop->eq(-1);
		$this->assertEquals(3, $val);
	}

	public function testGet() {
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

	public function testToJson() {
		$loop = new \Tipsy\Looper([1,"2"], new LoopItem);
		$this->assertEquals('[1,"2",{"test":true}]', $loop->json());
	}
}
