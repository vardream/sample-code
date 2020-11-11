<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12.11.2017
 * Time: 23:52
 */

namespace core\Controllers;

use core\Services\Mail\MailFacade;

class MailController
{
  /**
   * Отправка сообщения администратору сайта от имени посетителя
   *
   * @static
   *
   * @param      $from
   * @param      $subject
   * @param      $plain_message
   * @param null $html_message
   * @return bool|int
   */
  public static function sendVisitorsMessageToAdmin($from, $subject, $plain_message, $html_message = null)
  {
    $result = false;

    try {
      $mailService = new MailFacade();

      $from_address = $from['address'];
      $from_name = $from['name'];

      $sender_address = $mailService->sender_email;
      $sender_name = $mailService->sender_name;

      $admin_address = $mailService->admin_email;
      $admin_name = $mailService->admin_name;

      if (
        is_string($sender_address) &&
        is_string($sender_name) &&
        is_string($admin_address) &&
        is_string($admin_name) &&
        $mailer = $mailService->Mailer()
      ) {
        $message = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom([$from_address => $from_name])
          ->setSender($sender_address, $sender_name)
          ->setTo([$admin_address => $admin_name])
          ->setBody($plain_message);

        if (!is_null($html_message)) {
          $message->addPart($html_message);
        }

        $result = (bool)$mailer->send($message);
      }

    } catch (\Swift_IoException $e) {
    }

    return $result;
  }

  /**
   * @param $from
   * @param $subject
   * @param $plain_message
   * @return bool|int
   */
  public static function sendOrderToAdmin($from, $subject, $plain_message)
  {
    $result = false;

    try {
      $mailService = new MailFacade();

      $from_address = $from['address'];
      $from_name = $from['name'];

      $sender_address = $mailService->sender_email;
      $sender_name = $mailService->sender_name;

      $admin_address = $mailService->admin_email;
      $admin_name = $mailService->admin_name;

      if (
        is_string($sender_address) &&
        is_string($sender_name) &&
        is_string($admin_address) &&
        is_string($admin_name) &&
        $mailer = $mailService->Mailer()
      ) {
        $message = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom([$from_address => $from_name])
          ->setSender($sender_address, $sender_name)
          ->setTo([$admin_address => $admin_name])
          ->setBody($plain_message);

        $result = (bool)$mailer->send($message);
      }

    } catch (\Swift_IoException $e) {
    }

    return $result;
  }

  /**
   * @param $to
   * @param $subject
   * @param $html_message
   * @return bool|int
   */
  public static function sendOrderToCustomer($to, $subject, $html_message)
  {
    $result = false;

    try {
      $mailService = new MailFacade();
      $to_address = $to['address'];
      $to_name = $to['name'];

      $sender_address = $mailService->sender_email;
      $sender_name = $mailService->sender_name;

      $admin_address = $mailService->admin_email;
      $admin_name = $mailService->admin_name;

      if (
        is_string($sender_address) &&
        is_string($sender_name) &&
        is_string($admin_address) &&
        is_string($admin_name) &&
        $mailer = $mailService->Mailer()
      ) {
        $message = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom([$admin_address => $admin_name])
          ->setSender($sender_address, $sender_name)
          ->setTo([$to_address => $to_name])
          ->setBody($html_message, 'text/html', 'utf-8');

        $result = (bool)$mailer->send($message);
      }

    } catch (\Swift_IoException $e) {
    }

    return $result;
  }
}