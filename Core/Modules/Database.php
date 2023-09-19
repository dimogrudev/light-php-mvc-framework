<?php

namespace Core\Modules;

class Database
{
	private static self $instance;
	private \PDO $pdo;

	public int $rowCount = 0;

	private function __construct()
	{
		try {
			extract(\Core\Application::$config['pdo']);

			$this->pdo = new \PDO("mysql:host={$host};dbname={$dbName};charset=UTF8", $user, $pass);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (\Exception $e) {
			\Core\Application::error(500);
		}
	}

	public static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function query(string $sql, array $params = []): array
	{
		$sth = $this->pdo->prepare($sql);
		$result = $sth->execute($params);

		if ($result === false) {
			$this->rowCount = 0;
			return [];
		}

		$this->rowCount = $sth->rowCount();
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getLastInsertId(): int
	{
		return (int)$this->pdo->lastInsertId();
	}
}
