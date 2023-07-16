<?php

namespace App\Core;

use App\Database\DataBaseConnection;
use App\Entity\User;

class UserStorage
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    public function getUser()
    {
        if(!$userId = $_SESSION['user_id'] ?? null) {
            return null;
        }
        return $this->getUserById($userId);
    }

    public function storeUser($userId)
    {
        $_SESSION['user_id'] = $userId;
    }

    public function clear()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['email']);
        session_unset();
        session_destroy();
    }

    protected function getUserById($id)
    {
        $getUser = $this->connection->getConnection()->prepare("SELECT id, email, password, role FROM user WHERE id = ?");
        $getUser->execute(array($id));
        $userArr = $getUser->fetchAll(\PDO::FETCH_ASSOC);
        if (count($userArr)) {
            return $this->hydrateUser($userArr[0]);
        }
        return null;
    }

    protected function hydrateUser($userData)
    {
        $user = new User();
        $user->setId($userData['id']);
        $user->setPassword($userData['password']);
        $user->setEmail($userData['email']);
        $user->setRole($userData['role']);
        return $user;
    }
}