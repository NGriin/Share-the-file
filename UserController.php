<?php

require_once 'DataBaseConnection.php';

class UserController
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    public function create()
    {
        $user = new User();

        $data = json_decode(file_get_contents("php://input"));
        if (
            !empty($data->email) &&
            !empty($data->password) &&
            !empty($data->role)
        ){
            $user->email = $data->email;
            $user->password = $data->password;
            $user->role = $data->role;
        }

        if($user->create()){
            http_response_code(201);
            echo 'Пользователь успешно создан';
        } else {
            http_response_code(503);
            echo "Невозможно создать пользователя";
        }

    }


    public function updateUser($params)
    {
        $connection = new DataBaseConnection();
        $data = $this->connection->getConnection()->query("SELECT * FROM user");

        foreach ($data as $row) {
            echo " ID: " . $row['id'] . "<br />";
            echo " Email: " . $row['email'] . "<br />";
        }
    }

    public function getUserArr()
    {
        $getUser = $this->connection->getConnection()->prepare("SELECT * FROM user");
        $getUser->execute();
        $row = $getUser->fetchAll(PDO::FETCH_ASSOC);
        if (count($row) === 0) {
            echo "Пользователи не найдены";
        } else {
            var_dump($row);
        }
    }
}