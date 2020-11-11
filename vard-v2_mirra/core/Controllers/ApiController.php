<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 02.11.2017
 * Time: 16:08
 */

namespace core\Controllers;

use core\Models\Cart;
use core\Models\Consultation;
use core\Models\Article;
use core\Models\Order;
use core\Models\Post;
use core\Models\Product;
use core\Models\Registration;
use core\Models\Subscriber;
use core\Models\Visitor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController
{
  /**
   * @static
   * @param Request $request
   * @return bool
   */
  public static function route(Request $request)
  {
    $result = false;

    $path = $request->getPathInfo();

    if (preg_match('%(/page-([\d]+))$%sim', $path, $regs)) {
      $request->attributes->set('page', intval($regs[2]));
      $path = preg_replace('%(/page-([\d]+))$%sim', '', $path);
    }

    /*
     * GET /api/cart -> action: index -> возвращает список товаров Shopping Cart | $handler: cart, $method: GET; $id: null
     *
     * GET /api/cart/{id} -> action: show -> возвращает данные товара из базы | $handler: cart, $method: GET; $id: {id}
     * POST /api/cart/{id} -> action: store -> добавляет товар в корзину | $handler: cart, $method: POST; $id: {id}
     * PUT/PATCH /api/cart/{id} -> action: update -> обновляет количество единиц товара в корзине | $handler: cart, $method: POST (_method:PUT|_method:PATCH); $id: {id}
     * DELETE /api/cart/{id} -> action: destroy -> удаляет товар в корзину | $handler: cart, $method: POST (_method: DELETE); $id: {id}
     */

    $method = $request->getMethod();
    if ($method == 'POST' && $request->request->has('_method')) {
      $_method = $request->request->get('_method');
      switch ($_method) {
        case 'PUT':
        case 'PATCH':
          $method = 'PUT';
          break;
        case 'DELETE':
          $method = $_method;
          break;
        default:
          break;
      }
    }

    $handler = null;
    $id = null;
    $suffix = null;
    if (preg_match('%/api/(.+?)/?([\d]+)?/?(create|edit)?$%sim', $path, $regs)) {
      $length = count($regs);
      $handler = $length > 1 ? str_replace('/', '_', $regs[1]) : null;
      $id = ($length > 2 && $regs[2] != '') ? $regs[2] : null;
      $suffix = ($length > 3 && $regs[3] != '') ? $regs[3] : null;
    }

    if (!is_null($handler)) {

      switch ($method) {
        case 'PUT':
          $handler .= '_update';
          break;
        case 'DELETE':
          $handler .= '_destroy';
          break;
        case 'POST':
          $handler .= '_store';
          break;
        default:
          if (is_null($id)) {
            if (is_null($suffix)) {
              $handler .= '_index';
            } elseif ($suffix == 'create') {
              $handler .= '_create';
            } elseif ($suffix == 'edit') {
              $handler .= '_edit';
            } else {
              $handler = null;
            }
          } else {
            if (is_null($suffix)) {
              $handler .= '_show';
            } elseif ($suffix == 'create') {
              $handler .= '_create';
            } elseif ($suffix == 'edit') {
              $handler .= '_edit';
            } else {
              $handler = null;
            }
          }
          break;
      }

      if (!is_null($handler)) {

        try {
          $class = new \ReflectionClass(__CLASS__);
          if ($class->hasMethod($handler)) {
            $object = $class->name;
            $object::$handler($request, $id);

            $result = true;
          }
        } catch (\Exception $exception) {

        }

      }

    }

    return $result;
  }

  /**
   * @static
   * @param Request $request
   * @param null    $id
   */
  public static function subscribe_store(Request $request, $id = null)
  {
    $result = false;
    $is_new_subscriber = false;
    $visitor = null;
    $subscriber = null;

    if (is_null($id)
      && !$request->attributes->has('page')
      && $request->request->has('subscriber_mail')
    ) {
      $email = $request->request->get('subscriber_mail');

      if (preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/sim', $email)) {

        list($name, $domain) = explode('@', $email);
        $visitor = Visitor::findEmail($email);
        if (is_null($visitor)) {
          $visitor = Visitor::create(new Visitor([
            'name' => $name,
            'email' => $email
          ]));
        }

        if (!is_null($visitor)) {

          $subscriber = $visitor->subscriber();
          $is_new_subscriber = is_null($subscriber);

          if ($is_new_subscriber) {
            $subscriber = Subscriber::create(new Subscriber([
              'visitor_id' => $visitor->id,
              'mail_sent' => false,
              'ip' => $request->getClientIp(),
              'browser' => null,
            ]));
          }

          $result = true;
        }
      }

      $twig = $GLOBALS['twig'];

      if (!is_null($visitor)
        && !is_null($subscriber)
        && $result
      ) {

        if ($is_new_subscriber) {
          $subject = "Мирра. Подписка на рассылку.";
          $plain_message = "E-mail для получения рассылки: " . $visitor->email;
          $mail_sent = MailController::sendVisitorsMessageToAdmin(
            [
              'address' => $visitor->email,
              'name' => $visitor->name
            ],
            $subject,
            $plain_message
          );
          if ($mail_sent) {
            $subscriber->mail_sent = 1;
            $subscriber->save();
          }
        }

        $template = "subscribe_success.twig";
        $content = $twig->render($template, ['email' => $email]);
        $response = new Response($content, Response::HTTP_OK);
      } else {

        /** Ответ при ошибке */
        $response = new Response('', Response::HTTP_NOT_FOUND);
      }

      $response->headers->addCacheControlDirective('no-store');
      $response->headers->addCacheControlDirective('no-cache');
      $response->headers->addCacheControlDirective('private');
      $response->prepare($request);
      $response->send();
    }
  }

  /**
   * @static
   * @param Request  $request
   * @param int|null $id
   * @throws \Exception
   */
  public static function registration_store(Request $request, $id = null)
  {
    $result = false;
    $visitor = null;
    $registration = null;

    if (is_null($id)
      && !$request->attributes->has('page')
      && $request->request->has('person')
      && $request->request->has('phone')
      && $request->request->has('email')
      && $request->request->has('location')
    ) {
      $result = true;
    }

    if ($result) {
      $person = trim($request->request->get('person'));
      $email = trim($request->request->get('email'));
      $phone = trim($request->request->get('phone'));
      $location = trim($request->request->get('location'));

      // Замена пробельных символов на символ пробела
      $person = preg_replace('/\s+/im', ' ', $person);
      $result = (bool)preg_match('/^([\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}]+([\-\x{0022}\x{0027}\x{0060}\x{2019}]?[\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}])+\s*)+$/imu', $person);

      if ($result) {
        // Удаление пробельных символов
        $email = preg_replace('/\s+/im', '', $email);
        $result = (bool)preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/sim', $email);
      }

      if ($result) {
        # Successful match
        $visitor = Visitor::findEmail($email);
        if (is_null($visitor)) {
          $visitor = Visitor::create(new Visitor([
            'name' => $person,
            'email' => $email
          ]));
        } else {
          if ($visitor->name == $visitor->email) {
            $visitor->name = $person;
            $visitor->save();
          }
        }

        if (!is_null($visitor)) {

          if ($phone != '') {
            $phone = preg_replace('/\s{2,}/im', ' ', $phone);
            $result = (bool)preg_match('/^\+?\d+[\s\-.]*(\(\d+\)|\d+)[\s\-.]*(\d+[\s\-.]*)+$/sim', $phone);
          } else {
            $phone = null;
          }

          if ($location != '') {
            // Замена пробельных символов на символ пробела
            $location = preg_replace('/\s+/im', ' ', $location);
            // Унификация апострофов
            $location = preg_replace('/(\S)([\x{0022}\x{0027}\x{0060}]+)(\S)/imu', '$1\'$3', $location);
            // Удаление пробелов перед знаками препинания
            $location = preg_replace('/[ ]([.,;:!?…]+)/imu', '$1', $location);
            // Расстановка троеточий
            $location = preg_replace('/(\.{2,})/im', '…', $location);
            // Добавление отсутствующих пробелов после знаков препинания
            $location = preg_replace('/([.,;:!?…]+)(\S)/imu', '$1 $2', $location);

          } else {
            $location = null;
          }

          if ($result) {
            $registration = $visitor->registration();

            if (is_null($registration)) {

              $registration = Registration::create(new Registration([
                'visitor_id' => $visitor->id,
                'phone' => $phone,
                'location' => $location,
                'mail_sent' => false,
                'ip' => $request->getClientIp(),
                'browser' => null,
              ]));
            }

            $result = true;
          }
        } else {

          $result = false;
        }
      }

      if (!is_null($visitor)
        && !is_null($registration)
        && $result
      ) {

        $mail_data = [
          'date' => (new \DateTime())->format('d-m-Y H:i'),
          'person' => $person,
          'phone' => $registration->phone,
          'email' => $visitor->email,
          'location' => $registration->location
        ];

        if ($registration->mail_sent) {
          $subject = "Мирра. Регистрация дистрибьютора. Заявка N" . $visitor->id . ". (Повторно)";
        } else {
          $subject = "Мирра. Регистрация дистрибьютора. Заявка N" . $visitor->id;
        }

        $plain_message = "РЕГИСТРАЦИЯ ДИСТРИБЬЮТОРА\n\n";
        $plain_message .= "Имя: " . $mail_data['person'] . "\n";
        $plain_message .= "E-mail: " . $mail_data['email'] . "\n";

        if (!is_null($mail_data['phone'])) {
          $plain_message .= "Тел.: " . $mail_data['phone'] . "\n";
        }
        if (!is_null($mail_data['location'])) {
          $plain_message .= "Местонахождение.: " . $mail_data['location'] . "\n\n";
        }
        $plain_message .= "\nДата: " . $mail_data['date'] . "\n";

        $mail_sent = MailController::sendVisitorsMessageToAdmin(
          [
            'address' => $mail_data['email'],
            'name' => $mail_data['person']
          ],
          $subject,
          $plain_message
        );

        if ($mail_sent) {
          $registration->mail_sent = 1;
          $registration->save();
        }

        $response = new Response('', Response::HTTP_OK);

      } else {

        /** Ответ при ошибке */
        $response = new Response('', Response::HTTP_NOT_FOUND);
      }

      $response->headers->addCacheControlDirective('no-store');
      $response->headers->addCacheControlDirective('no-cache');
      $response->headers->addCacheControlDirective('private');
      $response->prepare($request);
      $response->send();
    }
  }

  /**
   * @static
   * @param Request $request
   * @param int|null    $id
   * @throws \Exception
   */
  public static function question_store(Request $request, $id = null)
  {
    $result = false;
    $visitor = null;
    $consultation = null;

    if (is_null($id)
      && !$request->attributes->has('page')
      && $request->request->has('person')
      && $request->request->has('subject')
      && $request->request->has('email')
      && $request->request->has('message')
    ) {
      $result = true;
    }

    if ($result) {
      $person = trim($request->request->get('person'));
      $email = trim($request->request->get('email'));
      $subject = trim($request->request->get('subject'));
      $message = trim($request->request->get('message'));

      // Замена пробельных символов на символ пробела
      $person = preg_replace('/\s+/im', ' ', $person);
      $result = (bool)preg_match('/^([\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}]+([\-\x{0022}\x{0027}\x{0060}\x{2019}]?[\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}])+\s*)+$/imu', $person);

      if ($result) {
        // Удаление пробельных символов
        $email = preg_replace('/\s+/im', '', $email);
        $result = (bool)preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/sim', $email);

        if ($subject != '') {
          // Замена пробельных символов на символ пробела
          $subject = preg_replace('/\s+/im', ' ', $subject);

          if ($message != '') {
            // Замена символов табуляции на пробелы
            $message = preg_replace('/\t+/im', ' ', $message);
            // Удаление повторяющихся пробельных символов
            $message = preg_replace('/(\s)\s+/im', '$1', $message);
            // Унификация апострофов
            $message = preg_replace('/(\S)([\x{0022}\x{0027}\x{0060}]+)(\S)/imu', '$1\'$3', $message);
            // Удаление пробелов перед знаками препинания
            $message = preg_replace('/[ ]([.,;:!?…]+)/imu', '$1', $message);
            // Расстановка троеточий
            $message = preg_replace('/(\.{2,})/im', '…', $message);
            // Добавление отсутствующих пробелов после знаков препинания
            $message = preg_replace('/([.,;:!?…]+)(\S)/imu', '$1 $2', $message);

          } else {
            $result = false;
          }

        } else {
          $result = false;
        }

      }

      if ($result) {

        $visitor = Visitor::findEmail($email);
        if (is_null($visitor)) {
          $visitor = Visitor::create(new Visitor([
            'name' => $person,
            'email' => $email
          ]));
        } else {
          if ($visitor->name == $visitor->email) {
            $visitor->name = $person;
            $visitor->save();
          }
        }

        if ($visitor) {

          if ($result) {

            $consultation = Consultation::create(new Consultation([
              'visitor_id' => $visitor->id,
              'subject' => $subject,
              'message' => $message,
              'mail_sent' => 0,
              'ip' => $request->getClientIp(),

            ]));

            $result = !is_null($consultation);
          }
        } else {
          $result = false;
        }
      }

      if (!is_null($visitor)
        && !is_null($consultation)
        && $result
      ) {

        $mail_data = [
          'date' => (new \DateTime())->format('d-m-Y H:i'),
          'person' => $person,
          'email' => $visitor->email,
          'subject' => $subject,
          'message' => $message,

        ];

        $subject = "Мирра. Вопрос посетителя сайта N" . $consultation->id;

        $plain_message = "ВОПРОС ПОСЕТИТЕЛЯ\n\n";
        $plain_message .= "Посетитель: " . $mail_data['person'] . "\n";
        $plain_message .= "E-mail: " . $mail_data['email'] . "\n\n";
        $plain_message .= "Тема: " . $mail_data['subject'] . "\n";
        $plain_message .= "Вопрос:\n";
        $plain_message .= $mail_data['message'] . "\n\n";
        $plain_message .= "Дата: " . $mail_data['date'] . "\n";

        $mail_sent = MailController::sendVisitorsMessageToAdmin(
          [
            'address' => $mail_data['email'],
            'name' => $mail_data['person']
          ],
          $subject,
          $plain_message
        );

        if ($mail_sent) {
          $consultation->mail_sent = 1;
          $consultation->save();
        }

        $response = new Response('', Response::HTTP_OK);

      } else {

        /** Ответ при ошибке */
        $response = new Response('', Response::HTTP_NOT_FOUND);
      }

      $response->headers->addCacheControlDirective('no-store');
      $response->headers->addCacheControlDirective('no-cache');
      $response->headers->addCacheControlDirective('private');
      $response->prepare($request);
      $response->send();
    }
  }

  /**
   * Отображение комментариев к статье
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   */
  public static function comment_article_show(Request $request, $id = null)
  {
    self::_comment_show($request, $id, 'core\\Models\\Article');
  }

  /**
   * Отображение комментариев к статье блога
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   */
  public static function comment_post_show(Request $request, $id = null)
  {
    self::_comment_show($request, $id, 'core\\Models\\Post');
  }

  /**
   * Отображение отзывов о товаре
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   */
  public static function comment_product_show(Request $request, $id = null)
  {
    self::_comment_show($request, $id, 'core\\Models\\Product');
  }

  /**
   * Добавление комментария к статье
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   * @throws \Exception
   */
  public static function comment_article_store(Request $request, $id = null)
  {
    self::_comment_store($request, $id, 'core\\Models\\Article');
  }

  /**
   * Добавление комментария к статье блога
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   * @throws \Exception
   */
  public static function comment_post_store(Request $request, $id = null)
  {
    self::_comment_store($request, $id, 'core\\Models\\Post');
  }

  /**
   * Добавление отзыва о товаре
   *
   * @static
   * @param Request  $request
   * @param int|null $id
   * @throws \Exception
   */
  public static function comment_product_store(Request $request, $id = null)
  {
    self::_comment_store($request, $id, 'core\\Models\\Product');
  }

  /**
   * Отображение отзывов и комментариев
   *
   * @param Request $request
   * @param null    $id
   * @param null    $model
   */
  private static function _comment_show(Request $request, $id = null, $model = null)
  {
    $items_per_page = 5;

    $content = [
      'page_current' => 1,
      'total_pages' => 0,
      'items' => []
    ];

    if (!is_null($id)) {
      $id = is_int($id) ? $id : is_numeric($id) ? intval($id) : null;
    }

    $result = !is_null($id) && ($id > 0) && !is_null($model);

    if ($result) {

      $result = false;

      $total = \core\Models\Response::countContentResponses($model, $id);
      if ($total > 0) {

        $total_pages = ceil($total / $items_per_page);

        $page_number = 1;
        if ($request->attributes->has('page')) {
          $page_number = $request->attributes->get('page');
        }

        if ($page_number <= $total_pages) {

          $items = \core\Models\Response::selectContentResponses($model, $id, true, ($page_number - 1) * $items_per_page, $items_per_page);

          $content['page_current'] = $page_number;
          $content['total_pages'] = $total_pages;

          /** @var \core\Models\Article | \core\Models\Post |  \core\Models\Response $object */
          foreach ($items as $object) {
            $content['items'][] = [
              'person' => $object->visitor()->name,
              'date' => $object->created_at->format('d.m.Y'),
              'body' => $object->body
            ];
          }

          $result = true;
        }
      }
    }

    if ($result) {

      /** Ответ в случае успеха */
      $response = new JsonResponse($content, Response::HTTP_OK);
    } else {
      /** Ответ при ошибке */
      $response = new Response('', Response::HTTP_NOT_FOUND);
    }

    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * Добавление комментария к статье или отзыва о товаре
   *
   * @static
   * @param Request     $request
   * @param int|null    $id - код статьи или товара
   * @param string|null $model
   * @throws \Exception
   */
  private static function _comment_store(Request $request, $id = null, $model = null)
  {
    $result = false;
    $response = null;

    if (!is_null($id)) {
      $id = is_int($id) ? $id : is_numeric($id) ? intval($id) : null;
    }

    if (!is_null($id)
      && ($id > 0)
      && !is_null($model)
      && !$request->attributes->has('page')
      && $request->request->has('person')
      && $request->request->has('email')
      && $request->request->has('message')
    ) {

      $person = trim($request->request->get('person'));
      $email = trim($request->request->get('email'));
      $message = trim($request->request->get('message'));

      if ($message != '') {
        // Замена символов табуляции на пробелы
        $message = preg_replace('/\t+/im', ' ', $message);
        // Удаление повторяющихся пробельных символов
        $message = preg_replace('/(\s)\s+/im', '$1', $message);
        // Унификация апострофов
        $message = preg_replace('/(\S)([\x{0022}\x{0027}\x{0060}]+)(\S)/imu', '$1\'$3', $message);
        // Удаление пробелов перед знаками препинания
        $message = preg_replace('/[ ]([.,;:!?…]+)/imu', '$1', $message);
        // Расстановка троеточий
        $message = preg_replace('/(\.{2,})/im', '…', $message);
        // Добавление отсутсвующих пробелов после знаков препинания
        $message = preg_replace('/([.,;:!?…]+)(\S)/imu', '$1 $2', $message);
        // Преобразование предопределённых символов в html-сущности
        $message = htmlentities($message, ENT_QUOTES, 'UTF-8');
        // Расстановка тега p
        $message = preg_replace('/^([^\r^\n]+)$/im', '<p>$1</p>', $message);
        // Замена пробельных символов на символ пробела
        $message = preg_replace('/\s+/im', ' ', $message);

        if ((bool)preg_match('/^([\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}]+([\-\x{0022}\x{0027}\x{0060}\x{2019}]?[\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}])+\s*)+$/imu', $person)) {
          // Удаление пробельных символов
          $email = preg_replace('/\s+/im', '', $email);
          if ((bool)preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/sim', $email)) {

            $entity = null;

            switch ($model) {
              case 'core\\Models\\Article':
                $entity = Article::find($id);
                break;
              case 'core\\Models\\Product':
                $entity = Product::find($id);
                break;
              case 'core\\Models\\Post':
                $entity = Post::find($id);
                break;
            }

            if (!is_null($entity)) {

              // Замена пробельных символов на символ пробела
              $person = preg_replace('/\s+/im', ' ', $person);

              $visitor = Visitor::findEmail($email);
              if (is_null($visitor)) {
                $visitor = Visitor::create(new Visitor([
                  'name' => $person,
                  'email' => $email
                ]));
              } else {
                if ($visitor->name == $visitor->email) {
                  $visitor->name = $person;
                  $visitor->save();
                }
              }

              if (!is_null($visitor)) {

                $response = \core\Models\Response::create(new \core\Models\Response([
                  'visitor_id' => $visitor->id,
                  'body' => $message,
                  'checked' => false,
                  'mail_sent' => false,
                  'ip' => $request->getClientIp(),
                  'browser' => null,
                  'content_id' => $id,
                  'content_type' => $model,
                  'created_at' => new \DateTime()
                ]));

              }

              $result = !is_null($response);
            }
          }
        }
      }
    }

    if ($result) {
      /** Ответ в случае успеха */
      $response = new Response('', Response::HTTP_OK);
    } else {
      /** Ответ при ошибке */
      $response = new Response('', Response::HTTP_NOT_FOUND);
    }

    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function cart_index(Request $request, $id = null)
  {
    if (is_null($id)) {
      $cart = Cart::getInstance();
      $items = $cart->items();
      $response = new JsonResponse($items, Response::HTTP_OK);
      $response->headers->addCacheControlDirective('no-store');
      $response->headers->addCacheControlDirective('no-cache');
      $response->headers->addCacheControlDirective('private');
      $response->prepare($request);
      $response->send();
    }
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function cart_show(Request $request, $id)
  {
    $cart = Cart::getInstance();
    $item = $cart->getItem($id);

    if (is_null($item)) {
      $response = new Response('', Response::HTTP_NOT_FOUND);
    } else {
      $response = new JsonResponse($item, Response::HTTP_OK);
    }
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function cart_store(Request $request, $id)
  {
    $cart = Cart::getInstance();

    if ($product = Product::find($id)) {
      $cart->addItem($product);
    }
    $item = $cart->getItem($id);

    if (is_null($item)) {
      $response = new Response('', Response::HTTP_NOT_FOUND);
    } else {
      $response = new JsonResponse($item, Response::HTTP_OK);
    }
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function cart_destroy(Request $request, $id)
  {
    $cart = Cart::getInstance();
    $cart->delItem($id);
    $response = new JsonResponse('', Response::HTTP_OK);
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function cart_update(Request $request, $id)
  {
    $result = false;

    $cart = Cart::getInstance();

    if ($cart->existItem($id) && $request->request->has('quantity')) {
      $quantity = $request->request->get('quantity');
      $result = $cart->setItemQuantity($id, $quantity);
    }
    if ($result) {
      $response = new JsonResponse('', Response::HTTP_OK);
    } else {
      $response = new JsonResponse('', Response::HTTP_NOT_FOUND);
    }
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

  /**
   * @param Request $request
   * @param         $id
   */
  public static function order_store(Request $request, $id = null)
  {
    $result = false;

    $cart = Cart::getInstance();
    if (count($cart->items()) > 0) {
      // Проверка данных формы "Контактные данные"
      if (
        $request->request->has('person') &&
        $request->request->has('email') &&
        $request->request->has('phone') &&
        $request->request->has('city') &&
        $request->request->has('delivery') &&
        $request->request->has('address')
      ) {
        $orderData = [
          'person' => trim($request->request->get('person')),
          'email' => trim($request->request->get('email')),
          'phone' => trim($request->request->get('phone')),
          'city' => trim($request->request->get('city')),
          'delivery' => trim($request->request->get('delivery')),
          'address' => trim($request->request->get('address'))
        ];

        if (Order::validate($orderData)) {

          $visitor = Visitor::findEmail($orderData['email']);
          if (is_null($visitor)) {
            $visitor = Visitor::create(new Visitor([
              'name' => $orderData['person'],
              'email' => $orderData['email']
            ]));
          } else {
            if ($visitor->name != $orderData['person']) {
              $visitor->name = $orderData['person'];
              $visitor->save();
            }
          }

          $orderData['person'] = $visitor->name;
          $orderData['visitor_id'] = $visitor->id;
          $orderData['email'] = $visitor->email;
          $orderData['items'] = $cart->items();

          if ($order = Order::create(new Order($orderData))) {
            // Данные сохранены в базе

            // Отправка писем пользователю и менеджеру
            $orderData['number'] = $order->id;
            $orderData['phone'] = $order->phone;
            $orderData['city'] = $order->city;
            $orderData['delivery'] = $order->delivery;
            $orderData['address'] = $order->address;
            $orderData['date'] = $order->created_at->format('d.m.Y');
            $orderData['total'] = $cart->sum();

            $twig = $GLOBALS['twig'];

            $template = "order_to_admin.twig";
            $html_message = $twig->render($template, $orderData);

            $subject = "Сайт Mirra. Заказ {$orderData['number']} от {$orderData['date']}";
            $result = MailController::sendOrderToAdmin(
              [
                'address' => $orderData['email'],
                'name' => $orderData['person']
              ],
              $subject,
              $html_message
            );

            if ($result) {

              $template = "order_to_customer.twig";
              $html_message = $twig->render($template, $orderData);

              $mail_sent = MailController::sendOrderToCustomer(
                [
                  'address' => $orderData['email'],
                  'name' => $orderData['person']
                ],
                $subject,
                $html_message
              );
            }

            if ($result) {
              $order->mail_sent = 1;
              $order->save();
            }

          }
        }
      }
    }

    if ($result) {
      $cart->reset();
      $response = new JsonResponse('', Response::HTTP_OK);
    } else {
      $response = new JsonResponse('', Response::HTTP_NOT_FOUND);
    }

    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('private');
    $response->prepare($request);
    $response->send();
  }

}
