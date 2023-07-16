<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response\Response;
use App\Core\UserStorage;
use App\Database\DataBaseConnection;
use App\Entity\User;

class AdminController
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    public function getAll()
    {
        if (!$this->checkAdmin()) {
            return new Response(['Error' => 'Для доступа необходимы права администратора'], 500);
        }
        $getUser = $this->connection->getConnection()->prepare("SELECT * FROM user");
        $getUser->execute();
        $row = $getUser->fetchAll(\PDO::FETCH_ASSOC);
        if (count($row) === 0) {
            return new Response(['Error' => "Пользователи не найдены"], 500);
        } else {
            return new Response($row);
        }
    }

    public function getById(Request $request)
    {
        $id = basename($request->getUri());
        if (!$this->checkAdmin()) {
            return new Response(['Error' => 'Для доступа необходимы права администратора'], 500);
        }
        if ($user = $this->getUser($id)) {
            return $user->asArray();
        }
        return new Response(['error' => 'User not found'], 500);
    }

    public function deleteUserById(Request $request)
    {
        if (!$this->checkAdmin()) {
            return new Response(['Error' => 'Для доступа необходимы права администратора'], 500);
        }
        try {
            $id = basename($request->getUri());
            $stmt = $this->connection->getConnection()->prepare("Delete FROM user WHERE id = ?");
            $test = $stmt->execute(array($id));
            if (!$stmt->rowCount()) {
                return new Response(['Error' => 'При удалении пользователя произошла ошибка'], 500);
            }
        } catch (\Exception $e) {
            return new Response(['Error' => 'При удалении пользователя произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Deleted']);

    }

    public function updateUser(Request $request)
    {
        if (!$this->checkAdmin()) {
            return new Response(['Error' => 'Для доступа необходимы права администратора'], 500);
        }
        if (!$id = $request->get('id')) {
            return new Response(['Error' => 'Некорректно введенные данные'], 500);
        }
        /**
         * @var $user User
         */
        if (!$user = $this->getUser($id)) {
            return new Response(['Error' => 'Пользователь не найден'], 500);
        }
        try {
            $user->update($request->getParams());
        } catch (\Exception $e) {
            return new Response(['Error' => 'При обновлении пользователя произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Updated']);
    }

    protected
    function checkAdmin()
    {
        $userStorage = new UserStorage();
        if (!$user = $userStorage->getUser()) {
            return false;
        }

        if ($user->getRole() != 'admin') {
            return false;
        }
        return true;
    }

    protected
    function getUser($id)
    {
        $getUser = $this->connection->getConnection()->prepare("SELECT id, email, password, role FROM user WHERE id = ?");
        $getUser->execute(array($id));
        $userArr = $getUser->fetchAll(\PDO::FETCH_ASSOC);
        if (count($userArr)) {
            return $this->hydrateUser($userArr[0]);
        }
        return null;
    }

    protected
    function hydrateUser($userData)
    {
        $user = new User();
        $user->setId($userData['id']);
        $user->setPassword($userData['password']);
        $user->setEmail($userData['email']);
        $user->setRole($userData['role']);
        return $user;
    }

}