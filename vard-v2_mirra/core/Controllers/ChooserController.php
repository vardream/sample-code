<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.01.2018
 * Time: 22:30
 */

namespace core\Controllers;


use core\Models\Page;
use core\Models\Product;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChooserController
{
  public static function show(Request $request, Page $page, $template)
  {

    $chooserAge = null;
    $chooserType = null;

    $current_uri = preg_replace('%^(/chooser/)%im', '', $request->getPathInfo());


    switch ($current_uri) {
      case "type-1-set-1":
        $chooserAge = 0;
        $chooserType = 0;
        break;
      case "type-1-set-2":
        $chooserAge = 0;
        $chooserType = 1;
        break;
      case "type-1-set-3":
        $chooserAge = 0;
        $chooserType = 2;
        break;
      case "type-1-set-4":
        $chooserAge = 0;
        $chooserType = 3;
        break;
      case "type-2-set-1":
        $chooserAge = 1;
        $chooserType = 0;
        break;
      case "type-2-set-2":
        $chooserAge = 1;
        $chooserType = 1;
        break;
      case "type-2-set-3":
        $chooserAge = 1;
        $chooserType = 2;
        break;
      case "type-2-set-4":
        $chooserAge = 1;
        $chooserType = 3;
        break;
      case "type-3-set-1":
        $chooserAge = 2;
        $chooserType = 0;
        break;
      case "type-3-set-2":
        $chooserAge = 2;
        $chooserType = 1;
        break;
      case "type-3-set-3":
        $chooserAge = 2;
        $chooserType = 2;
        break;
      case "type-3-set-4":
        $chooserAge = 2;
        $chooserType = 3;
        break;
    }

    if (is_null($chooserType) || is_null($chooserAge)) {
      \ErrorHandler::error_404_not_found();
    }

    // Продукция
    $aProducts = [
      // Возраст
      0 => [
        // Тип кожи
        0 => [
          // Разделы

          // Продукты
          0 => ["3002"],
          1 => [],
          2 => ["3061"],
          3 => ["3018"],
          4 => ["3021"],
          5 => ["3032"],
          6 => ["3073"],
          7 => ["3364"],
          8 => ["3101", "3212", "3167"],

        ],

        // Тип кожи
        1 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3063"],
          2 => ["3221"],
          3 => ["3368"],
          4 => ["3031"],
          5 => ["3032"],
          6 => ["3073"],
          7 => ["3164"],
          8 => ["3104", "3238", "3097"],

        ],

        // Тип кожи
        2 => [
          // Разделы

          // Продукты
          0 => ["3353"],
          1 => ["3063"],
          2 => ["3354"],
          3 => ["3355"],
          4 => ["3031"],
          5 => ["3032"],
          6 => ["3356"],
          7 => ["3164"],
          8 => ["3104", "3238", "3357"],

        ],

        // Тип кожи
        3 => [
          // Разделы

          // Продукты
          0 => ["3353"],
          1 => ["3063"],
          2 => ["3008"],
          3 => ["3355"],
          4 => ["3031"],
          5 => ["3032"],
          6 => ["3073"],
          7 => ["3170"],
          8 => ["3104", "3238", "3357", "3211"],

        ],
      ],

      // Возраст
      1 => array(
        // Тип кожи
        0 => [
          // Разделы

          // Продукты
          0 => ["3002"],
          1 => ["3061"],
          2 => ["3007"],
          3 => ["3015"],
          4 => ["3025"],
          5 => ["3033"],
          6 => ["3214"],
          7 => ["3365", "3363"],
          8 => ["3265", "3172", "3101"],

        ],

        // Тип кожи
        1 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3102"],
          2 => ["3221"],
          3 => ["3361"],
          4 => ["3362"],
          5 => ["3033"],
          6 => ["3242"],
          7 => ["3365", "3363"],
          8 => ["3212", "3238", "3170", "3260"],

        ],

        // Тип кожи
        2 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3063"],
          2 => ["3008"],
          3 => ["3010"],
          4 => ["3169"],
          5 => ["3033"],
          6 => ["3242"],
          7 => ["3164"],
          8 => ["3101", "3167", "3211", "3238"],

        ],

        // Тип кожи
        3 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3063"],
          2 => ["3008"],
          3 => ["3010"],
          4 => ["3099"],
          5 => ["3033"],
          6 => ["3073"],
          7 => ["3170"],
          8 => ["3265", "3238", "3162", "3169"],

        ]

      ),

      // Возраст
      2 => array(
        // Тип кожи
        0 => [
          // Разделы

          // Продукты
          0 => ["3002"],
          1 => ["3061"],
          2 => ["3267"],
          3 => ["3069"],
          4 => ["3025"],
          5 => ["3365"],
          6 => ["3214"],
          7 => ["3241"],
          8 => ["3101", "3167", "3172", "3102"],

        ],

        // Тип кожи
        1 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3102"],
          2 => ["3221"],
          3 => ["3255"],
          4 => ["3256"],
          5 => ["3033"],
          6 => ["3242"],
          7 => ["3365", "3363"],
          8 => ["3265", "3238", "3097", "3172"],

        ],

        // Тип кожи
        2 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3102"],
          2 => ["3267"],
          3 => ["1005"],
          4 => ["1001"],
          5 => ["3101"],
          6 => ["3242"],
          7 => ["3239", "3091"],
          8 => ["3241", "3238", "3102"],

        ],

        // Тип кожи
        3 => [
          // Разделы

          // Продукты
          0 => ["3005"],
          1 => ["3102"],
          2 => ["3267"],
          3 => ["3361"],
          4 => ["3362"],
          5 => ["1002"],
          6 => ["3242"],
          7 => ["3239", "3091"],
          8 => ["3363", "3101", "3097"],

        ]

      )

    ];


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
      'menu_recipes' => null,
      'content' => null,
      'ages' => [
        "20-30 лет",
        "30-45 лет",
        "45 лет и более"
      ],
      'types' => [
        "Сухая",
        "Нормальная",
        "Жирная",
        "Комбинированная"
      ],
      'sections' => [
        "Очищение",
        "Глубокое очищение",
        "Тонизирование",
        "Увлажнение",
        "Питание",
        "Крем для орбитальных зон",
        "Маски",
        "Интенсивный уход",
        "Специальные средства"
      ]
    ];

    $data['assets'] = PageController::assets();
    $data['menu_chapters'] = MenuController::getMenuPageItems('menu_chapters');
    $data['menu_sidebar'] = MenuController::getMenuPageItems('menu_sidebar');
    $data['menu_interesting'] = MenuController::getMenuInterestingItems();
    $data['menu_news'] = MenuController::getMenuNewsItems();
    $data['menu_recipes'] = MenuController::getMenuPageItems('menu_recipes');
    $data['chain'] = MenuController::getChain($page);

    $data['content'] = [
      'title' => "Рекомендуемый набор косметики",
      'chooser' => "Кожа {$data['types'][$chooserType]}, возраст {$data['ages'][$chooserAge]}",
      'sections' => []
    ];

    foreach ($data['sections'] as $section_id => $section) {

      if (isset($aProducts[$chooserAge][$chooserType][$section_id]) && !empty($aProducts[$chooserAge][$chooserType][$section_id])) {

        $items = [];

        foreach ($aProducts[$chooserAge][$chooserType][$section_id] as $nomenclature) {

          $product = Product::findNomenclature($nomenclature);

          if ($product && !is_null($product->page()) && $product->page()->published) {

            $old_price = $product->price;
            $price = $product->discount();
            if ($price == 0) {
              $price = $old_price;
              $old_price = null;
            }

            $items[] = [
              'id' => $product->id,
              'url' => $product->page()->path(),
              'title' => $product->title,
              'nomenclature' => $product->nomenclature,
              'notice' => $product->notice,
              'old_price' => $old_price,
              'price' => $price
            ];

          }

        }

        if (!empty($items)) {
          $data['content']['sections'][] = [
            'title' => $section,
            'items' => $items,

          ];
        }

      }
    }

    if (empty($data['content']['sections'])) {
      \ErrorHandler::error_404_not_found();
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