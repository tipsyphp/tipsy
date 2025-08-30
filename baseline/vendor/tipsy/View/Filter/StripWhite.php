<?php

namespace Tipsy\View\Filter;

class StripWhite extends \Tipsy\View\Filter {
	public static function filter($content, $arguments = [] ) {
		$find = [
			'/^(\s?)(.*?)(\s?)$/',
			'/\t|\n|\r/',
			'/(\<\!\-\-)(.*?)\-\-\>/'
		];
		$replace = [
			'\\2',
			'',
			''
		];
		return preg_replace($find, $replace, $content);
	}
}
