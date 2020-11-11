<?php
/**
 * Created by PhpStorm.
 * User: vard
 * Date: 06.08.2018
 * Time: 12:21
 */

namespace core\Services\Log;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * Class LoggerFacade
 *
 * @package core\Services\Log
 * @property bool $enabled
 * @property string $path
 */
class LoggerFacade
{
  static private $_instance;
  private $logger;
  private $maxFiles = 2;

  private function __construct()
  {
    if ($this->enabled) {
      try {
        $this->logger = new Logger('support');
        $this->logger->pushHandler(new RotatingFileHandler($this->path, $this->maxFiles, Logger::INFO, true, null, true));
      } catch (\Exception $e) {
      }
    }
  }

  /**
   * @return LoggerFacade
   */
  public static function getInstance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * @param $name
   * @return bool|mixed|null
   */
  public function __get($name)
  {
    $result = false;

    if ($config = $this->_config()) {

      switch ($name) {
        case 'enabled':
          $result = $config['enabled'];
          break;
        case 'path':
          $result = $config['path'];
          break;
        default:
          $result = null;
          break;
      }
    }

    return $result;
  }

  public function logger()
  {
    return $this->logger;
  }

  /**
   * @return array|null
   */
  private function _config()
  {
    $config = null;

    if (
      array_key_exists('cfg', $GLOBALS) &&
      array_key_exists('log', $GLOBALS['cfg']) &&
      is_array($GLOBALS['cfg']['log']) &&
      array_key_exists('enabled', $GLOBALS['cfg']['log']) &&
      $GLOBALS['cfg']['log']['enabled'] &&
      array_key_exists('path', $GLOBALS['cfg']['log'])
    ) {
      $config = $GLOBALS['cfg']['log'];
    }

    return $config;
  }
}