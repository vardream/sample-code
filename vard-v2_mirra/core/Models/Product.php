<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29.09.2017
 * Time: 18:31
 */

namespace core\Models;

use core\DBMySQL;
use core\Registry;

/**
 * Class Product
 *
 * @package core\Models
 *
 * @property int       $id,
 * @property string    $nomenclature
 * @property bool      $is_available
 * @property int       $package_id
 * @property float     $quantity
 * @property int       $unit_id
 * @property float     $price
 * @property string    $title
 * @property string    $notice
 * @property string    $body
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Product
{
  const ORDER_BY_NOMENCLATURE = 'nomenclature';
  const ORDER_BY_TITLE = 'title';
  const ORDER_BY_PRICE = 'price';

  private $fields = [
    'id' => null,
    'nomenclature' => null,
    'is_available' => false,
    'package_id' => null,
    'quantity' => 0.000,
    'unit_id' => null,
    'price' => 0.00,
    'title' => null,
    'notice' => null,
    'body' => null,
    'created_at' => null,
    'updated_at' => null
  ];

  /**
   * Product constructor.
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
      case 'is_available':
        if (!is_bool($value)) {
          if (is_numeric($value)) {
            $value = boolval(intval($value));
          } else if (is_int($value)) {
            $value = boolval($value);
          } else {
            $value = false;
          }
        }
        $this->fields[$name] = $value;
        break;
      case 'package_id':
      case 'unit_id':
        if (!is_null($value) && !is_int($value)) {
          $value = intval($value);
        }
        if (is_int($value) && ($value < 1)) {
          $value = null;
        }
        $this->fields[$name] = $value;
        break;
      case 'nomenclature':
      case 'title':
      case 'notice':
      case 'body':
        if (!is_null($value)) {
          if (is_bool($value)) {
            $value = null;
          } else {
            $value = trim($value);
            if ($value == '') {
              $value = null;
            } else {
              $value = preg_replace('/(\s{2,})/sim', ' ', $value);
              $length = mb_strlen($value);
              switch ($name) {
                case 'nomenclature':
                  if ($length > 32) {
                    $value = mb_substr($value, 0, 32);
                  }
                  break;
                case 'title':
                  if ($length > 255) {
                    $value = mb_substr($value, 0, 255);
                  }
                  break;
                case 'notice':
                case 'body':
                  if ($length > 65535) {
                    $value = mb_substr($value, 0, 65535);
                  }
                  break;
              }
            }
          }
        }
        $this->fields[$name] = $value;
        break;
      case 'quantity':
      case 'price':
        if (is_null($value)) {
          $value = 0;
        } else {
          if (!is_float($value)) {
            $value = floatval($value);
          }
        }
        $this->fields[$name] = ($value > 0) ? $value : 0;
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
   * @return Package|null
   */
  public function package()
  {
    return !is_null($this->package_id) ? Package::find($this->package_id) : null;
  }

  /**
   * @return Unit|null
   */
  public function unit()
  {
    return !is_null($this->unit_id) ? Unit::find($this->unit_id) : null;
  }

  public function discounts($discount_type = null, $active = true)
  {
    return ProductDiscount::selectProductDiscounts($this->id, $discount_type, $active);
  }

  /**
   * @return float
   */
  public function discount()
  {
    return ProductDiscount::getProductDiscount($this);
  }

  /**
   * @return Page|null
   */
  public function page()
  {
    return Page::findContentPage(__CLASS__, $this->id);
  }

  /**
   * @param $slug
   * @return Tag|null
   */
  public function tag($slug)
  {
    return Tag::findContentTagWithSlug(__CLASS__, $this->id, $slug);
  }


  /**
   * @param null $slug
   * @return array
   */
  public function tags($slug = null)
  {
    return Tag::selectContentTags(__CLASS__, $this->id, $slug);
  }

  /**
   * @param bool $checked
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function responses($checked = true, $from = 0, $limit = null)
  {
    return Response::selectContentResponses(__CLASS__, $this->id, $checked, $from, $limit);
  }

  /**
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public function videos($from = 0, $limit = null)
  {
    return Video::selectContentVideo(__CLASS__, $this->id, $from, $limit);
  }

  /**
   * @return bool
   */
  public function save()
  {
    $result = false;

    if (!is_null($this->id) && !is_null($this->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
UPDATE mirra_products SET nomenclature = :nomenclature, is_available = :is_available, package_id = :package_id, quantity = :quantity, unit_id = :unit_id, price = :price, title = :title, notice = :notice, body = :body WHERE id = :id
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindValue(':nomenclature', $this->nomenclature);
      $stmt->bindValue(':is_available', intval($this->is_available));
      $stmt->bindValue(':package_id', $this->package_id);
      $stmt->bindValue(':quantity', $this->quantity);
      $stmt->bindValue(':unit_id', $this->unit_id);
      $stmt->bindValue(':price', $this->price);
      $stmt->bindValue(':title', $this->title);
      $stmt->bindValue(':notice', $this->notice);
      $stmt->bindValue(':body', $this->body);
      $stmt->bindValue(':id', $this->id);

      $result = $stmt->execute();

    }

    return $result;
  }


  /**
   * @param Product $product
   * @return Product|null
   */
  public static function create(Product $product)
  {
    $result = null;

    if (!is_null($product->nomenclature) && !is_null($product->title) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
INSERT INTO mirra_products (nomenclature, is_available, package_id, quantity, unit_id, price, title, notice, body, created_at) VALUES (:nomenclature, :is_available, :package_id, :quantity, :unit_id, :price, :title, :notice, :body, NOW())
SQL;
      $stmt = $db->prepare($query);
      $stmt->bindValue(':nomenclature', $product->nomenclature);
      $stmt->bindValue(':is_available', intval($product->is_available));
      $stmt->bindValue(':package_id', $product->package_id);
      $stmt->bindValue(':quantity', $product->quantity);
      $stmt->bindValue(':unit_id', $product->unit_id);
      $stmt->bindValue(':price', $product->price);
      $stmt->bindValue(':title', $product->title);
      $stmt->bindValue(':notice', $product->notice);
      $stmt->bindValue(':body', $product->body);

      if ($stmt->execute()) {
        $result = self::find($db->lastInsertId());
      }
    }

    return $result;
  }

  /**
   * @param int $id
   * @return Product|null
   */
  public static function find($id)
  {
    $reg = Registry::getInstance();
    $key = Registry::createKey(__CLASS__, $id);

    if (!$reg->existsObject($key) && ($db = DBMySQL::getInstance()->connection())) {

      $query = <<<SQL
SELECT * FROM  mirra_products WHERE id = $id
SQL;

      /** @var Product $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $reg->addObject($key, $object);
      }
    }

    return $reg->existsObject($key) ? $reg->getObject($key) : null;
  }

  /**
   * @param $nomenclature
   * @return Product|null
   */
  public static function findNomenclature($nomenclature)
  {
    $result = null;

    if ($db = DBMySQL::getInstance()->connection()) {

      $query = <<<SQL
SELECT * FROM mirra_products WHERE nomenclature = $nomenclature
SQL;

      $reg = Registry::getInstance();

      /** @var Product $object */
      foreach ($db->query($query, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);

        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }

        $result = $reg->getObject($key);
      }
    }

    return $result;
  }

  /**
   * @return int
   */
  public static function countItems()
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(id) as total FROM mirra_products
SQL;
      foreach ($db->query($statement, \PDO::FETCH_ASSOC) as $row) {
        $result = $row['total'];
      }

    }

    return $result;
  }

  /**
   * @param int $from
   * @param int $number
   * @return array
   */
  public static function selectItems($from = 0, $number = 10)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT * FROM mirra_products LIMIT $from,$number
SQL;

      $reg = Registry::getInstance();

      /** @var Product $object */
      foreach ($db->query($statement, \PDO::FETCH_CLASS, __CLASS__) as $object) {
        $key = Registry::createKey(__CLASS__, $object->id);

        if (!$reg->existsObject($key)) {
          $reg->addObject($key, $object);
        }

        $result[] = $reg->getObject($key);
      }

    }

    return $result;
  }

  /**
   * @param Tag  $tag
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @return array
   */
  public static function selectTagContent(Tag $tag, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT mp.* FROM mirra_tags_items mti, mirra_products mp, mirra_pages p WHERE mti.tag_id = :tag_id AND mti.content_type = :content_type AND mp.id = mti.content_id  AND p.content_type = mti.content_type AND p.content_id = mti.content_id
SQL;

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      switch ($order) {
        case self::ORDER_BY_TITLE :
          $statement .= " ORDER BY mp.title ASC";
          break;
        case self::ORDER_BY_NOMENCLATURE :
          $statement .= " ORDER BY mp.nomenclature ASC";
          break;
        case self::ORDER_BY_PRICE :
          $statement .= " ORDER BY mp.price ASC";
          break;
        default:
          $statement .= " ORDER BY mti.position ASC, mp.title ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT $from,$limit";
      }

      $class = __CLASS__;
      $tag_id = $tag->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':tag_id', $tag_id);
      $stmt->bindValue(':content_type', $class);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Product $object */
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
   * @param Tag  $tag
   * @param bool $published
   * @return int
   */
  public static function selectTagContentCount(Tag $tag, $published = true)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT COUNT(mp.id) as total FROM mirra_tags_items mti, mirra_products mp, mirra_pages p WHERE mti.tag_id = :tag_id AND mti.content_type = :content_type AND mp.id = mti.content_id  AND p.content_type = mti.content_type AND p.content_id = mti.content_id
SQL;

      if ($published) {
        $statement .= " AND p.published = 1";
      }

      $class = __CLASS__;
      $tag_id = $tag->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':tag_id', $tag_id);
      $stmt->bindValue(':content_type', $class);

      if ($stmt->execute()) {
        $result = $stmt->fetchColumn(0);
      }

    }

    return $result;
  }

  public static function selectPageContent(Page $page, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT p.* FROM mirra_products p, mirra_pages mp WHERE mp.parent_id = :page_id AND mp.content_type = :content_type AND p.id = mp.content_id
SQL;
      if (!is_null($published)) {
        $statement .= " AND mp.published = :published";
      }

      if (!is_null($handler_id)) {
        $statement .= " AND mp.handler_id = :handler_id";
      }

      if (is_null($order)) {
        $order = self::ORDER_BY_NOMENCLATURE;
      }

      switch ($order) {
        case self::ORDER_BY_NOMENCLATURE :
          $statement .= " ORDER BY p.nomenclature ASC";
          break;
        case self::ORDER_BY_TITLE :
          $statement .= " ORDER BY p.title ASC";
          break;
        case self::ORDER_BY_PRICE :
          $statement .= " ORDER BY p.price ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT {$from},{$limit}";
      }

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', __CLASS__);
      $stmt->bindValue(':page_id', $page->id);

      if (!is_null($published)) {
        $stmt->bindValue(':published', $published);
      }

      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Product $object */
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
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @param null $order
   * @param int  $from
   * @param null $limit
   * @param int  $deep
   * @return array
   */
  public static function selectPageContentRecursive(Page $page, $handler_id = null, $published = true, $order = null, $from = 0, $limit = null, $deep = 3)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';
      $where = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= " mp{$i}.content_id";
          $where .= "mp{$i}.content_type = :content_type";

        } else {
          $select = "IFNULL(mp{$i}.content_id, {$select})";
          $where .= " OR mp{$i}.content_type = :content_type";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id";
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
        if (!is_null($handler_id)) {
          $where .= " AND mp{$i}.handler_id = :handler_id";
        }
      }

      $statement = "SELECT * FROM mirra_products WHERE id IN (SELECT DISTINCT {$select} AS id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id AND ({$where}))";

      switch ($order) {
        case self::ORDER_BY_NOMENCLATURE :
          $statement .= " ORDER BY nomenclature ASC";
          break;
        case self::ORDER_BY_TITLE :
          $statement .= " ORDER BY title ASC";
          break;
        case self::ORDER_BY_PRICE :
          $statement .= " ORDER BY price ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT {$from},{$limit}";
      }

      $page_id = $page->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', __CLASS__);
      $stmt->bindValue(':page_id', $page_id);

      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Product $object */
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
   * @param Page $page
   * @param null $handler_id
   * @param bool $published
   * @param int  $deep
   * @return int
   */
  public static function selectPageContentRecursiveCount(Page $page, $handler_id = null, $published = true, $deep = 3)
  {
    $result = 0;

    if ($db = DBMySQL::getInstance()->connection()) {

      $select = '';
      $left = '';
      $where = '';

      for ($i = 1; $i <= $deep; $i++) {

        if ($i == 1) {
          $select .= " mp{$i}.content_id";
          $where .= "mp{$i}.content_type = :content_type";

        } else {
          $select = "IFNULL(mp{$i}.content_id, {$select})";
          $where .= " OR mp{$i}.content_type = :content_type";
        }
        $left .= " LEFT JOIN mirra_pages mp{$i} ON mp{$i}.parent_id = mp" . ($i - 1) . ".id";
        if ($published) {
          $left .= " AND mp{$i}.published = 1";
        }
        if (!is_null($handler_id)) {
          $where .= " AND mp{$i}.handler_id = :handler_id";
        }
      }

      $statement = "SELECT COUNT(id) as total FROM mirra_products WHERE id IN (SELECT DISTINCT {$select} AS id FROM mirra_pages mp0 {$left} WHERE mp0.id = :page_id AND ({$where}))";

      $class = __CLASS__;
      $page_id = $page->id;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':content_type', $class);
      $stmt->bindValue(':page_id', $page_id);

      if (!is_null($handler_id)) {
        $stmt->bindValue(':handler_id', $handler_id);
      }

      if ($stmt->execute()) {
        $result = $stmt->fetchColumn(0);
      }
    }

    return $result;
  }

  /**
   * @param Discount $discount
   * @param bool     $published
   * @param null     $order
   * @param int      $from
   * @param null     $limit
   * @return array
   */
  public static function selectDiscountProducts(Discount $discount, $published = true, $order = null, $from = 0, $limit = null)
  {
    $result = [];

    if ($db = DBMySQL::getInstance()->connection()) {

      $statement = <<<SQL
SELECT mp.* FROM mirra_products_discounts mpd, mirra_products mp, mirra_pages p WHERE mpd.discount_id = :discount_id AND mp.id = mpd.product_id AND p.content_id = mpd.product_id AND p.content_type = :content_type
SQL;
      if ($published) {
        $statement .= " AND p.published = 1";
      }

      if (is_null($order)) {
        $order = self::ORDER_BY_NOMENCLATURE;
      }

      switch ($order) {
        case self::ORDER_BY_NOMENCLATURE :
          $statement .= " ORDER BY mp.nomenclature ASC";
          break;
        case self::ORDER_BY_TITLE :
          $statement .= " ORDER BY mp.title ASC";
          break;
        case self::ORDER_BY_PRICE :
          $statement .= " ORDER BY mp.price ASC";
          break;
      }

      if (!is_null($limit)) {
        $statement .= " LIMIT {$from},{$limit}";
      }

      $class = __CLASS__;

      $stmt = $db->prepare($statement);
      $stmt->bindValue(':discount_id', $discount->id);
      $stmt->bindValue(':content_type', $class);

      if ($stmt->execute()) {

        $reg = Registry::getInstance();

        /** @var Product $object */
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