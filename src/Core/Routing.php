<?php

namespace App\Core;

use App\Core\Response\Response;

class Routing
{
    private const ControllerNamespace = 'App\\Controller';
    private const routes = [
        '/user/' => [
            'GET' => 'User::getAll()',
            'POST' => 'User::addUser()',
            'PUT' => 'User::updateUser()'
        ],
        '/user/login' => [
            'GET' => 'User::login()'
        ],
        '/user/logout' => [
            'GET' => 'User::logout()'
        ],
        'user/resetpassword' => [
            'GET' => 'User::resetPassword()'
        ],
        '/user/search/{email}' => [
            'GET' => 'User::getByEmail()',
        ],
        '/user/{id}' => [
            'GET' => 'User::getById()',
            'DELETE' => 'User::deleteById()'
        ],
        '/admin/user/' => [
            'GET' => 'Admin::getAll()',
            'PUT' => 'Admin::updateUser()'
        ],
        '/admin/user/{id}' => [
            'GET' => 'Admin::getById()',
            'DELETE' => 'Admin::deleteUserById()'
        ],
        '/file/' => [
            'POST' => 'File::create()',
            'GET' => 'File::getAll()',
            'PUT' => 'File::renameOrMove()'
        ],
        '/file/{id}' => [
            'GET' => 'File::getInfo()',
            'DELETE' => 'File::deleteFileById()',
        ],
        '/directory/' => [
            'POST' => 'File::createDirectory()',
            'PUT' => 'File::renameDirectory()'
        ],
        '/directory/{id}' => [
            'GET' => 'File::getDirectory()',
            'DELETE' => 'File::deleteDirectory()'
        ],
        '/files/share/{id}/{id_user}' => [
            'PUT' => 'File::putAccess()',
            'DELETE' => 'File::deleteAccess()'
        ],
        '/files/share/{id}' => [
            'GET' => 'File::getAccessUsers()',
        ],

    ];

    public function start(Request $request)
    {
        foreach (self::routes as $route => $routeParams) {
            $pattern = $this->translateRouteToPatterm($route);
            if (preg_match($pattern, $request->getUri(), $matches)) {
                if (isset($routeParams[$request->getMethod()])) {
                    list($className, $method) = $this->getTargetCOntroller($routeParams[$request->getMethod()]);
                    $class = new $className;
                    $response = call_user_func_array([$class, $method], array_merge([$request], array_slice($matches, 1)));
                    return $response;
                }
                break;
            };
        }
        return new Response(['Error' => 'page not found'], 404);
    }

    protected function getTargetCOntroller($raw)
    {
        list($controllerName, $method) = explode('::', $raw);
        $method = substr($method, 0, -2);
        $className = self::ControllerNamespace . '\\' . $controllerName . 'Controller';
        return [$className, $method];
    }


    protected function translateRouteToPatterm($route)
    {
        return '/^' . str_replace('/', '\/', preg_replace('/{.+?}/', '(.+)', $route)) . '$/';
    }
}