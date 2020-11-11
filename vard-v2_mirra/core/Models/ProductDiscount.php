<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26.10.2017
 * Time: 17:51
 */

namespace core\Models;

use core\Registry;
use core\DBMySQL;

/**
 * Class ProductDiscount
 *
 * @package core\Models
 *
 * @property int $id
 * @property int $discount_id
 * @property int $product_id
 * @property string $type
 * @property int $priority
 * @property bool $final
 * @property float $discount_value
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class ProductDiscount
{
  const TYPE_PRICE = 'price';
  const TYPE_PERCENT = 'percent';
  const TYPE_VALUE = 'value';

  private $fields = [
    'id' => null,
    'discount_id' => null,
    'product_id' => null,
    'type' => self::TYPE_PRICE,
    'priority' => 0,
    'final' => false,
    'discount_value' => 0,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * ProductDiscount constructor.
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
   * @param mixed $value
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
      case 'discount_id':
      case 'product_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'type':
        if (!is_null($value)) {
          $value = strtolower($value);
          if ($value == self::TYPE_PRICE OR $value == self::TYPE_PERCENT OR $value == self::TYPE_VALUE) {
            $this->fields[$name] = $value;
          }
        }
        break;
      case 'priority':
        if (!is_null($value)) {
          $value = !is_int($value) ? intval($value) : $value;
          $this->fields[$name] = ($value < 0) ? 0 : $value;
        } else {
          $this->fields[$name] = 0;
        }
        break;
      case 'final':
        $this->fields[$name] = boolval($value);
        break;
      case 'discount_value':
        if (!is_null($value)) {
          $value = !is_float($value) ? floatval($value) : $value;
          $this->fields[$name] = ($value < 0) ? 0 : $value;
        } else {
          $this->fields[$name] = 0;
        }
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
   * @return Discount|null
   */
  public function discount()
  {
    return !is_null($this->discount_id) ? Discount::find($this->discount_id) : null;
  }

  /**
   * @return Product|null
   */
  public function product()
  {
    return !is_null($this->product_id) ? Product::find($this->product_id) : null;
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id)
      && !is_null($this->discount_id)
      && !is_null($this->product_id)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $query = <<<SQL
UPDATE mirra_products_discounts SET discount_id = :discount_id, product_id = :product_id, type = :type, priority = :priority, final = :final, discount_value = :discount_value WHERE id = :id
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':discount_id', $this->discount_id);
      $stmt->bindValue(':product_id', $this->product_id);
      $stmt->bindValue(':type', $this->type);
      $stmt->bindValue(':priority', $this->priority);
      $stmt->bindValue(':final', intval($this->final));
      $stmt->bindValue(':discount_value', $this->discount_value);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * @param $id
   * @return ProductDiscount|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_products_discounts WHERE id = $id
SQL;

      /** @var ProductDiscount $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param ProductDiscount $product_discount
   * @return ProductDiscount|null
   */
  public static function create(ProductDiscount $product_discount)
  {
    $result = null;

    if (!is_null($product_discount->discount_id)
      && !is_null($product_discount->product_id)
      && ($db = DBMySQL::getInstance()->connection())
    ) {

      $query = <<<SQL
INSERT INTO mirra_products_discounts (discount_id, product_id, type, priority, final, discount_value, created_at) VALUES (:discount_id, :product_id, :type, :priority, :final, :discount_value, NOW())
SQL;

      $stmt = $db->prepare($query);
      $stmt->bindValue(':discount_id', $product_discount->discount_id);
      $stmt->bindValue(':product_id', $product_discount->product_id);
      $stmt->bindValue(':type', $product_discount->type);
      $stmt->bindValue(':priority', $product_discount->priority);
      $stmt->bindValue(':final', $product_discount->final);
      $stmt->bindValue(':discount_value', $product_discount->discount_value);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param $discount_id
   * @return array
   */
  public static function selectDiscountItems($discount_id)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_products_discounts WHERE discount_id = :discount_id ORDER BY priority ASC
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindParam(':discount_id', $discount_id);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var ProductDiscount $object */
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

  /**
   * @param      $product_id
   * @param null $discount_type
   * @param bool $active
   * @return array
   */
  public static function selectProductDiscounts($product_id, $discount_type = null, $active = true)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT mpd.* FROM mirra_products_discounts mpd, mirra_discounts md WHERE mpd.product_id = :product_id AND md.id = mpd.discount_id 
SQL;

      if (!is_null($discount_type)) {
        $statement .= " AND md.discount_type = :discount_type";
      }

      if ($active) {
        $statement .= " AND md.date_from IS NOT NULL AND md.date_from <= NOW() AND (md.date_to IS NULL OR md.date_to >= NOW())";
      }

      $statement .= " ORDER BY md.date_from DESC, md.date_to DESC";

      $stmt = $db->prepare($statement);
      $stmt->bindParam(':product_id', $product_id);

      if (!is_null($discount_type)) {
        $stmt->bindParam(':discount_type', $discount_type);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var ProductDiscount $object */
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

  /**
   * @param Product $product
   * @return float
   */
  public static function getProductDiscount(Product $product)
  {
    $result = 0.00;
    $discounts = [];

    if ($db = DBMySQL::getInstance()->connection() AND !is_null($product->id)) {

      $statement = <<<SQL
SELECT mpd.* FROM mirra_products_discounts mpd, mirra_discounts md WHERE mpd.product_id = :product_id AND md.id = mpd.discount_id  AND md.date_from IS NOT NULL AND md.date_from <= NOW() AND (md.date_to IS NULL OR md.date_to >= NOW()) ORDER BY mpd.priority DESC, md.date_from DESC, md.date_to ASC  
SQL;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':product_id', $product->id);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var ProductDiscount $object */
        while ($object = $stmt->fetchObject(__CLASS__)) {
          $key = Registry::createKey(__CLASS__, $object->id);
          if (!$reg->existsObject($key)) {
            $reg->addObject($key, $object);
          }
          $discounts[] = $reg->getObject($key);
        }

      }

      if (!empty($discounts)) {
        $result = $product->price;

        /** @var ProductDiscount $discount */
        foreach ($discounts as $discount) {
          switch ($discount->type) {
            case self::TYPE_PERCENT:
              $result = $result * (1 - $discount->discount_value / 100);
              break;
            case self::TYPE_VALUE:
              $result = $result - $discount->discount_value;
              break;
            default:
              $result = $discount->discount_value;
              break;
          }

          if ($discount->final) {
            break;
          }
        }
      }

      if ($result < 0) {
        $result = 0;
      }

    }

    return round($result, 0);
  }
}