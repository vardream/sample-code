<?php
/**
 * Created by PhpStorm.
 * User: vard
 * Date: 03.08.2018
 * Time: 17:56
 */

use core\Services\Mail\MailFacade;
use core\Services\Log\LoggerFacade;

require __DIR__ . '/../../vendor/autoload.php';
$cfg = include __DIR__ . '/../../config.php';

try {
  $mailService = new MailFacade();
  $mailService->flushQueue();

} catch (Swift_IoException $e) {
  if ($logger = LoggerFacade::getInstance()->logger()) {
    $logger->addError($e->getMessage(), [
      'code' => $e->getCode(),
      'file' => $e->getLine()
    ]);
  }
}