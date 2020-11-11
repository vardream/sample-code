<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 01.10.2017
 * Time: 1:59
 */

namespace core\Controllers;

use core\Models\Article;
use core\Models\Menu;
use core\Models\MenuItem;
use core\Models\Page;
use core\Models\Product;
use core\Models\Tag;
use core\Models\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController
 *
 * @package core\Controllers
 */
class PageController
{

  /**
   * @param Request $request
   * @param Page    $page
   * @param         $template
   */
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

    $data['assets'] = self::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['menu_recipes'] = MenuController::getMenuPageItems('menu_recipes');
    $data['chain'] = MenuController::getChain($page);

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

  public static function about(Request $request, Page $page, $template)
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
      'menu_about' => null,
      'content' => null
    ];

    $data['assets'] = self::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['menu_about'] = MenuController::getMenuPageItems('menu_about');
    $data['chain'] = MenuController::getChain($page);

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


  /**
   * @param Request $request
   * @param Page    $page
   * @param         $template
   */
  public static function home(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $data = [
      'assets' => null,
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => $request->getPathInfo(),
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'tags_leaders' => null,
      'content' => null
    ];

    $data['assets'] = self::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();
    $data['tags_leaders'] = MenuController::getMenuTagLeaders();

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

  public static function interesting(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $items_per_page = 10;

    $data = [
      'assets' => null,
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => self::parseURL($request),
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_interesting' => null,
      'content' => null,
      'interesting' => null,
      'sorted' => null
    ];

    $data['assets'] = self::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();

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

    if ($handler = Handler::findName('show_interesting')) {
      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      $total_items = $page->childrenContentCount('core\Models\Article', $handler->id, true);

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = Article::ORDER_BY_ID;
        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = Article::ORDER_BY_TITLE;
              break;
          }
        }

        $articles = $page->childrenContent('core\Models\Article', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);

        if (!empty($articles)) {

          $data['interesting'] = [
            'items' => []
          ];

          /** @var Article $item */
          foreach ($articles as $item) {

            $url = $item->page()->path();

            $data['interesting']['items'][] = [
              'url' => $url,
              'title' => $item->title,
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

  public static function advices(Request $request, Page $page, $template)
  {
    $twig = $GLOBALS['twig'];

    $items_per_page = 10;

    $data = [
      'assets' => null,
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => self::parseURL($request),
      'chain' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_recipes' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'content' => null,
      'advices' => null,
      'sorted' => null
    ];

    $data['assets'] = self::assets();
    $data['chain'] = MenuController::getChain($page);
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_recipes'] = MenuController::getMenuPageItems('menu_recipes');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();

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

    if ($handler = Handler::findName('show_advices')) {
      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      $total_items = $page->childrenContentRecursiveCount('core\Models\Article', $handler->id, true);

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = Article::ORDER_BY_ID;
        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = Article::ORDER_BY_TITLE;
              break;
          }
        }

        $articles = $page->childrenContentRecursive('core\Models\Article', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);

        if (!empty($articles)) {

          $data['advices'] = [
            'items' => []
          ];

          /** @var Article $item */
          foreach ($articles as $item) {

            $url = $item->page()->path();

            $data['advices']['items'][] = [
              'url' => $url,
              'title' => $item->title,
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
    $response->headers->addCacheControlDirective('must-revalidate');
    $response->headers->addCacheControlDirective('proxy-revalidate');
    $response->headers->addCacheControlDirective('public');
    $response->headers->addCacheControlDirective('max-age=600');
    $response->prepare($request);
    $response->send();

  }

  /**
   * @param Request $request
   * @param Page    $page
   * @param         $template
   */
  public static function professional(Request $request, Page $page, $template)
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
      'menu_lines' => null,
      'tags_purpose' => null,
      'content' => null
    ];

    $data['assets'] = self::assets();
    $data['chain'] = MenuController::getChain($page);
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_lines'] = MenuController::getMenuPageItems('menu_lines');
    $data['tags_purpose'] = MenuController::getMenuTagPurpose();

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

  public static function assets()
  {
    $data = null;

    $filename = __DIR__ . '/../../../public_html/js/assets.json';
    if (file_exists($filename)) {
      $json_assets = file_get_contents($filename);
      try {
        $assets = json_decode($json_assets, true);
        $data = [
          'js' => [],
          'css' => [],
        ];
        foreach ($assets as $items => $values) {
          foreach ($values as $key => $value) {
            if (!is_array($value)) {
              if (preg_match('/^common/i', $items)) {
                array_unshift($data[$key], $value);
              } else {
                if ($key == 'css' || $key == 'js') {
                  array_push($data[$key], $value);
                }
              }
            } else {
              if ($key == 'css') {
                foreach ($value as $value_item) {
                  array_push($data[$key], $value_item);
                }
              }
            }
          }
        }
      } catch (\Exception $exception) {
      }
    }

    return $data;
  }

  /**
   * @param Request $request
   * @return string
   */
  public static function parseURL(Request $request)
  {
    $url = $request->getPathInfo();

    if (preg_match('%/advices/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/blog/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/news/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/questions/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/professional/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/products/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
      if (preg_match('%(/tag)(/([^/]+))$%sim', $url, $regs)) {
        $request->attributes->set('tag', $regs[3]);
        $url = preg_replace('%(/tag)(/([^/]+))$%sim', '', $url);
      }
    } elseif (preg_match('%/g-synergie/.+$%sim', $url)) {
      if (preg_match('%(/page-([\d]+))$%sim', $url, $regs)) {
        $request->attributes->set('page', intval($regs[2]));
        $url = preg_replace('%(/page-([\d]+))$%sim', '', $url);
      }
    }

    return $url;
  }
}