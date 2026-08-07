<?php

declare(strict_types=1);

namespace Tipsy\View\Filter;

class StripWhite extends \Tipsy\View\Filter {
	public static function filter(string $content, array $arguments = []): string {
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
		return preg_replace($find, $replace, $content) ?? $content;
	}
}
