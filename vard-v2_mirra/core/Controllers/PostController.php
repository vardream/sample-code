<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23.03.2018
 * Time: 16:21
 */

namespace core\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use core\Models\Page;
use core\Models\Post;
use core\Models\Article;
use core\Models\Handler;

class PostController
{

  public static function index(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $items_per_page = 10;

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
      'content' => null,
      'blog' => null
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
    } else {
      $data['content'] = [
        'title' => $page->menu_title
      ];
    }

    $data['sorted'] = [
      'current' => '',
      'value' => ''
    ];

    if ($request->query->has('sort')) {
      $value = $request->query->get('sort');
      switch ($value) {
        case 'headline':
          $data['sorted'] = [
            'current' => $value,
            'value' => '?sort=' . $value
          ];
          break;
      }
    }

    if ($handler = Handler::findName('show_blog')) {
      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      $total_items = $page->childrenContentCount('core\Models\Post', $handler->id, true);

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = Post::ORDER_BY_ID;
        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = Post::ORDER_BY_TITLE;
              break;
          }
        }

        $posts = $page->childrenContent('core\Models\Post', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);

        if (!empty($posts)) {

          $data['blog'] = [
            'items' => []
          ];

          /** @var Post $item */
          foreach ($posts as $item) {

            $url = $item->page()->path();

            $data['blog']['items'][] = [
              'url' => $url,
              'title' => $item->title,
              'image' => $item->image,
              'notice' => $item->notice
            ];
          }

        }

      } else {
        \ErrorHandler::error_404_not_found();
      }

    }

    $content = $twig->render($template, $data);
    $response = new Response($content, Response::HTTP_OK);
//    $response->headers->addCacheControlDirective('must-revalidate');
//    $response->headers->addCacheControlDirective('proxy-revalidate');
//    $response->headers->addCacheControlDirective('public');
//    $response->headers->addCacheControlDirective('max-age=600');
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();

  }

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
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_recipes'] = MenuController::getMenuPageItems('menu_recipes');

    /** @var Post $content */
    if ($content = $page->content()) {
      $data['content'] = [
        'id' => $content->id,
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