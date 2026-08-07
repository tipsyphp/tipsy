<?php

declare(strict_types=1);

namespace Tipsy\Db;

/**
 * Optional compatibility layer: rewrite MySQL-flavored SQL for PostgreSQL.
 *
 * Tipsy 2.0 defaults to Postgres and Resource emits dialect-aware SQL, so this
 * is only needed if your app still writes raw MySQL SQL (backticks, unix_timestamp, etc.)
 * against a Postgres connection:
 *
 *   $app->service('Db', Tipsy\Db\MysqlToPgsql::class);
 */
class MysqlToPgsql extends \Tipsy\Db {
	public static function convert(string &$query, array &$args = []): array {
		// replace backticks
		$query = str_replace('`', '"', $query);

		// replace add single quotes to interval statements
		$query = preg_replace('/(interval) ([0-9]+) ([a-z]+)/i', '\\1 \'\\2 \\3\'', $query) ?? $query;

		// replace unix_timestamp
		$query = preg_replace('/unix_timestamp( )?\((.*?)\)/i', 'extract(epoch FROM \\2)', $query) ?? $query;

		// replace date_sub
		$query = preg_replace('/(date_sub\((.*?),(.*?))\)/i', '\\2 - \\3', $query) ?? $query;

		// replace date formats
		$query = preg_replace_callback('/date_format\(( )?(.*?),( )?("(.*?)"|\\\'(.*?)\\\')( )?\)/i', function(array $m): string {
			$find = ['/\%Y/', '/\%m/', '/\%d/', '/\%H/', '/\%i/', '/\%s/', '/\%W/'];
			$replace = ['YYYY', 'MM', 'DD', 'HH24', 'MI', 'SS', 'D'];
			$format = preg_replace($find, $replace, $m[6] ?: $m[5]);
			return 'to_char(' . $m[2] . ', \'' . $format . '\')';
		}, $query) ?? $query;


		foreach ($args as $k => $v) {
			if ($v === true) {
				$args[$k] = 'true';
			} elseif ($v === false) {
				$args[$k] = 'false';
			}
		}

		return ['query' => $query, 'args' => $args];
	}

	public function query(string $query, ?array $args = null): \PDOStatement {
		if (!$query) {
			throw new \Tipsy\Exception('Query is empty');
		}
		$argsRef = $args ?? [];
		self::convert($query, $argsRef);
		if (!$query) {
			throw new \Tipsy\Exception('mysqlToPgsql Query is empty');
		}
		return parent::query($query, $argsRef);
	}

	public function exec(string $query): int|false {
		$args = [];
		self::convert($query, $args);
		return parent::exec($query);
	}
}
