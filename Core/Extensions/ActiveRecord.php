<?php

namespace Core\Extensions;

use Core\Modules\Database;

abstract class ActiveRecord
{
	/** @var int */
	private int $id;

	public function getId(): int
	{
		return $this->id;
	}

	public function __set(string $name, $value)
	{
		$propertyName = self::underscoreToCamelCase($name);
		$this->$propertyName = $value;
	}

	public static function getDbInstance(): Database
	{
		return Database::getInstance();
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $where
	 * @param string[] $orderBy
	 * @return static|null
	 */
	public static function findOne($where = [], array $orderBy = [])
	{
		$stmt = (Query::select('*'))
			->from(static::tableName())
			->where($where)
			->orderBy($orderBy[0] ?? 'id', $orderBy[1] ?? 'ASC')
			->limit(1)
			->run();

		if ($stmt) {
			$stmt->setFetchMode(\PDO::FETCH_CLASS, static::class);
			return $stmt->fetch() ?: null;
		}
		return null;
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $where
	 * @param string[] $orderBy
	 * @param int|null $limit
	 * @return static[]
	 */
	public static function findAll($where = [], array $orderBy = [], ?int $limit = null): array
	{
		$stmt = Query::select('*')
			->from(static::tableName())
			->where($where)
			->orderBy($orderBy[0] ?? 'id', $orderBy[1] ?? 'ASC')
			->limit($limit)
			->run();
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_CLASS, static::class) : [];
	}

	/** @return static[] */
	public static function findBySql(string $sql, array $params = []): array
	{
		$stmt = Database::getInstance()
			->query($sql, $params);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_CLASS, static::class) : [];
	}

	public function delete(): void
	{
		if (isset($this->id)) {
			if ($this->beforeDelete()) {
				Query::delete()
					->from(static::tableName())
					->where(['id' => $this->id])
					->run();
				unset($this->id);

				$this->afterDelete();
			}
		}
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $condition
	 * @return void
	 */
	public static function deleteAll($condition = []): void
	{
		Query::delete()
			->from(static::tableName())
			->where($condition)
			->run();
	}

	public function save(): void
	{
		if ($this->beforeSave()) {
			if (isset($this->id)) {
				$this->update();
			} else {
				$this->insert();
			}

			$this->afterSave();
		}
	}

	private function update(): void
	{
		Query::update(static::tableName())
			->set($this->getMappedProperties())
			->where(['id' => $this->id])
			->run();
	}

	private function insert(): void
	{
		Query::insert()
			->into(static::tableName())
			->values($this->getMappedProperties())
			->run();

		$this->id = Database::getInstance()
			->getLastInsertId();
		$this->refresh();
	}

	private function refresh(): void
	{
		$dbObject = static::findOne(['id' => $this->id]);

		foreach ((new \ReflectionObject($dbObject))->getProperties() as $property) {
			$property->setAccessible(true);
			$propertyName = $property->getName();

			if (!in_array($propertyName, static::ignoreAttributes())) {
				$this->$propertyName = $property->getValue($dbObject);
			}
		}
	}

	private function getMappedProperties(): array
	{
		$mappedProperties = [];

		foreach ((new \ReflectionObject($this))->getProperties() as $property) {
			$propertyName = $property->getName();

			if (!in_array($propertyName, static::ignoreAttributes())) {
				$propertyType = gettype($this->$propertyName);

				if ($propertyType == 'boolean') {
					$propertyValue = intval($this->$propertyName);
				} else {
					$propertyValue = $this->$propertyName;
				}

				$propertyNameAsUnderscore = self::camelCaseToUnderscore($propertyName);
				$mappedProperties[$propertyNameAsUnderscore] = $propertyValue;
			}
		}

		return $mappedProperties;
	}

	private static function underscoreToCamelCase(string $str): string
	{
		return lcfirst(str_replace('_', '', ucwords($str, '_')));
	}

	private static function camelCaseToUnderscore(string $str): string
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
	}

	abstract public static function tableName();

	public static function ignoreAttributes()
	{
		return [];
	}

	protected function beforeSave(): bool
	{
		return true;
	}

	protected function beforeDelete(): bool
	{
		return true;
	}

	protected function afterSave(): void {}

	protected function afterDelete(): void {}
}
