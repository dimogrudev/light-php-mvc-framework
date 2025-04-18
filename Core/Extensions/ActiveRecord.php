<?php

namespace Core\Extensions;

use Core\Modules\Database;

abstract class ActiveRecord
{
	/** @var array<string, int|string> $primaryKey Instance primary key */
	private array $primaryKey;

	/** @var array<string, int> $hashes Properties' hashes */
	private array $hashes;

	/** @var bool $newEntry Instance is a new entry in the table */
	private bool $newEntry		= true;

	/**
	 * Checks if instance primary key is set
	 * @return bool
	 */
	public function isPrimaryKeySet(): bool
	{
		return isset($this->primaryKey);
	}

	/**
	 * Gets instance primary key
	 * @return int|string|array<string, int|string> Instance primary key
	 */
	public function getPrimaryKey()
	{
		if (isset($this->primaryKey)) {
			$keyTypes = static::primaryKey();
			$keysNumber = count($keyTypes);

			if (count($this->primaryKey) == $keysNumber) {
				if ($keysNumber == 1) {
					return $this->primaryKey[array_key_first($keyTypes)];
				}
				return $this->primaryKey;
			}
		}

		throw new \Exception('Primary key is not set');
	}

	/**
	 * Sets instance primary key
	 * @param int|string ...$primaryKey Instance primary key
	 * @return void
	 */
	protected function setPrimaryKey(...$primaryKey): void
	{
		if (isset($this->primaryKey)) {
			throw new \Exception('Primary key is already set');
		}

		$keyTypes = static::primaryKey();

		if (count($primaryKey) == count($keyTypes)) {
			$this->primaryKey = [];

			$ai = static::autoIncrement();
			$index = 0;

			foreach ($keyTypes as $keyCol => $keyType) {
				if ($ai && $keyCol == $ai) {
					throw new \Exception('Column \'' . $keyCol . '\' is auto incremented');
				}
				if (gettype($primaryKey[$index]) != $keyType) {
					throw new \Exception('Primary keys types mismatch');
				}
				$this->primaryKey[$keyCol] = $primaryKey[$index];

				$index++;
			}

			return;
		}

		throw new \Exception('The number of arguments does not match the number of primary keys');
	}

	protected function unsetPrimaryKey(): void
	{
		unset($this->primaryKey);
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $condition
	 * @param null|(int|string)[]|int|string $params
	 * @param string[] $orderBy
	 * @return static|null
	 */
	public static function findOne($condition = [], $params = null, array $orderBy = [])
	{
		$query = Query::select('*')
			->from(static::tableName())
			->where($condition, $params);

		if ($orderBy) {
			$query = $query->orderBy($orderBy[0], $orderBy[1] ?? 'ASC');
		}
		$query = $query->limit(1);

		$stmt = $query->run();
		if ($stmt) {
			return self::fetch($stmt);
		}
		return null;
	}

	/**
	 * @param \PDOStatement $stmt
	 * @return static|null
	 */
	private static function fetch(\PDOStatement $stmt)
	{
		/** @var array|false $properties */
		$properties = $stmt->fetch(\PDO::FETCH_ASSOC);

		if ($properties) {
			$instance = new static();
			$instance->init($properties);

			return $instance;
		}
		return null;
	}

	/**
	 * @param string|array<int|string, null|(int|string)[]|int|string> $condition
	 * @param null|(int|string)[]|int|string $params
	 * @param string[] $orderBy
	 * @param int|null $limit
	 * @return static[]
	 */
	public static function findAll($condition = [], $params = null, array $orderBy = [], ?int $limit = null): array
	{
		$query = Query::select('*')
			->from(static::tableName())
			->where($condition, $params);

		if ($orderBy) {
			$query = $query->orderBy($orderBy[0], $orderBy[1] ?? 'ASC');
		}
		if ($limit) {
			$query = $query->limit($limit);
		}

		$stmt = $query->run();
		if ($stmt) {
			return self::fetchAll($stmt);
		}
		return [];
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return static[]
	 */
	public static function findBySql(string $sql, array $params = []): array
	{
		$stmt = Database::getInstance()
			->query($sql, $params);

		if ($stmt) {
			return self::fetchAll($stmt);
		}
		return [];
	}

	/**
	 * @param \PDOStatement $stmt
	 * @return static[]
	 */
	private static function fetchAll(\PDOStatement $stmt): array
	{
		$instances = [];

		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $properties) {
			$instance = new static();
			$instance->init($properties);

			$instances[] = $instance;
		}

		return $instances;
	}

	public function delete(): void
	{
		if (!$this->newEntry) {
			if (!isset($this->primaryKey)) {
				throw new \Exception('Primary key is needed for doing this action');
			}

			if ($this->beforeDelete()) {
				Query::delete()
					->from(static::tableName())
					->where($this->primaryKey)
					->run();

				unset($this->hashes);

				$this->newEntry = true;
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
			if ($this->newEntry) {
				$this->insert();

				if (static::autoIncrement()) {
					$keyTypes = static::primaryKey();
					$lastInsertId = Database::getInstance()
						->getLastInsertId();

					if (isset($keyTypes[static::autoIncrement()])) {
						if (!isset($this->primaryKey)) {
							$this->primaryKey = [];
						}
						$this->primaryKey[static::autoIncrement()] = $lastInsertId;
					} else {
						if (property_exists($this, static::autoIncrement())) {
							$this->{static::autoIncrement()} = $lastInsertId;
						}
					}
				}

				$this->newEntry = false;
				$this->refresh();
			} else {
				$this->update();
			}

			$this->afterSave();
		}
	}

	private function insert(): void
	{
		$mappedProperties = $this->getMappedProperties(false);
		if (isset($this->primaryKey)) {
			$mappedProperties = $this->primaryKey + $mappedProperties;
		}

		if (static::autoIncrement()) {
			$mappedProperties[static::autoIncrement()] = null;
		}

		if ($mappedProperties) {
			Query::insert()
				->into(static::tableName())
				->values($mappedProperties)
				->run();
		}
	}

	private function refresh(): void
	{
		if (!isset($this->primaryKey)) {
			throw new \Exception('Primary key is needed for doing this action');
		}

		$instance = static::findOne($this->primaryKey);

		if ($instance) {
			foreach ((new \ReflectionClass($instance))->getProperties() as $propertyInfo) {
				$propertyName = $propertyInfo->getName();

				if (!$propertyInfo->isPrivate()) {
					$this->$propertyName = $propertyInfo->getValue($instance);
				}
			}
		}
	}

	private function update(): void
	{
		$mappedProperties = $this->getMappedProperties(true);

		if ($mappedProperties) {
			if (!isset($this->primaryKey)) {
				throw new \Exception('Primary key is needed for doing this action');
			}

			Query::update(static::tableName())
				->set($mappedProperties)
				->where($this->primaryKey)
				->run();
		}
	}

	/**
	 * Initializes instance with specific properties
	 * @param array<string, string> $properties Instance properties
	 * @return void
	 */
	protected function init(array $properties): void
	{
		$classInfo = new \ReflectionClass($this);
		/** @var array<string, string> $primaryKeys */
		$primaryKeys = static::primaryKey();
		/** @var array<string, int> $hashes */
		$hashes = [];

		foreach ($properties as $propertyName => $propertyValue) {
			if (isset($primaryKeys[$propertyName])) {
				/** @var int|string $propertyValue */
				settype($propertyValue, $primaryKeys[$propertyName]);

				if (!isset($this->primaryKey)) {
					$this->primaryKey = [];
				}
				$this->primaryKey[$propertyName] = $propertyValue;
				continue;
			}

			$propertyInfo = false;

			if ($classInfo->hasProperty($propertyName)) {
				$propertyInfo = $classInfo->getProperty($propertyName);
			}

			if ($propertyInfo && !$propertyInfo->isPrivate()) {
				$hashes[$propertyName] = crc32($propertyValue);
				$propertyTypeInfo = $propertyInfo->getType();

				if ($propertyTypeInfo instanceof \ReflectionNamedType) {
					$propertyType = $propertyTypeInfo->getName();

					if ($propertyType == 'array') {
						$this->$propertyName = json_decode($propertyValue, true) ?? [];
						continue;
					}
				}

				$this->$propertyName = $propertyValue;
			}
		}

		$this->hashes = $hashes;
		$this->newEntry = false;
	}

	/**
	 * Gets mapped properties for working with database
	 * @param bool $checkHash Check hash
	 * @return array<string, mixed> Mapped properties
	 */
	private function getMappedProperties(bool $checkHash): array
	{
		if (!isset($this->hashes)) {
			$this->hashes = [];
		}

		/** @var array<string, mixed> $mappedProperties */
		$mappedProperties = [];

		foreach ((new \ReflectionClass($this))->getProperties() as $propertyInfo) {
			$propertyName = $propertyInfo->getName();

			if (
				!$propertyInfo->isPrivate()
				&& (!static::autoIncrement() || $propertyName != static::autoIncrement())
			) {
				$propertyType = gettype($this->$propertyName);

				if ($propertyType == 'boolean') {
					$propertyValue = intval($this->$propertyName);
				} else if ($propertyType == 'array') {
					$propertyValue = json_encode($this->$propertyName);
					$propertyValue = preg_replace('/\":([\"\d])/', '": $1', $propertyValue);
				} else {
					$propertyValue = $this->$propertyName;
				}

				$propertyHash = crc32((string)$propertyValue);

				if (!$checkHash || !isset($this->hashes[$propertyName]) || $this->hashes[$propertyName] != $propertyHash) {
					$mappedProperties[$propertyName] = $propertyValue;
					$this->hashes[$propertyName] = $propertyHash;
				}
			}
		}

		return $mappedProperties;
	}

	abstract public static function tableName();

	public static function primaryKey()
	{
		return ['id' => 'integer'];
	}

	public static function autoIncrement()
	{
		return 'id';
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
