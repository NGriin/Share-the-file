<?php
session_start();

use App\Core\Request;
use App\Core\Response;
use App\Core\Routing;

spl_autoload_register(function ($className) {
    $className = str_replace('App',__DIR__.DIRECTORY_SEPARATOR.'src',$className);
    $file = $className.'.php';
    if(file_exists($file)) {
        include_once $file;
    }
});

$request = new Request();
$router = new Routing();
/**
 * @var Response $response
 */
$response = $router->start($request);

header('Content-Type: application/json; charset=utf-8');
http_response_code($response->getStatus());
echo json_encode($response->getContent());


//$user = new User();
//
//$uri = $_SERVER['REQUEST_URI'];
//$method = $_SERVER['REQUEST_METHOD'];
//
//$urlList = [
//    '/user' => [
//        'GET' => [$user, 'baseList'],
//        'POST' => [$user, 'create']
//    ],
//];
//
//if ($uri === '/user') {
//    if ($method == 'GET') {
//        $urlList['/user']['GET']();
//    }elseif ($method == 'POST'){
//        $urlList['/user']['POST']();
//    }
//}
