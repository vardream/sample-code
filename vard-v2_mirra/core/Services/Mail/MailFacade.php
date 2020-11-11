<?php
/**
 * Created by PhpStorm.
 * User: vard
 * Date: 03.08.2018
 * Time: 13:32
 */

namespace core\Services\Mail;

use core\Services\Log\LoggerFacade;

/**
 * Class MailFacade
 *
 * @package core\Services\Mail
 * @property string|null $driver
 * @property string|null $host
 * @property int|null    $port
 * @property string|null $encryption
 * @property string|null $spool_type
 * @property string|null $spool_path
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property string|null $admin_email
 * @property string|null $admin_name
 * @property string|null $support_email
 * @property string|null $support_name
 */
class MailFacade
{
  private $transport = null;

  /**
   * MailFacade constructor.
   *
   * @throws \Swift_IoException
   */
  public function __construct()
  {
    $spool_type = $this->spool_type;
    if (!is_bool($spool_type)) {
      switch ($spool_type) {
        case 'file':
          $path = $this->spool_path;
          if (is_string($path)) {
            $this->transport = new \Swift_SpoolTransport(new \Swift_FileSpool($path));
          }
          break;
        case 'memory':
          $this->transport = new \Swift_SpoolTransport(new \Swift_MemorySpool());
          break;
        default:
          $this->transport = $this->_createTransport();
          break;
      }
    }
  }

  /**
   * @return null|\Swift_Mailer
   */
  public function Mailer()
  {
    return !is_null($this->transport) ? new \Swift_Mailer($this->transport) : null;
  }

  /**
   * @param $name
   * @return string|int|null|bool
   */
  public function __get($name)
  {
    // При отсутвии данных о конфигурации
    $result = false;

    $config = $this->_config();

    if (is_array($config)) {

      switch ($name) {
        case 'driver':
          $result = array_key_exists('mail_driver', $config) ? $config['mail_driver'] : null;
          break;
        case 'host':
          $result = array_key_exists('mail_host', $config) ? $config['mail_host'] : null;
          break;
        case 'port':
          $result = array_key_exists('mail_port', $config) ? $config['mail_port'] : null;
          break;
        case 'encryption':
          $result = array_key_exists('mail_encryption', $config) ? $config['mail_encryption'] : null;
          break;
        case 'spool_type':
          $result = null;
          if (
            array_key_exists('spool', $config) &&
            is_array($config['spool']) &&
            array_key_exists('type', $config['spool'])
          ) {
            $result = $config['spool']['type'];
          }
          break;
        case 'spool_path':
          $result = null;
          if (
            array_key_exists('spool', $config) &&
            is_array($config['spool']) &&
            array_key_exists('path', $config['spool'])
          ) {
            $result = $config['spool']['path'];
          }
          break;
        case 'sender_email':
          $result = null;
          if (
            array_key_exists('from', $config) &&
            is_array($config['from']) &&
            array_key_exists('address', $config['from'])
          ) {
            $result = $config['from']['address'];
          }
          break;
        case 'sender_name':
          $result = null;
          if (
            array_key_exists('from', $config) &&
            is_array($config['from']) &&
            array_key_exists('name', $config['from'])
          ) {
            $result = $config['from']['name'];
          }
          break;
        case 'admin_email':
          $result = null;
          if (
            array_key_exists('admin', $config) &&
            is_array($config['admin']) &&
            array_key_exists('address', $config['admin'])
          ) {
            $result = $config['admin']['address'];
          }
          break;
        case 'admin_name':
          $result = null;
          if (
            array_key_exists('admin', $config) &&
            is_array($config['admin']) &&
            array_key_exists('name', $config['admin'])
          ) {
            $result = $config['admin']['name'];
          }
          break;
        case 'support_email':
          $result = null;
          if (
            array_key_exists('support', $config) &&
            is_array($config['support']) &&
            array_key_exists('address', $config['support'])
          ) {
            $result = $config['support']['address'];
          }
          break;
        case 'support_name':
          $result = null;
          if (
            array_key_exists('support', $config) &&
            is_array($config['support']) &&
            array_key_exists('name', $config['support'])
          ) {
            $result = $config['support']['name'];
          }
          break;
        default:
          // При отсутвии данных в конфигурации
          $result = null;
      }
    }

    return $result;
  }

  public function flushQueue($messageLimit = 10, $timeLimit = 30, $recoverTimeout = -1)
  {
    $sent = 0;

    if ($this->transport instanceof \Swift_Transport_SpoolTransport) {

      $spool = $this->transport->getSpool();

      if ($spool instanceof \Swift_ConfigurableSpool) {
        $spool->setMessageLimit($messageLimit);
        $spool->setTimeLimit($timeLimit);
      }

      if ($spool instanceof \Swift_FileSpool) {
        if ($recoverTimeout > 0) {
          $spool->recover($recoverTimeout);
        } else {
          $spool->recover();
        }
      }

      $sent = $spool->flushQueue($this->_createTransport());

      if (
        $sent > 0 &&
        $logger = LoggerFacade::getInstance()->logger()
      ) {

        $logger->addInfo('Отправлено ' . $sent . ' сообщений.', [
          'Source: ' => __METHOD__
        ]);
      }
    }
    return $sent;
  }

  /**
   * @return string|null
   */
  private function _username()
  {
    $result = null;

    $config = $this->_config();
    if (is_array($config)) {
      $result = array_key_exists('mail_username', $config) ? $config['mail_username'] : null;
    }

    return $result;
  }

  /**
   * @return string|null
   */
  private function _password()
  {
    $result = null;

    $config = $this->_config();
    if (is_array($config)) {
      $result = array_key_exists('mail_password', $config) ? $config['mail_password'] : null;
    }

    return $result;
  }

  /**
   * @return array|null
   */
  private function _config()
  {
    $config = null;
    if (
      array_key_exists('cfg', $GLOBALS) &&
      is_array($GLOBALS['cfg']) &&
      array_key_exists('mail', $GLOBALS['cfg'])
    ) {
      $config = $GLOBALS['cfg']['mail'];
    }
    return $config;
  }

  private function _createTransport()
  {
    $transport = null;
    if ($this->driver == 'smtp') {
      $transport = new \Swift_SmtpTransport(
        $this->host,
        $this->port,
        $this->encryption
      );
      if ($transport) {
        $transport
          ->setUsername($this->_username())
          ->setPassword($this->_password());
      }
    }

    return $transport;
  }
}