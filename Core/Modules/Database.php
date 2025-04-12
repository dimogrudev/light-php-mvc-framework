<?php

namespace Core\Modules;

final class Database
{
	use \Patterns\Singletone;

	/** @var \PDO $pdo Database connection */
	private \PDO $pdo;

	/** @var int $queryCount Total number of queries made to database */
	private int $queryCount			= 0;
	/** @var float $executionTime Total query execution time */
	private float $executionTime	= 0;

	private function __construct()
	{
		$config = \Core\Application::getConfigParam('pdo');

		try {
			$this->pdo = new \PDO("mysql:host={$config['host']};dbname={$config['dbName']};charset=UTF8", $config['user'], $config['pass']);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (\PDOException $e) {
			error_log('Database connection error: ' . $e->getMessage());
			\Core\Application::error(500);
		}
	}

	/**
	 * @param string $query SQL statement
	 * @param array $params Parameters
	 * 
	 * @return \PDOStatement|null
	 * Returns a **PDOStatement** object or **NULL** in case of an error
	 */
	public function query(string $query, array $params = []): ?\PDOStatement
	{
		try {
			$stmt = $this->pdo->prepare($query);
		} catch (\PDOException $e) {
			error_log('An error occured while preparing SQL statement: ' . $e->getMessage());
			\Core\Application::error(500);
		}

		if ($stmt) {
			$microtime = microtime(true);

			try {
				$result = $stmt->execute($params);
			} catch (\PDOException $e) {
				error_log('An error occured while executing SQL statement: ' . $e->getMessage());
				\Core\Application::error(500);
			}

			$this->queryCount++;
			$this->executionTime += microtime(true) - $microtime;

			if ($result) {
				return $stmt;
			}
		}

		return null;
	}

	public function getLastInsertId(): int
	{
		return (int)$this->pdo->lastInsertId();
	}

	public function getQueryCount(): int
	{
		return $this->queryCount;
	}

	public function getExecutionTime(): float
	{
		return $this->executionTime;
	}
}
