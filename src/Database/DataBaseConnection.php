<?php

namespace App\Database;

class DataBaseConnection
{
    public $connection;
    private $host = 'localhost:3325';
    private $dbName = 'myfiles';
    private $userName = 'root';
    private $password = '';


    public function getConnection()
    {
        $this->connection = NULL;

        try {
            $this->connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->dbName, $this->userName, $this->password);
            $this->connection->exec('set names utf8');
        } catch (\PDOException $e) {
            echo 'Ошибка подключения к базе данных' . $e->getMessage();
        }
        return $this->connection;
    }
}