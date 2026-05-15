<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpFoundation\RequestStack;

session_start();

// Обработка статических файлов (изображения, PDF)
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('#^/uploads/(covers|files)/#', $requestUri)) {
    $filePath = __DIR__ . '/..' . $requestUri;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'fb2' => 'application/xml',
            'mobi' => 'application/x-mobipocket-ebook'
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        
        if (strpos($mimeType, 'image/') === 0) {
            header('Cache-Control: public, max-age=3600');
        }
        
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        echo "File not found";
        exit;
    }
}

$request = Request::createFromGlobals();
$requestStack = new RequestStack();
$requestStack->push($request);

$routes = new RouteCollection();

$routes->add('login_post', new Route('/login', ['_controller' => 'App\Controller\AuthController::doLogin'], [], [], '', [], ['POST']));
$routes->add('login', new Route('/login', ['_controller' => 'App\Controller\AuthController::login'], [], [], '', [], ['GET']));
$routes->add('logout', new Route('/logout', ['_controller' => 'App\Controller\AuthController::logout'], [], [], '', [], ['GET']));
$routes->add('home', new Route('/', ['_controller' => 'App\Controller\BookController::index'], [], [], '', [], ['GET']));
$routes->add('book_add', new Route('/books/add', ['_controller' => 'App\Controller\BookController::add'], [], [], '', [], ['GET']));
$routes->add('book_add_post', new Route('/books/add', ['_controller' => 'App\Controller\BookController::create'], [], [], '', [], ['POST']));
$routes->add('book_edit', new Route('/books/{id}/edit', ['_controller' => 'App\Controller\BookController::edit'], ['id' => '\d+'], [], '', [], ['GET']));
$routes->add('book_edit_post', new Route('/books/{id}/edit', ['_controller' => 'App\Controller\BookController::update'], ['id' => '\d+'], [], '', [], ['POST']));
$routes->add('book_delete', new Route('/books/{id}/delete', ['_controller' => 'App\Controller\BookController::delete'], ['id' => '\d+'], [], '', [], ['POST']));
$routes->add('book_download', new Route('/books/{id}/download', ['_controller' => 'App\Controller\BookController::download'], ['id' => '\d+'], [], '', [], ['GET']));

$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);
$controllerResolver = new ControllerResolver();
$argumentResolver = new ArgumentResolver();

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new RouterListener($matcher, $requestStack));

$kernel = new HttpKernel($dispatcher, $controllerResolver, $requestStack, $argumentResolver);

try {
    $response = $kernel->handle($request);
    $response->send();
} catch (\Exception $e) {
    error_log("Exception: " . $e->getMessage());
    $response = new Response('Error: ' . $e->getMessage(), 500);
    $response->send();
}
