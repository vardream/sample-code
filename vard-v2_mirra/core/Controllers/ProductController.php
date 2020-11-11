<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12.10.2017
 * Time: 19:48
 */

namespace core\Controllers;

use core\Models\Discount;
use core\Models\Handler;
use core\Models\ProductDiscount;
use core\Models\Video;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use core\Models\Article;
use core\Models\Page;
use core\Models\Product;
use core\Models\Tag;

/**
 * Class ProductController
 *
 * @package core\Controllers
 */
class ProductController
{
  /**
   * Вывод списка товаров на странице
   *
   * @param Request $request
   * @param Page    $page
   * @param string  $template
   */
  public static function index(Request $request, Page $page, $template)
  {
    $items_per_page = 5;

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
      'menu_lines' => null,
      'tags_purpose' => null,
      'tags_leaders' => null,
      'content' => null,
      'products' => null,
      'chapters' => null
    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_lines'] = MenuController::getMenuPageItems('menu_lines');
    $data['tags_purpose'] = MenuController::getMenuTagPurpose();
    $data['tags_leaders'] = MenuController::getMenuTagLeaders();
    $data['chain'] = MenuController::getChain($page);

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

    $tag = null;
    if ($request->attributes->has('tag')) {
      $tag = Tag::findSlug($request->attributes->get('tag'));
      $data['current'] = $request->getPathInfo();
      if (preg_match('%(/page-([\d]+))$%sim', $data['current'], $regs)) {
        $data['current'] = preg_replace('%(/page-([\d]+))$%sim', '', $data['current']);
      }
      if (is_null($tag)) {
        \ErrorHandler::error_404_not_found();
      }
    }

    if (is_null($tag)) {
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

      if ($parent = $page->parent()) {
        if (!is_null($parent->handler_id) && ($parent->handler_id == $page->handler_id)) {
          $chapters = $page->children(true, ['handler_id' => $page->handler_id]);
          if (!empty($chapters)) {
            $data['chapters'] = [
              'items' => []
            ];
            /** @var Page $chapter */
            foreach ($chapters as $chapter) {
              $data['chapters']['items'][] = [
                'title' => $chapter->menu_title,
                'url' => $chapter->path()
              ];
            }
          }
        }
      }
    } else {
      $data['content'] = [
        'title' => $tag->title
      ];
    }

    if ($handler = Handler::findName('show_product')) {

      $page_number = 1;
      if ($request->attributes->has('page')) {
        $page_number = $request->attributes->get('page');
      }

      if (is_null($tag)) {
        $total_items = $page->childrenContentRecursiveCount('core\Models\Product', $handler->id, true);
      } else {
        $total_items = $tag->contentCount('core\Models\Product');
      }

      $total_pages = ceil($total_items / $items_per_page);

      if ($total_items > 0 && $page_number <= $total_pages) {

        $data['pagination'] = MenuController::productsPagination($request, $total_pages);

        $order = Product::ORDER_BY_NOMENCLATURE;
        if ($request->query->has('sort')) {
          switch ($request->query->get('sort')) {
            case 'headline' :
              $order = Product::ORDER_BY_TITLE;
              break;
            case 'price' :
              $order = Product::ORDER_BY_PRICE;
              break;
          }
        }

        if (is_null($tag)) {
          $products = $page->childrenContentRecursive('core\Models\Product', $handler->id, true, $order, ($page_number - 1) * $items_per_page, $items_per_page);
        } else {
          $products = $tag->content('core\Models\Product', true, $order, ($page_number - 1) * $items_per_page, $items_per_page);
        }

        if (count($products) > 0) {

          $data['products'] = [
            'items' => []
          ];
        }

        /** @var Product $product */
        foreach ($products as $product) {

          $old_price = $product->price;
          $price = $product->discount();
          if ($price == 0) {
            $price = $old_price;
            $old_price = null;
          }

          $data['products']['items'][] = [
            'id' => $product->id,
            'url' => $product->page()->path(),
            'title' => $product->title,
            'nomenclature' => $product->nomenclature,
            'is_available' => $product->is_available,
            'notice' => $product->notice,
            'old_price' => $old_price,
            'price' => $price
          ];
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

  /**
   * Вывод страницы с товаром
   *
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
      'product_tags' => null,
      'menu_chapters' => null,
      'menu_sidebar' => null,
      'menu_lines' => null,
      'tags_purpose' => null,
      'tags_leaders' => null,
      'content' => null,
      'videos' => null
    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_lines'] = MenuController::getMenuPageItems('menu_lines');
    $data['tags_purpose'] = MenuController::getMenuTagPurpose();
    $data['tags_leaders'] = MenuController::getMenuTagLeaders();
    $data['chain'] = MenuController::getChain($page);

    /** @var Product $content */
    if ($content = $page->content()) {

      $data['product_tags'] = MenuController::getProductTags($content);

      $image = '/images/products/' . $content->nomenclature . '.png';

      if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
        $image = null;
      }

      $quantity = $content->quantity;

      $volume = '';

      if ($unit = $content->unit()) {
        $unit_title = $unit->title;
        $volume .= !is_null($unit_title) ? $quantity . ' ' . $unit_title : '';
      }

      if ($package = $content->package()) {
        $package_title = $package->title;
        $volume = !is_null($package_title) ? $package_title . ' ' . $volume : '';
      }

      $old_price = $content->price;
      $price = $content->discount();
      if ($price == 0) {
        $price = $old_price;
        $old_price = null;
      }

      $data['content'] = [
        'id' => $content->id,
        'title' => $content->title,
        'body' => $content->body,
        'nomenclature' => $content->nomenclature,
        'is_available' => $content->is_available,
        'image' => $image,
        'volume' => $volume,
        'old_price' => $old_price,
        'price' => $price
      ];

      $videos = $content->videos();
      if (!empty($videos)) {

        $data['videos'] = [];

        /** @var Video $video */
        foreach ($videos as $video) {

          $image = $video->image;
          $image_2x = null;

          if (!is_null($image)
            && !ImageController::existsImage($image)
          ) {
            $image = null;
          }

          if (!is_null($image)) {
            $image_2x = ImageController::image2x($image);
          }

          if (!is_null($image_2x)
            && !ImageController::existsImage($image_2x)
          ) {
            $image_2x = null;
          }

          $data['videos'][] = [
            'title' => $video->title,
            'url' => $video->link,
            'image' => $image,
            'image_2x' => $image_2x,
          ];
        }
      }

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
  public static function seasonal(Request $request, Page $page, $template)
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
      'menu_interesting' => null,
      'menu_news' => null,
      'content' => null,
      'products_discounts' => null
    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['chain'] = MenuController::getChain($page);
    $data['menu_interesting'] = MenuController::getMenuInterestingItems();
    $data['menu_news'] = MenuController::getMenuNewsItems();

    /** @var Article $content */
    if ($content = $page->content()) {
      $data['content'] = [
        'title' => $content->title,
        'body' => $content->body
      ];
    }

    $discount_type = 'seasonal';
    $discounts = Discount::selectDiscountsWithType($discount_type, true);
    $discounts_number = count($discounts);
    if ($discounts_number > 0) {
      $data['products_discounts'] = [];
      $discounts_products = [];

      /** @var Discount $discount */
      foreach ($discounts as $discount) {
        $data['products_discounts'][] = [
          'title' => $discount->title,
          'items' => []
        ];
        $discounts_products[] = $discount->products();
      }

      $discounts_number--;

      for ($i = $discounts_number; $i >= 0; $i--) {
        /** @var Product $product */
        foreach ($discounts_products[$i] as $product) {

          if (($page = $product->page()) && $page->published) {
            $data['products_discounts'][$i]['items'][] = [
              'title' => $product->title,
              'nomenclature' => $product->nomenclature,
              'is_available' => $product->is_available,
              'url' => $product->page()->path(),
              'old_price' => $product->price,
              'price' => $product->discount()
            ];
          }
        }
      }
      unset($discounts_products);
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
   * @param Page $page
   * @param $template
   */
  public static function checkout(Request $request, Page $page, $template)
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
      'menu_lines' => null,
      'tags_purpose' => null,
      'tags_leaders' => null,
      'content' => null,
      'products_discounts' => null
    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_lines'] = MenuController::getMenuPageItems('menu_lines');
    $data['tags_purpose'] = MenuController::getMenuTagPurpose();
    $data['tags_leaders'] = MenuController::getMenuTagLeaders();
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
}