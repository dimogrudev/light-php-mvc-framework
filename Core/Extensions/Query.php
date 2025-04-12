<?php

namespace Core\Extensions;

use Core\Modules\Database;

final class Query
{
	/** @var string $sql */
	private string $sql;
	/** @var (int|string)[] $params */
	private array $params;

	/** @var array<string, array{required: bool, called: bool}> $methods */
	private array $methods	= [];

	/**
	 * @param string $sql
	 * @param array<string, bool> $allowedMethods
	 * @return void
	 */
	private function __construct(string $sql, array $allowedMethods)
	{
		$this->sql = $sql;
		$this->params = [];
		
		$this->setAllowedMethods($allowedMethods);
	}

	/**
	 * @param array<string, bool> $methods
	 * @return void
	 */
	private function setAllowedMethods(array $methods): void
	{
		$this->methods = [];

		foreach ($methods as $methodName => $isMethodRequired) {
			$this->methods[$methodName] = [
				'required'	=> $isMethodRequired,
				'called'	=> false
			];
		}
	}

	private function checkMethod(string $callingMethodName): void
	{
		if ($this->methods && isset($this->methods[$callingMethodName])) {
			if ($this->methods[$callingMethodName]['called']) {
				throw new \Exception('Method Core\Modules\Query::' . $callingMethodName . ' was already called');
			}

			foreach ($this->methods as $methodName => $methodParams) {
				if ($methodName == $callingMethodName) {
					break;
				}

				if ($methodParams['required'] && !$methodParams['called']) {
					throw new \Exception('Method Core\Modules\Query::' . $methodName . ' is missed '
						. 'before calling Core\Modules\Query::' . $callingMethodName . '');
				}
			}

			$this->methods[$callingMethodName]['called'] = true;
			return;
		}

		throw new \Exception('Trying to call nonexistent method');
	}

	public static function insert(bool $ignore = false): self
	{
		return new self(
			'INSERT' . ($ignore ? ' IGNORE' : ''),
			[
				'into'		=> true,
				'values'	=> true
			]
		);
	}

	public function into(string $tableName): self
	{
		$this->checkMethod('into');

		$this->sql .= ' INTO ' . $tableName;
		return $this;
	}

	/**
	 * @param array<string, int|string> $values
	 * @return self
	 */
	public function values(array $values): self
	{
		if ($values) {
			$this->checkMethod('values');

			/** @var string[] $columns */
			$columns = array_keys($values);

			$this->sql .= ' (' . implode(', ', $columns) . ')';
			$this->sql .= ' VALUES (' . implode(', ', array_fill(0, count($values), '?')) . ')';

			$this->params = array_merge($this->params, array_values($values));
		}

		return $this;
	}

	/**
	 * @param string|array $expression
	 * @return self
	 */
	public static function select($expression): self
	{
		return new self(
			'SELECT ' . (is_array($expression) ? implode(', ', $expression) : $expression),
			[
				'from'		=> true,
				'where'		=> false,
				'orderBy'	=> false,
				'limit'		=> false
			]
		);
	}

	public function from(string $tableName): self
	{
		$this->checkMethod('from');

		$this->sql .= ' FROM ' . $tableName;
		return $this;
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $condition
	 * @param null|(int|string)[]|int|string $params
	 * @return self
	 */
	public function where($condition, $params = null): self
	{
		if ($condition) {
			$this->checkMethod('where');
			$this->sql .= ' WHERE ';

			if (is_array($condition)) {
				$sqlConds = [];

				foreach ($condition as $column => $value) {
					$sqlCond = '';

					if (is_int($column)) {
						if (is_array($value) && count($value) == 3) {
							$sqlCond = $value[1] . ' ' . $value[0] . ' ?';
							$this->params[] = $value[2];
						}
					} else {
						if (is_array($value)) {
							$sqlCond = $column . ' IN (' . implode(', ', array_fill(0, count($value), '?')) . ')';
							$this->params = array_merge($this->params, $value);
						} else if (is_null($value)) {
							$sqlCond = $column . ' IS NULL';
						} else {
							$sqlCond = $column . ' = ?';
							$this->params[] = $value;
						}
					}

					$sqlConds[] = '(' . $sqlCond . ')';
				}

				$this->sql .= implode(' AND ', $sqlConds);
			} else {
				$this->sql .= $condition;
				if ($params) {
					if (is_array($params)) {
						$this->params = array_merge($this->params, $params);
					} else {
						$this->params[] = $params;
					}
				}
			}
		}

		return $this;
	}

	public function orderBy(string $expression, string $sort = 'ASC'): self
	{
		$this->checkMethod('orderBy');

		$this->sql .= ' ORDER BY ' . $expression . ' ' . $sort;
		return $this;
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return self
	 */
	public function limit(?int $limit, ?int $offset = null): self
	{
		if (!is_null($limit)) {
			$this->checkMethod('limit');

			$this->sql .= ' LIMIT ' . $limit;
			if ($offset) {
				$this->sql .= ' OFFSET ' . $offset;
			}
		}

		return $this;
	}

	public static function delete(): self
	{
		return new self(
			'DELETE',
			[
				'from'		=> true,
				'where'		=> false,
				'orderBy'	=> false,
				'limit'		=> false
			]
		);
	}

	public static function update(string $tableName): self
	{
		return new self(
			'UPDATE ' . $tableName,
			[
				'set'		=> true,
				'where'		=> false,
				'orderBy'	=> false,
				'limit'		=> false
			]
		);
	}

	/**
	 * @param string|array<string, int|string> $expression
	 * @param null|(int|string)[]|int|string $params
	 * @return self
	 */
	public function set($expression, $params = null): self
	{
		if ($expression) {
			$this->checkMethod('set');
			$this->sql .= ' SET ';

			if (is_array($expression)) {
				$sqlConds = [];

				foreach ($expression as $column => $value) {
					$sqlConds[] = $column . ' = ?';
					$this->params[] = $value;
				}

				$this->sql .= implode(', ', $sqlConds);
			} else {
				$this->sql .= $expression;
				if ($params) {
					if (is_array($params)) {
						$this->params = array_merge($this->params, $params);
					} else {
						$this->params[] = $params;
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @return \PDOStatement|null
	 */
	public function run(): ?\PDOStatement
	{
		foreach ($this->methods as $methodName => $methodParams) {
			if ($methodParams['required'] && !$methodParams['called']) {
				throw new \Exception('Method Core\Modules\Query::' . $methodName . ' is missed '
					. 'before getting SQL-statement');
			}
		}

		return Database::getInstance()
			->query("{$this->sql};", $this->params);
	}
}
