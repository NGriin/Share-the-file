<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response\Response;
use App\Core\UserStorage;
use App\Database\DataBaseConnection;
use App\Entity\User;
use PHPMailer\PHPMailer\PHPMailer;

class UserController
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    public function addUser(Request $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');
        $role = $request->get('role');

        if (!$email || !$password || !$role) {
            return new Response(['Error' => 'Данные некорректны'], 500);
        }
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(md5($password));
        $user->setRole($role);

        try {
            $user->create();
        } catch (\Exception $e) {
            return new Response(['Error' => 'При создании пользователя произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Created']);
    }

    public function login($request)
    {
        $userStorage = new UserStorage();
        if ($userStorage->getUser()) {
            return new Response(['Status' => 'Authenticated']);
        }


        $email = $request->get('email');
        $password = $request->get('password');


        if (!$user = $this->getByPasswordrAndEmail($password, $email)) {
            return new Response(['Error' => 'Ошибка авторизации'], 500);
        }
        $userStorage->storeUser($user->getId());
        print_r($_SESSION['user_id']);
        return new Response(['Status' => 'Authenticated']);
    }

    public function logout()
    {
        $userStorage = new UserStorage();
        $userStorage->clear();
        return new Response(['Status' => 'Unauthenticated']);
    }

    public function resetPassword(Request $request)
    {
        if (!$email = $request->get('email')) {
            return new Response(['Error' => 'Некорректно введенные данные'], 500);
        }
        /**
         * @var $user User
         */
        if (!$this->getUser($email)) {
            return new Response(['Error' => 'Пользователь не найден'], 500);
        }
        if ($email = $request->get('email')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = ''; /*Адрес почты, с котрой будет осуществляться отправка письма*/
                $mail->Password = ''; /*Пароль приложения почты, с котрой будет осуществляться отправка письма*/
                $mail->Port = 587;
                $mail->SMTPSecure = 'tls';
                $mail->addAddress($request->get('email'));
                $mail->Body = 'Для того чтобы сменить пароль перейдите по ссылке:';
                $mail->Subject = 'Изменение пароля';
                $mail->send();
            } catch (\Exception $e) {
                return new Response(['Error' => 'При отправе письма произошла ошибка'], 500);
            }
        }
        return new Response(['Status' => 'Sent']);
    }


    public function updateUser(Request $request)
    {
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

    public function getAll()
    {
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
        if ($user = $this->getUser($id)) {
            $userArr = $user->asArray();
            return new Response($userArr);
        }
        return new Response(['error' => 'User not found'], 500);
    }

    public function getByEmail(Request $request)
    {
        $email = basename($request->getUri());
        $stmt = $this->connection->getConnection()->prepare('SELECT email FROM user WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user) {
            return new Response(['Error' => 'Пользователь не найден'], 500);
        }
        return new Response($user['email']);
    }

    public function deleteById(Request $request)
    {
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

    protected function getUser($id)
    {
        $getUser = $this->connection->getConnection()->prepare("SELECT id, email, password, role FROM user WHERE id = ?");
        $getUser->execute(array($id));
        $userArr = $getUser->fetchAll(\PDO::FETCH_ASSOC);
        if (count($userArr)) {
            return $this->hydrateUser($userArr[0]);
        }
        return null;
    }

    protected function getByPasswordrAndEmail($password, $email)
    {
        $getUser = $this->connection->getConnection()->prepare("SELECT * FROM user WHERE email = ? and password = ?");
        $getUser->execute(array($email, md5($password)));
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