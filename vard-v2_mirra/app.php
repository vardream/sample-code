<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use core\Registry;
use core\DBMySQL;
use core\Models\Page;
use core\Controllers\ApiController;
use core\Controllers\PageController;
use core\Models\Cart;

$success = true;

$cfg = include 'config.php';

$reg = Registry::getInstance();
$db = DBMySQL::getInstance();
$request = Request::createFromGlobals();

$url = PageController::parseURL($request);

if ($request->attributes->has('page') && ($request->attributes->get('page') < 1)) {
  \ErrorHandler::error_404_not_found();
}

$storage = new NativeSessionStorage(array(), new NativeFileSessionHandler());
$session = new Session($storage);
$session->start();
if (!$request->hasSession()) {
  $request->setSession($session);
}

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../public_html/templates');
$twig = new Twig_Environment($loader, ['cache' => __DIR__ . '/cache', 'optimizations' => 1, 'auto_reload' => true, 'debug' => false]);
$twig->addGlobal('mode', $cfg['mode']);

// Корзина (глобально)
$cart = Cart::getInstance();
if ($cart) {
  $twig->addGlobal('cart', [
    'total' => $cart->total(),
    'sum' => $cart->sum()
  ]);
}


if (!ApiController::route($request)) {

  if ($request->getMethod() == 'GET') {

    if ($page = Page::route($url)) {

      if ($handler = $page->handler()) {

        $controller = $handler->controller;
        $method = $handler->method;

        try {
          $controller::$method($request, $page, $handler->template);
        } catch (Exception $exception) {
          $success = false;
        }

      } else {
        $success = false;
      }

    } else {
      $success = false;
    }

  } else {
    $success = false;
  }
}

if (!$success) {
  \ErrorHandler::error_404_not_found();
}
