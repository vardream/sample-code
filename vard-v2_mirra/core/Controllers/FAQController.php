<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 06.01.2018
 * Time: 16:48
 */

namespace core\Controllers;


use core\Models\Article;
use core\Models\FAQ;
use core\Models\Handler;
use core\Models\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FAQController
{
  public static function index(Request $request, Page $page, $template)
  {
    $items_per_page = 15;

    $twig = $GLOBALS['twig'];

    $data = [
      'assets' => null,
      'meta_title' => $page->meta_title,
      'meta_keywords' => $page->meta_keywords,
      'meta_description' => $page->meta_description,
      'current' => PageController::parseURL($request),
      'chain' => null,
      'sorted' => null,
      'pagination' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_news' => null,
      'menu_blog' => null,
      'menu_faq' => null,
      'content' => null,
      'questions' => null
    ];

    $data['assets'] = PageController::assets();
    $data['chain'] = MenuController::getChain($page);
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_blog'] = MenuController::getMenuBlogItems();

    $data['menu_faq'] = MenuController::getMenuPageItems('menu_faq');

    $data['sorted'] = [
      'current' => '',
      'value' => ''
    ];

    if ($request->query->has('sort')) {
      $value = $request->query->get('sort');
      switch ($value) {
        case 'headline':
        case 'price':
          $data['sorted'] = [
            'current' => $value,
            'value' => '?sort=' . $value
          ];
          break;
      }
    }

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

    if ($handler = Handler::findName('index_faq')) {

      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      $total_items = $page->childrenContentRecursiveCount('core\Models\FAQ', $handler->id, true);

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = FAQ::ORDER_BY_DATE;

        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = FAQ::ORDER_BY_QUESTION;
              break;
          }
        }

        $faq = $page->childrenContentRecursive('core\Models\FAQ', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);

        if (!empty($faq)) {

          $data['questions'] = [
            'items' => []
          ];

          /** @var FAQ $item */
          foreach ($faq as $item) {

            $data['questions']['items'][] = [
              'question' => $item->question,
              'answer' => $item->answer
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
//    $response->headers->addCacheControlDirective('max-age=300');
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

}