<?php

namespace Core;

use Core\Modules\Database;
use Core\Modules\Query;

abstract class ActiveRecord
{
	/** @var null|int */
	private ?int $id = null;

	public function getId(): ?int
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

	/** @return null|static */
	public static function findOne(array $where = [], array $orderBy = [])
	{
		$result = Query::select('*')
			->from(static::tableName())
			->where($where)
			->orderBy($orderBy[0] ?? 'id', $orderBy[1] ?? 'ASC')
			->limit(1)
			->run(Database::getInstance(), static::class);
		return ($result) ? $result[0] : null;
	}

	public static function findAll(array $where = [], array $orderBy = []): array
	{
		return Query::select('*')
			->from(static::tableName())
			->where($where)
			->orderBy($orderBy[0] ?? 'id', $orderBy[1] ?? 'ASC')
			->run(Database::getInstance(), static::class);
	}

	public static function findBySql(string $sql, array $params = []): array
	{
		return (Database::getInstance())->query($sql, $params, static::class);
	}

	public function delete(): void
	{
		if ($this->id !== null) {
			if ($this->beforeDelete()) {
				Query::delete()
					->from(static::tableName())
					->where(['id' => $this->id])
					->run(Database::getInstance());
				$this->id = null;

				$this->afterDelete();
			}
		}
	}

	public static function deleteAll(?array $condition = null): void
	{
		Query::delete()
			->from(static::tableName())
			->where($condition)
			->run(Database::getInstance());
	}

	public function save(): void
	{
		if ($this->beforeSave()) {
			if ($this->id !== null) {
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
			->run(Database::getInstance());
	}

	private function insert(): void
	{
		$dbInstance = Database::getInstance();

		Query::insert()
			->into(static::tableName())
			->values($this->getMappedProperties())
			->run($dbInstance);

		$this->id = $dbInstance->getLastInsertId();
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

	protected function afterSave(): void
	{
	}

	protected function afterDelete(): void
	{
	}
}
