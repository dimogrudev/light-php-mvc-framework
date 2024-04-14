<?php

namespace Core\Modules;

class Query
{
	private static string $sql;
	private static array $params;

	private static array $methods;

	public function __call($name, $arguments)
	{
		if (
			self::$methods
			&& isset(self::$methods[$name])
		) {
			if (self::$methods[$name]['called']) {
				throw new \Exception('Method Core\Modules\Query::' . $name . ' was already called');
			}

			foreach (self::$methods as $methodName => $methodParams) {
				if ($methodName == $name) {
					break;
				}

				if ($methodParams['required'] && !$methodParams['called']) {
					throw new \Exception('Method Core\Modules\Query::' . $methodName . ' is missed '
						. 'before calling Core\Modules\Query::' . $name . '');
				}
			}

			self::$methods[$name]['called'] = true;
			return call_user_func_array('self::' . $name, $arguments);
		}

		throw new \Exception('Trying to call nonexistent method');
	}

	public function __toString()
	{
		foreach (self::$methods as $methodName => $methodParams) {
			if ($methodParams['required'] && !$methodParams['called']) {
				throw new \Exception('Method Core\Modules\Query::' . $methodName . ' is missed '
					. 'before getting SQL-statement');
			}
		}

		return self::$sql . ';';
	}

	private static function setAllowedMethods(array $methods)
	{
		self::$methods = [];

		foreach ($methods as $methodName => $isMethodRequired) {
			self::$methods[$methodName] = [
				'required' => $isMethodRequired,
				'called' => false
			];
		}
	}

	public static function insert(bool $ignore = false): self
	{
		self::$sql = 'INSERT' . (($ignore) ? ' IGNORE' : '');
		self::$params = [];

		self::setAllowedMethods([
			'into' => true,
			'values' => true
		]);

		return new self();
	}

	private static function into(string $tableName): self
	{
		self::$sql .= ' INTO ' . $tableName;
		return new self();
	}

	private static function values(array $values): self
	{
		if ($values) {
			$columns = array_keys($values);

			self::$sql .= ' (' . implode(', ', $columns) . ')';
			self::$sql .= ' VALUES (' . implode(', ', array_fill(0, count($values), '?')) . ')';

			self::$params = array_merge(self::$params, array_values($values));
		}

		return new self();
	}

	public static function select($expression): self
	{
		if ($expression) {
			self::$sql = 'SELECT ' . ((is_array($expression)) ? implode(', ', $expression) : $expression);
			self::$params = [];

			self::setAllowedMethods([
				'from' => true,
				'where' => false,
				'orderBy' => false,
				'limit' => false
			]);
		}

		return new self();
	}

	private static function from(string $tableName): self
	{
		self::$sql .= ' FROM ' . $tableName;
		return new self();
	}

	private static function where($condition, $params = null): self
	{
		if ($condition) {
			self::$sql .= ' WHERE ';

			if (is_array($condition)) {
				$sqlConds = [];

				foreach ($condition as $column => $value) {
					$sqlCond = '';

					if (is_int($column) && is_array($value) && count($value) == 3) {
						$sqlCond = $value[1] . ' ' . $value[0] . ' ?';
						self::$params[] = $value[2];
					} else {
						if (is_array($value)) {
							$sqlCond = $column . ' IN (' . implode(', ', array_fill(0, count($value), '?')) . ')';
							self::$params = array_merge(self::$params, $value);
						} else if (is_null($value)) {
							$sqlCond = $column . ' IS NULL';
						} else {
							$sqlCond = $column . ' = ?';
							self::$params[] = $value;
						}
					}

					$sqlConds[] = '(' . $sqlCond . ')';
				}

				self::$sql .= implode(' AND ', $sqlConds);
			} else {
				self::$sql .= $condition;
				if ($params) {
					if (is_array($params)) {
						self::$params = array_merge(self::$params, $params);
					} else {
						self::$params[] = $params;
					}
				}
			}
		}

		return new self();
	}

	private static function orderBy(string $expression, string $sort = 'ASC'): self
	{
		self::$sql .= ' ORDER BY ' . $expression . ' ' . $sort;
		return new self();
	}

	private static function limit(int $limit, ?int $offset = null): self
	{
		self::$sql .= ' LIMIT ' . $limit;
		if ($offset) {
			self::$sql .= ' OFFSET ' . $offset;
		}

		return new self();
	}

	public static function delete(): self
	{
		self::$sql = 'DELETE';
		self::$params = [];

		self::setAllowedMethods([
			'from' => true,
			'where' => false,
			'orderBy' => false,
			'limit' => false
		]);

		return new self();
	}

	public static function update(string $tableName): self
	{
		self::$sql = 'UPDATE ' . $tableName;
		self::$params = [];

		self::setAllowedMethods([
			'set' => true,
			'where' => false,
			'orderBy' => false,
			'limit' => false
		]);

		return new self();
	}

	private static function set($expression, $params = null): self
	{
		if ($expression) {
			self::$sql .= ' SET ';

			if (is_array($expression)) {
				$sqlConds = [];

				foreach ($expression as $column => $value) {
					$sqlConds[] = $column . ' = ?';
					self::$params[] = $value;
				}

				self::$sql .= implode(', ', $sqlConds);
			} else {
				self::$sql .= $expression;
				if ($params) {
					if (is_array($params)) {
						self::$params = array_merge(self::$params, $params);
					} else {
						self::$params[] = $params;
					}
				}
			}
		}

		return new self();
	}

	public static function run(Database $database, string $className = 'stdClass'): array
	{
		return $database->query((string)new self(), self::$params, $className);
	}
}
