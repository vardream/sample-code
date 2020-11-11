<?php
/**
 * Created by PhpStorm.
 * User: vard
 * Date: 24.06.2018
 * Time: 14:21
 */

namespace core\Controllers;


use core\Models\Article;
use core\Models\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController
{
  public static function show(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $data = [
      'assets' => null,
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => $request->getPathInfo(),
      'chain' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'menu_recipes' => null,
      'content' => null
    ];

    $data['assets'] = PageController::assets();
    $data['chain'] = MenuController::getChain($page);
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['menu_recipes'] = MenuController::getMenuPageItems('menu_recipes');

    /** @var Article $content */
    if ($content = $page->content()) {
      $data['content'] = [
        'title' => $content->title,
        'body' => $content->body
      ];
    }

    $content = $twig->render($template, $data);
    $response = new Response($content, Response::HTTP_OK);
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }
}