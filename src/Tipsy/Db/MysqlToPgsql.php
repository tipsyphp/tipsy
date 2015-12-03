<?php

namespace Tipsy\Db;

// transforms mysql queries to pgsql (kinda)
class MysqlToPgsql extends \Tipsy\Db {
	public static function convert(&$query, &$args = []) {
		// replace backticks
		$query = str_replace('`','"', $query);

		// replace add single quotes to interval statements
		$query = preg_replace('/(interval) ([0-9]+) ([a-z]+)/i','\\1 \'\\2 \\3\'', $query);

		// replace unix_timestamp
		$query = preg_replace('/unix_timestamp( )?\((.*?)\)/i','extract(epoch FROM \\2)', $query);

		// replace date_sub
		$query = preg_replace('/(date_sub\((.*?),(.*?))\)/i','\\2 - \\3', $query);

		// replace date formats
		$query = preg_replace_callback('/date_format\(( )?(.*?),( )?("(.*?)"|\'(.*?)\')( )?\)/i',function($m) {
			$find = ['/\%Y/', '/\%m/', '/\%d/', '/\%H/', '/\%i/', '/\%s/', '/\%W/'];
			$replace = ['YYYY', 'MM', 'DD', 'HH24', 'MI', 'SS', 'D'];
			$format = preg_replace($find, $replace, $m[6] ? $m[6] : $m[5]);
			return 'to_char('.$m[2].', \''.$format.'\')';
		}, $query);


		if ($args) {
			foreach ($args as $k => $v) {
				if ($v === true) {
					$args[$k] = 'true';
				} elseif ($v === false) {
					$args[$k] = 'false';
				}
			}
		}
		return [query => $query, args => $args];
	}

	public function query($query, $args = []) {
		if (!$query) {
			throw new \Tipsy\Exception('Query is emtpy');
		}
		self::convert($query, $args);
		if (!$query) {
			throw new \Tipsy\Exception('mysqlToPgsql Query is emtpy');
		}
		return parent::query($query, $args);
	}

	public function exec($query) {
		self::convert($query);
		return parent::exec($query);
	}
}
