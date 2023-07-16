<?php

namespace App\Entity;

use App\Core\Response\Response;
use App\Database\DataBaseConnection;

class File
{
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    public $id;
    public $fileName;
    public $path;

    private $tableName = 'files';

    function create()
    {
        $query = "INSERT INTO {$this->tableName} (owner_id,name,path) values (:owner,:fileName,:path);";

        $stmt = $this->connection->getConnection()->prepare($query);

        $stmt->bindParam(':fileName', $this->fileName);
        $stmt->bindParam(':path', $this->path);
        $stmt->bindParam(':owner', $_SESSION['user_id']);
        $stmt->execute();

    }

    function update($params)
    {
        $query = "update {$this->tableName} set name = :name, path = :path where path = :oldpath";

        $stmt = $this->connection->getConnection()->prepare($query);

        $fileName = basename($params['new_path']) . PHP_EOL ?? $this->fileName;
        $path = $params['new_path'] ?? $this->path;

        $stmt->bindParam(':name', $fileName);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':oldpath', $params['old_path']);
        $stmt->execute();
    }

    function find($params)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE path LIKE :param";

        $oldPath = basename($params['old_path']);
        $newPath = basename($params['new_path']);

        $stmt = $this->connection->getConnection()->prepare($query);
        $stmt->execute(['param' => "%$oldPath%"]);
        $results = $stmt->fetchAll();
        $pathArr = [];
        $idArr = [];
        foreach ($results as $result) {
            array_push($pathArr, $result['path']);
            array_push($idArr, $result['id']);
        }

        $newPathArr = str_replace($oldPath, $newPath, $pathArr);

        for ($i = 0; $i < count($newPathArr); $i++) {
            $this->updatePath($newPathArr[$i], $idArr[$i]);

        }
    }

    function updatePath($newPath, $id)
    {
        $query = "update {$this->tableName} set path = :newPath where id = :id";

        $stmt = $this->connection->getConnection()->prepare($query);

        $stmt->bindParam(':newPath', $newPath);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    function deleteByPath($subDir)
    {
        $query = "DELETE FROM {$this->tableName} WHERE path LIKE :param";
        $stmt = $this->connection->getConnection()->prepare($query);
        $stmt->execute(['param' => "%$subDir%"]);
    }

    function getAccess($fileId, $userId)
    {
        $query = "INSERT INTO file_access (file_id, user_id) VALUES (:file_id, :user_id)";

        if (!$this->findById($fileId)) {
            return false;
        } else {
            $stmt = $this->connection->getConnection()->prepare($query);

            $stmt->bindParam(':file_id', $fileId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        }
        return true;
    }

    function deleteAccess($fileId, $userId)
    {
        $query = "DELETE FROM file_access WHERE file_id = :file_id AND user_id = :user_id";
        $stmt = $this->connection->getConnection()->prepare($query);
        $stmt->bindParam(':file_id', $fileId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    function findById($fileId)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->connection->getConnection()->prepare($query);
        $stmt->execute([$fileId]);
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
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
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path): void
    {
        $this->path = $path;
    }

    public function asArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getFileName(),
            'path' => $this->getPath(),

        ];
    }

}