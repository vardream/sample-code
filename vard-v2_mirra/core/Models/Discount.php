<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26.10.2017
 * Time: 13:31
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class Discount
 *
 * @package core\Models
 *
 * @property int       $id
 * @property string    $title
 * @property string    $discount_type
 * @property int       $priority
 * @property \DateTime $date_from
 * @property \DateTime $date_to
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Discount
{
  private $fields = [
    'id' => null,
    'title' => null,
    'discount_type' => null,
    'priority' => 1,
    'date_from' => null,
    'date_to' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Discount constructor.
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
      case 'priority':
        if (!is_null($value)) {
          $value = !is_int($value) ? intval($value) : $value;
          $this->fields[$name] = ($value < 0) ? 0 : $value;
        } else {
          $this->fields[$name] = 1;
        }
        break;
      case 'title':
      case 'discount_type':
        if (!is_null($value)) {
          $value = trim($value);
          if ($value != '') {
            $this->fields[$name] = $value;
          }
        }
        break;
      case "date_from":
      case "date_to":
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
   * @return array
   */
  public function items()
  {
    return ProductDiscount::selectDiscountItems($this->id);
  }

  /**
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function products($published = true, $order = null, $from = 0, $limit = null)
  {
    return Product::selectDiscountProducts($this, $published, $order, $from, $limit);
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id)
      && !is_null($this->title)
      && !is_null($this->discount_type)
      && !is_null($this->date_from)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $query = <<<SQL
UPDATE mirra_discounts SET title = :title, discount_type = :discount_type, priority = :priority, date_from = :date_from, date_to = :date_to WHERE id = :id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':title', $this->title);
      $stmt->bindValue(':discount_type', $this->discount_type);
      $stmt->bindValue(':priority', $this->priority);
      $stmt->bindValue(':date_from', $this->date_from);
      $stmt->bindValue(':date_to', $this->date_to);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param $id
   * @return Discount|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_discounts WHERE id = $id
SQL;

      /** @var Discount $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param Discount $discount
   * @return Discount|null
   */
  public static function create(Discount $discount)
  {
    $result = null;

    if (!is_null($discount->title)
      && !is_null($discount->discount_type)
      && !is_null($discount->date_from)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $query = <<<SQL
INSERT INTO mirra_discounts (title, discount_type, priority, date_from, date_to, created_at) VALUES (:title, :discount_type, :priority, :date_from, :date_to, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':title', $discount->title);
      $stmt->bindValue(':discount_type', $discount->discount_type);
      $stmt->bindValue(':priority', $discount->priority);
      $stmt->bindValue(':date_from', $discount->date_from->format('Y-m-d H:i:s'));
      $stmt->bindValue(':date_to', $discount->date_to->format('Y-m-d H:i:s'));

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param      $discount_type
   * @param bool $active
   * @return array
   */
  public static function selectDiscountsWithType($discount_type, $active = true)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_discounts WHERE discount_type = :discount_type 
SQL;
      if ($active) {
        $statement .= " AND date_from IS NOT NULL AND date_from <= NOW() AND (date_to IS NULL OR date_to >= NOW())";
      }

      $statement .= " ORDER BY date_from DESC, date_to DESC";

      $stmt = $db->prepare($statement);
      $stmt->bindParam(':discount_type', $discount_type);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Discount $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $result[] = $reg->getObject($key);
        }

      }

    }

    return $result;
  }
}