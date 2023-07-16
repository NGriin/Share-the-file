<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response\Response;
use App\Database\DataBaseConnection;
use App\Entity\File;


class FileController
{
    private $uploadFilesDir = 'C:\xampp\htdocs\myfiles\UploadsFiles';  /*Заменить на свой путь*/
    protected $connection;

    public function __construct()
    {
        $this->connection = new DataBaseConnection();
    }

    function create()
    {
        $file = $_FILES['file'];

        if (!is_dir('./UploadsFiles')) {
            return new Response(['Error' => 'Папка с файлами не найдена'], 500);
        }
        move_uploaded_file($file['tmp_name'], './UploadsFiles' . DIRECTORY_SEPARATOR . $file['name']);
        $name = $file['name'];
        $path = 'UploadsFiles' . DIRECTORY_SEPARATOR . $file['name'];
        $file = new File();
        $file->setFileName($name);
        $file->setPath($path);
        try {
            $file->create();
        } catch (\Exception $e) {
            return new Response(['Error' => 'При создании файла произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Created']);
    }

    function getAll()
    {
        return new Response($this->getFiles('UploadsFiles'));
    }

    function getFiles($dir)
    {
        $fileName = scandir($dir);
        $ids = [];
        foreach ($fileName as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $ids = array_merge($ids, $this->getFiles($path));
            } else {
                $ids[] = $path;
            }
        }
        return $ids;
    }

    function getFilesFromBase($id)
    {
        $getFile = $this->connection->getConnection()->prepare("SELECT id, name, path FROM files WHERE id = ?");
        $getFile->execute(array($id));
        $fileArr = $getFile->fetchAll(\PDO::FETCH_ASSOC);
        if (count($fileArr)) {
            return $this->hydrateFile($fileArr[0]);
        }
        return null;
    }

    protected function hydrateFile($fileData)
    {
        $file = new File();
        $file->setId($fileData['id']);
        $file->setFileName($fileData['name']);
        $file->setPath($fileData['path']);
        return $file;
    }

    function getInfo(Request $request)
    {
        $id = basename($request->getUri());

        if ($file = $this->getFilesFromBase($id)) {
            $fileArr = $file->asArray();
            return new Response($fileArr);
        }
        return new Response(['error' => 'File not found'], 500);
    }

    function renameOrMove(Request $request)
    {
        $oldPath = $request->get('old_path');
        $newPath = $request->get('new_path');
        $file = new File();

        if (rename($oldPath, $newPath)) {
            try {
                $file->update($request->getParams());
            } catch (\Exception $e) {
                return new Response(['Error' => 'При обновлении данных в бд произошла ошибка'], 500);
            }
            return new Response(['Status' => 'Updated']);
        } else {
            return new Response(['Error' => 'При обновлении файла произошла ошибка'], 500);
        }
    }

    function deleteFileById(Request $request)
    {
        try {
            $id = basename($request->getUri());
            $file = $this->getFilesFromBase($id);
            $path = $file->asArray();
            unlink($path['path']);
            $stmt = $this->connection->getConnection()->prepare("Delete FROM files WHERE id = ?");
            $test = $stmt->execute(array($id));
            if (!$stmt->rowCount()) {
                return new Response(['Error' => 'При удалении файла произошла ошибка'], 500);
            }
        } catch (\Exception $e) {
            return new Response(['Error' => 'При удалении файла произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Deleted']);
    }

    function createDirectory(Request $request)
    {
        $path = $request->get('path');
        $lastSlash = strrpos($path, '\\');
        $folderPath = substr($path, 0, $lastSlash);
        try {
            if (!is_dir($folderPath)) {
                return new Response(['Error' => 'Данного пути не существует'], 500);
            } else {
                mkdir($path);
            }
        } catch (\Exception $e) {
            return new Response(['Error' => 'При удалении файла произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Created']);
    }

    function renameDirectory(Request $request)
    {
        $oldName = $request->get('old_path');
        $newName = $request->get('new_path');
        $file = new File();
        try {
            if (!file_exists($oldName)) {
                return new Response(['Error' => 'Данной папки не существует'], 500);
            } else {
                $file->find($request->getParams());
                rename($oldName, $newName);
            }
        } catch (\Exception $e) {
            return new Response(['Error' => 'При обновлении имени папки произошла ошибка'], 500);
        }
        return new Response(['Status' => 'Renamed']);
    }

    function getDirectory(Request $request)
    {
        $parentDir = $this->uploadFilesDir;
        $subDir = basename($request->getUri());
        $subPath = $this->findPath($parentDir, $subDir);
        $filesArr = [];

        if ($subDir == basename($parentDir)) {
            $files = scandir($parentDir);
            foreach ($files as $file) {
                if (is_file($parentDir . '\\' . $file)) {
                    $filesArr[] = $file;
                }
            }
        } else {
            if ($subPath !== false) {
                $files = scandir($subPath);
                foreach ($files as $file) {
                    if (is_file($subPath . '\\' . $file)) {
                        $filesArr[] = $file;
                    }
                }
            }
        }
        if (!empty($filesArr)) {
            return new Response($filesArr);
        } else {
            return new Response(['Error' => 'Данной папки не существует'], 500);
        }
    }

    function findPath($dir, $subDir)
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == "." || $item == "..") {
                continue;
            }
            $path = $dir . "\\" . $item;

            if (is_dir($path)) {
                if ($item == $subDir) {
                    return $path;
                } else {
                    $subPath = $this->findPath($path, $subDir);

                    if ($subPath !== false) {
                        return $subPath;
                    }
                }
            }
        }
        return false;
    }

    function deleteDirectory(Request $request)
    {
        $parentDir = $this->uploadFilesDir;
        $subDir = basename($request->getUri());
        $subPath = $this->findPath($parentDir, $subDir);
        $file = new File();
        $file->deleteByPath($subDir);
        return new Response(['Status' => 'Deleted'], $this->deleteDir($subPath));
    }

    function deleteDir($path)
    {
        if (!file_exists($path)) {
            return true;
        }
        if (!is_dir($path)) {
            return unlink($path);
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!$this->deleteDir($path . '\\' . $item)) {
                return false;
            }
        }
        return rmdir($path);
    }

    function getAccessUsers(Request $request)
    {
        $id = [];
        $fileId = basename($request->getUri());
        $file = new File();
        if (!$file->findById($fileId)) {
            return new Response(['Error' => 'У данного файла нет пользователей имеющих к нему доступ'], 500);
        } else {
            $stmt = $this->connection->getConnection()->prepare("SELECT user_id FROM file_access WHERE file_id = :file_id");
            $stmt->bindParam(':file_id', $fileId);
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $userId = array_column($results, 'user_id');
            $emails = $this->getEmailById($userId);
            if (!$emails) {
                return new Response(['Error' => 'Пользователи не найдены'], 500);
            } else {
                return new Response($emails);
            }
        }
    }

    function getEmailById($userId)
    {
        $placeholders = implode(',', array_fill(0, count($userId), '?'));
        $stmt = $this->connection->getConnection()->prepare(('SELECT email FROM user WHERE id IN (' . implode(',', $userId) . ')'));
        $stmt->execute();
        $emails = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $emails;
    }

    function putAccess(Request $request)
    {
        $userId = basename($request->getUri());
        $fileId = explode('/', $request->getUri());
        $fileId = $fileId[count($fileId) - 2];

        $file = new File();
        if ($file->getAccess($fileId, $userId) === false) {
            return new Response(['Error' => 'Предоставить доступ невозможно, так как данного файла не существует'], 500);
        } else {
            return new Response(['Status' => 'Access completed']);
        }
    }

    function deleteAccess(Request $request)
    {
        $userId = basename($request->getUri());
        $fileId = explode('/', $request->getUri());
        $fileId = $fileId[count($fileId) - 2];

        $file = new File();
        $file->deleteAccess($fileId, $userId);

        return new Response(['Status' => 'Access denied']);
    }
}