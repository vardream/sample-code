<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15.10.2017
 * Time: 12:15
 */

namespace core\Controllers;

use core\Models\Menu;
use core\Models\MenuItem;
use core\Models\Article;
use core\Models\News;
use core\Models\Page;
use core\Models\Post;
use core\Models\Product;
use core\Models\Tag;
use core\Models\Handler;
use Symfony\Component\HttpFoundation\Request;

class MenuController
{
  /**
   * @param $slug
   * @return array|null
   */
  public static function getMenuPageItems($slug)
  {
    $data = null;

    if ($menu = Menu::findName($slug)) {
      $menu_items = $menu->children();

      if (!empty($menu_items)) {
        $data = [
          'title' => $menu->title,
          'items' => []
        ];

        /** @var MenuItem $menu_item */
        foreach ($menu_items as $menu_item) {
          if ($item_page = $menu_item->page()) {
            $data['items'][] = [
              'url' => $item_page->path(),
              'title' => $item_page->menu_title
            ];
          }
        }
      }
    }

    return $data;
  }

  /**
   * Возвращает массив с данными статей для меню блога
   *
   * @return array|null
   */
  public static function getMenuBlogItems()
  {
    $data = null;

    $items_in_menu = 3;

    $url = '/blog';

    if ($page = Page::route($url)) {

      if ($handler = Handler::findName('show_blog')) {

        $posts = $page->childrenContent('core\Models\Post', $handler->id, true, Post::ORDER_BY_ID, 0, $items_in_menu);

        if (!empty($posts)) {

          $data = [
            'title' => $page->menu_title,
            'items' => []
          ];

          /** @var Post $item */
          foreach ($posts as $item) {
            if ($item_page = $item->page()) {
              $title = $item_page->menu_title;
              if (is_null($title)) {
                $title = $item->title;
              }
              $image = $item->image;
              $image2x = null;
              if (!is_null($image) && ImageController::existsImage($image)) {
                $image2x = ImageController::image2x($image);
              } else {
                $image = null;
              }
              $url = $item_page->path();

              $data['items'][] = [
                'title' => $title,
                'image' => $image,
                'image2x' => $image2x,
                'url' => $url
              ];

            }
          }
        }
      }
    }

    return $data;
  }

  /**
   * Возвращает массив элеменов меню "Новости"
   *
   * @return array|null
   */
  public static function getMenuNewsItems()
  {
    $data = null;

    $items_in_menu = 5;

    $url = '/news';

    if ($page = Page::route($url)) {

      if ($handler = Handler::findName('show_news')) {

        $news = $page->childrenContent('core\Models\News', $handler->id, true, News::ORDER_BY_DATE, 0, $items_in_menu);

        if (!empty($news)) {

          $data = [
            'title' => $page->menu_title,
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
            $title = $item->page()->menu_title;
            if (is_null($title)) {
              $title = $item->title;
            }
            $date = $item->published;

            $data['items'][] = [
              'date' => $date->format('d.m.Y'),
              'title' => $title,
              'url' => $url,
              'target' => $target
            ];

          }
        }
      }
    }

    return $data;
  }

  /**
   * @return array|null
   */
  public static function getMenuInterestingItems()
  {
    $data = null;

    $items_in_menu = 5;

    $url = '/interesting';

    if ($page = Page::route($url)) {

      if ($handler = Handler::findName('show_interesting')) {

        $articles = $page->childrenContent('core\Models\Article', $handler->id, true, Article::ORDER_BY_ID, 0, $items_in_menu);

        if (!empty($articles)) {

          $data = [
            'title' => $page->menu_title,
            'items' => []
          ];

          /** @var Article $item */
          foreach ($articles as $item) {

            $title = $item->page()->menu_title;
            if (is_null($title)) {
              $title = $item->title;
            }
            $url = $item->page()->path();

            $data['items'][] = [
              'title' => $title,
              'url' => $url
            ];

          }
        }
      }
    }

    return $data;
  }


  /**
   * @return array|null
   */
  public static function getMenuTagPurpose()
  {
    $data = null;

    if ($tag_purpose = Tag::findSlug('purpose')) {

      if ($page_products = Page::route('/products')) {

        $base_url = $page_products->path();

        $data = [
          'title' => $tag_purpose->title,
          'items' => []
        ];

        $tag_purpose_children = $tag_purpose->children();

        /** @var Tag $tag */
        foreach ($tag_purpose_children as $tag) {
          $data['items'][] = [
            'url' => $base_url . '/tag/' . $tag->slug,
            'title' => $tag->title
          ];
        }
      }
    }

    return $data;
  }

  /**
   * @return array|null
   */
  public static function getMenuTagLeaders()
  {
    $data = null;

    if ($tag_leaders = Tag::findSlug('leaders')) {
      $tag_leaders_products = $tag_leaders->content('core\Models\Product');

      if (count($tag_leaders_products) > 0) {

        $data = [
          'title' => $tag_leaders->title,
          'items' => []
        ];

        /** @var Product $product */
        foreach ($tag_leaders_products as $product) {
          $image = '/images/products/' . $product->nomenclature . '.png';

          if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
            $image = null;
          }

          $old_price = $product->price;
          $price = $product->discount();
          if ($price == 0) {
            $price = $old_price;
            $old_price = null;
          }

          $data['items'][] = [
            'id' => $product->id,
            'url' => $product->page()->path(),
            'title' => $product->title,
            'image' => $image,
            'old_price' => $old_price,
            'price' => $price
          ];
        }
      }
    }

    return $data;
  }

  /**
   * @param Product $product
   * @return array|null
   */
  public static function getProductTags(Product $product)
  {
    $data = null;

    if ($page_products = Page::route('/products')) {

      $base_url = $page_products->path();

      $product_tags = $product->tags('purpose');

      if (count($product_tags) > 0) {

        $data = [
          'items' => []
        ];

        /** @var Tag $tag */
        foreach ($product_tags as $tag) {
          $data['items'][] = [
            'url' => $base_url . '/tag/' . $tag->slug,
            'title' => $tag->title
          ];
        }
      }
    }

    return $data;
  }

  /**
   * @return array|null
   */
  public static function getMenuLinesOnHome()
  {
    $data = null;

    if ($menu = Menu::findName('menu_lines')) {
      if ($menu_items = $menu->children()) {

        $data = [
          'title' => $menu->title,
          'items' => []
        ];

        /** @var MenuItem $menu_item */
        foreach ($menu_items as $menu_item) {

          $title = $menu_item->title;

          if ($image = $menu_item->image) {
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
              $image = null;
            }
          }

          if (!is_null($title) && !is_null($image) && ($item_page = $menu_item->page())) {
            $data['items'][] = [
              'url' => $item_page->path(),
              'title' => $title,
              'image' => $image,
            ];
          }
        }
      }
    }

    return $data;
  }

  /**
   * @param Page $page
   * @return array|null
   */
  public static function getChain(Page $page)
  {
    $data = null;

    if ($page_path = $page->path()) {

      if ($page_path != '/') {

        $data = [
          'items' => []
        ];

        $chunks = [];

        $current = $page->parent();

        while ($current) {
          $chunks[] = [
            'url' => $current->path(),
            'title' => $current->menu_title
          ];
          $current = $current->parent();
        }

        $n = count($chunks);

        for ($i = $n; $i > 0; $i--) {
          $data['items'][] = $chunks[$i - 1];
        }
      }

    }

    return $data;
  }

  public static function productsPagination(Request $request, $total, $interval = 4)
  {
    $data = null;

    $url = PageController::parseURL($request);
    $current = 1;
    if ($request->attributes->has('page')) {
      $current = $request->attributes->get('page');
    }

    if ($current + $interval > $total) {
      $begin = $total;
      if ($total - $interval > 0) {
        $end = $total - $interval;
      } else {
        $end = 1;
      }
    } else {
      if ($current > 1) {
        $begin = $current + $interval - 1;
        $end = $current - 1;
      } else {
        $begin = $current + $interval;
        $end = $current;
      }
    }

    if ($total > 1) {


      $link = $url;

      if ($request->attributes->has('tag')) {
        $link .= "/tag/" . $request->attributes->get('tag');
      }

      $data = [
        'current' => $current > 1 ? $link . '/page-' . $current : $link,
        'items' => []
      ];

      for ($i = $end; $i <= $begin; $i++) {

        $data['items'][] = [
          'url' => $i > 1 ? $link . '/page-' . $i : $link,
          'title' => $i,
        ];

      }
    }

    return $data;
  }
}