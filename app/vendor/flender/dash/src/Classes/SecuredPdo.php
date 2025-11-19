<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use PDO;
use PDOException;
use PDOStatement;

class SecuredPdo
{

    public function __construct(private PDO $pdo, private ILogger $logger)
    {
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function query_one(string $sql, array $params = []): ?array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    private function prepareAndExecute(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $this->logger->error("Failed prepare SQL statement", [
                "sql" => $sql,
                "params" => $params
            ]);
            throw new PDOException("Error SQL preparing statement");
        }
        $exec = $stmt->execute($params);
        if ($exec === false) {
            $this->logger->error("Failed executing SQL", [
                "sql" => $sql,
                "params" => $params
            ]);
            throw new PDOException("Error SQL executing statement");
        }
        return $stmt;
    }

}