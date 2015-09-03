<?php

require __DIR__ . "/../vendor/autoload.php";

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('POST', '/code/new', 'create');
    $r->addRoute('GET', '/code/{id:.+}', 'fetch');
    $r->addRoute('GET', '/code-demo/', 'demo');
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header('Status: 404 Not Found');
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        header('Status: 405 Method Not Allowed');
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        if (false === $handler($vars)) {
            header('Status: 404 Not Found');
            echo "404 Not Found";
            break;
        }
        break;
}

