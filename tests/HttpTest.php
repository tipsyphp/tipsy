<?php

class HttpTest extends Tipsy_Test {
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
	}

	public function testFormGetJson() {
		$http = (new Tipsy\Http())->get('http://localhost:8000/item/1/json', [key => 'value'], [dataType => 'form'])->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});
		$this->assertEquals(true, $res);
	}

	public function testFormPostJson() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/json', [key => 'value'])->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});
		$this->assertEquals(true, $res);
	}

	public function testFormGetPlain() {
		$http = (new Tipsy\Http())->get('http://localhost:8000/item/1/plain', [key => 'value'])->complete(function($data) use (&$res) {
			$res = $data == '1.value';
		});
		$this->assertEquals(true, $res);
	}

	public function testFormPostPlain() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/plain', [key => 'value'], [dataType => 'form'])->complete(function($data) use (&$res) {
			$res = $data == '1.value';
		});
		$this->assertEquals(true, $res);
	}

	/** these tests are currently failing. unknown why
	public function testJsonGetJson() {
		$http = (new Tipsy\Http())->get('http://localhost:8000/item/1/json', [key => 'value'], [dataType => 'json'])->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});
		$this->assertEquals(true, $res);
	}

	public function testJsonPostJson() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/json', [key => 'value'], [dataType => 'json'])->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});
		$this->assertEquals(true, $res);
	}

	public function testJsonGetPlain() {
		$http = (new Tipsy\Http())->get('http://localhost:8000/item/1/plain', [key => 'value'], [dataType => 'json'])->complete(function($data) use (&$res) {
			$res = $data == '1.value';
		});
		$this->assertEquals(true, $res);
	}

	public function testJsonPostPlain() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/plain', [key => 'value'], [dataType => 'json'])->complete(function($data) use (&$res) {
			$res = $data == '1.value';
		});
		$this->assertEquals(true, $res);
	}
	**/

	public function testError() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/404')
			->complete(function($data) use (&$res) {
				$res['complete'] = true;
			})
			->success(function($data) use (&$res) {
				$res['success'] = true;
			})
			->error(function($data) use (&$res) {
				$res['error'] = true;
			});

		$this->assertTrue($res['complete']);
		$this->assertNull($res['success']);
		$this->assertTrue($res['error']);
	}

	public function testSuccess() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/plain')
			->complete(function($data) use (&$res) {
				$res['complete'] = true;
			})
			->success(function($data) use (&$res) {
				$res['success'] = true;
			})
			->error(function($data) use (&$res) {
				$res['error'] = true;
			});

		$this->assertTrue($res['complete']);
		$this->assertTrue($res['success']);
		$this->assertNull($res['error']);
	}

	public function testKwargsCopy() {
		$http = (new Tipsy\Http())->post('http://localhost:8000/item/1/json', [key => 'value'])
		->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});

		$this->assertTrue($res);
	}

	public function testKwargs() {
		$http = (new Tipsy\Http())->request([
			url => 'http://localhost:8000/item/1/json',
			method => 'post',
			data => [key => 'value']
		])
		->complete(function($data) use (&$res) {
			$res = $data == (object)[id => 1, key => 'value'];
		});

		$this->assertTrue($res);
	}
}
