<?php

namespace App\Entity;

use App\Database\DataBaseConnection;

class User
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    private $tableName = 'user';

    public $id;
    public $email;
    public $password;
    public $role;


    function create()
    {
        $query = "INSERT INTO {$this->tableName} (email,password,role) values (:email,:password,:role);";

        $stmt = $this->connection->getConnection()->prepare($query);

        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        $stmt->execute();
    }

    function update($params)
    {
        $query = "update {$this->tableName} set email = :email, password = :password, role =:role where id = :id";

        $stmt = $this->connection->getConnection()->prepare($query);

        $email = $params['email']?? $this->email;
        $password = $params['password']?? $this->password;
        $role = $params['role']?? $this->role;

        $stmt->bindParam(':email',$email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    public function asArray()
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'role' => $this->getRole(),

        ];
    }
}