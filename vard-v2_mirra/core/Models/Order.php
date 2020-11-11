<?php
/**
 * Created by PhpStorm.
 * User: vard
 * Date: 22.06.2018
 * Time: 00:00
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Order
 *
 * @package core\Models
 *
 * @property int       $id
 * @property int       $visitor_id
 * @property string    $phone
 * @property string    $city
 * @property string    $address
 * @property string    $delivery
 * @property array     $items
 * @property boolean   $mail_sent
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Order
{
  private $fields = [
    'id' => null,
    'visitor_id' => null,
    'phone' => null,
    'city' => null,
    'address' => null,
    'delivery' => null,
    'items' => [],
    'mail_sent' => false,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Order constructor.
   *
   * @param array $fields
   */
  public function __construct($fields = [])
  {
    foreach ($fields as $field => $value) {
      $this->$field = $value;
    }
  }

  /**
   * @param $name
   * @return mixed|null
   */
  public function __get($name)
  {
    return array_key_exists($name, $this->fields) ? $this->fields[$name] : null;
  }

  /**
   * @param string $name
   * @param mixed  $value
   */
  public function __set($name, $value)
  {
    switch ($name) {
      case 'id':
        if (is_null($this->fields[$name]) && is_numeric($value)) {
          $value = intval($value);
          if ($value > 0) {
            $this->fields[$name] = $value;
          }
        }
        break;
      case 'visitor_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'phone':
        if (!is_null($value)) {
          $value = trim($value);
          if ($value != '') {
            $this->fields[$name] = $value;
          }
        }
        break;
      case 'city':
      case 'address':
      case 'delivery':
        if (!is_null($value)) {
          $value = trim($value);
        }
        if ($value == '') {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'items':
        if (is_array($value)) {
          $this->fields[$name] = $value;
        } elseif (is_string($value)) {
          $this->fields[$name] = unserialize($value);
        }
        break;
      case 'mail_sent':
        $this->fields[$name] = boolval($value);
        break;
      case "created_at":
      case "updated_at":
        if ($value instanceof \DateTime) {
          $this->fields[$name] = $value;
        } elseif (is_string($value)) {
          $this->fields[$name] = \DateTime::createFromFormat("Y-m-d H:i:s", trim($value));
          if (is_bool($this->fields[$name])) {
            $this->fields[$name] = null;
          }
        } else {
          $this->fields[$name] = null;
        }
        break;
      default:
        break;
    }
  }

  /**
   * @return Visitor|null
   */
  public function visitor()
  {
    return !is_null($this->visitor_id) ? Visitor::find($this->visitor_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id) &&
      !is_null($this->visitor_id) &&
      !is_null($this->phone) &&
      ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_orders
SET visitor_id = :visitor_id, 
  phone = :phone, 
  city = :city, 
  address = :address, 
  delivery = :delivery, 
  items = :items, 
  mail_sent = :mail_sent 
WHERE id = :id
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindValue(':visitor_id', $this->visitor_id);
      $stmt->bindValue(':phone', $this->phone);
      $stmt->bindValue(':city', $this->city);
      $stmt->bindValue(':address', $this->address);
      $stmt->bindValue(':delivery', $this->delivery);
      $stmt->bindValue(':items', serialize($this->items));
      $stmt->bindValue(':mail_sent', $this->mail_sent);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }

  /**
   * @param $id
   * @return Order|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_orders WHERE id = $id
SQL;

      /** @var Order $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param Order $order
   * @return Order|null
   */
  public static function create(Order $order)
  {
    $result = null;

    if (!is_null($order->visitor_id) &&
      !is_null($order->phone) &&
      ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_orders (visitor_id, phone, city, address, delivery, items, mail_sent, created_at)
  VALUES (:visitor_id, :phone, :city, :address, :delivery, :items, :mail_sent, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':visitor_id', $order->visitor_id);
      $stmt->bindValue(':phone', $order->phone);
      $stmt->bindValue(':city', $order->city);
      $stmt->bindValue(':address', $order->address);
      $stmt->bindValue(':delivery', $order->delivery);
      $stmt->bindValue(':items', serialize($order->items));
      $stmt->bindValue(':mail_sent', $order->mail_sent);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  public static function validate($data = [])
  {
    $result = false;
    if (
      !empty($data) &&
      array_key_exists('person', $data) &&
      array_key_exists('email', $data) &&
      array_key_exists('phone', $data) &&
      array_key_exists('city', $data) &&
      array_key_exists('address', $data) &&
      array_key_exists('delivery', $data)
    ) {
      foreach ($data as $key => $value) {
        // Замена пробельных символов на символ пробела
        $data[$key] = trim(preg_replace('/\s+/im', ' ', $value));
      }
      $result = true;
      $result = $result && (bool) preg_match('/^([\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}]+([\-\x{0022}\x{0027}\x{0060}\x{2019}]?[\x{0401}\x{0404}\x{0406}\x{0407}\x{0451}\x{0454}\x{0456}\x{0457}\x{0490}\x{0491}\x{0410}-\x{044F}])+\s*)+$/imu', $data['person']);
      $result = $result && (bool) preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/sim', $data['email']);
      $result = $result && (bool) preg_match('/^\+?\d+[\s\-.]*(\(\d+\)|\d+)[\s\-.]*(\d+[\s\-.]*)+$/sim', $data['phone']);
      // Проверка длины поля
      $len = mb_strlen($data['person'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 64);
      $len = mb_strlen($data['email'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 64);
      $len = mb_strlen($data['phone'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 32);
      $len = mb_strlen($data['city'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 64);
      $len = mb_strlen($data['address'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 255);
      $len = mb_strlen($data['delivery'], 'utf-8');
      $result = $result && ($len > 0 && $len <= 255);
    }
    return $result;
  }
}