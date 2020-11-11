<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22.12.2017
 * Time: 14:58
 */

namespace core\Controllers;

use core\Models\News;
use core\Models\Article;
use core\Models\Page;
use core\Models\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Node\Expression\NameExpression;

/**
 * Class NewsController
 *
 * @package core\Controllers
 */
class NewsController
{

  public static function index(Request $request, Page $page, $template)
  {
    $items_per_page = 10;

    $twig = $GLOBALS['twig'];

    $data = [
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => PageController::parseURL($request),
      'assets' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'chain' => null,
      'sorted' => null,
      'news' => null,

    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['chain'] = MenuController::getChain($page);

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

    if ($handler = Handler::findName('show_news')) {
      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      $total_items = $page->childrenContentCount('core\Models\News', $handler->id, true);

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = News::ORDER_BY_DATE;
        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = News::ORDER_BY_TITLE;
              break;
          }
        }

        $news = $page->childrenContent('core\Models\News', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);

        if (!empty($news)) {

          $data['news'] = [
            'items' => []
          ];

          /** @var News $item */
          foreach ($news as $item) {

            $type = $item->type;
            if ($type == News::TYPE_TEXT) {
              $url = $item->page()->path();
            } else {
              $url = $item->link;
            }
            $target = '_self';
            if ($type == News::TYPE_EXTERNAL) {
              $target = '_blank';
            }

            $date = $item->published;

            $data['news']['items'][] = [
              'date' => $date->format('d.m.Y'),
              'url' => $url,
              'title' => $item->title,
              'notice' => $item->notice,
              'target' => $target
            ];
          }

        }

      } else {
        \ErrorHandler::error_404_not_found();
      }

    }


    $content = $twig->render($template, $data);
    $response = new Response($content, Response::HTTP_OK);
    $response->headers->addCacheControlDirective('must-revalidate');
    $response->headers->addCacheControlDirective('proxy-revalidate');
    $response->headers->addCacheControlDirective('public');
    $response->headers->addCacheControlDirective('max-age=600');
//    $response->headers->addCacheControlDirective('no-store');
//    $response->headers->addCacheControlDirective('no-cache');
//    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();

  }

  public static function show(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $data = [
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => $request->getPathInfo(),
      'assets' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'chain' => null,

    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['chain'] = MenuController::getChain($page);

    /** @var News $content */
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

    $content = $twig->render($template, $data);
    $response = new Response($content, Response::HTTP_OK);
    $response->headers->addCacheControlDirective('must-revalidate');
    $response->headers->addCacheControlDirective('proxy-revalidate');
    $response->headers->addCacheControlDirective('public');
    $response->headers->addCacheControlDirective('max-age=600');
//    $response->headers->addCacheControlDirective('no-store');
//    $response->headers->addCacheControlDirective('no-cache');
//    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }
}