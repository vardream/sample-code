<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 30.01.2018
 * Time: 13:49
 */

namespace core\Controllers;

/**
 * Class ImageController
 *
 * @package core\Controllers
 */
class ImageController
{
  /**
   * Проверка наличия изображения
   *
   * @static
   * @param string $image
   * @return bool
   */
  public static function existsImage($image)
  {
    return file_exists($_SERVER['DOCUMENT_ROOT'] . $image);
  }

  /**
   * Возвращает имя изображения 2x
   *
   * В случае не корректного имени изображения - возвращает null
   *
   * @static
   * @param $image
   * @return null|string
   */
  public static function image2x($image)
  {
    $result = preg_replace('/(?!-x2)(\.[^.]+)$/im', '-x2$1', $image);
    if (!preg_match('/-x2\.[^.]+$/sim', $result)) {
      $result = null;
    }
    return $result;
  }
}