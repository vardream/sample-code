<?php

class ErrorHandler
{
  static public function error_404_not_found()
  {
    $title = '404 Страница не найдена';
    $message = <<<HTML
<p>Возможно страница {$_SERVER['REQUEST_URI']} была удалена либо допущена ошибка в адресе</p>
HTML;

    ob_start();
    ob_clean();
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/html; charset=UTF-8');
    self::template($title, $message);
    header('Content-Length: ' . ob_get_length());
    ob_end_flush();
    exit();
  }

  static public function error_504_gateway_timeout($comment = null)
  {
    $title = "Сайт временно недоступен.";
    $message = <<<HTML
<p>Возможно сейчас на сайте очень много посетителей.</p>
<p>Попробуйте обратиться к сайту через 15 минут.</p>
HTML;

    if (!is_null($comment)) {
      $message .= "<p>{$comment}</p>";
    }

    ob_start();
    ob_clean();
    header('HTTP/1.1 504 Gateway Timeout');
    header('Content-Type: text/html; charset=UTF-8');
    self::template($title, $message);
    header('Content-Length: ' . ob_get_length());
    ob_end_flush();
    exit();
  }

  static private function template($title, $message)
  {
    echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Lang" content="ru">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{$title}</title>
	<style type="text/css">
		body {
			font-family: Arial, Helvetica, sans-seif;
			font-size: 100%;
			line-height: 1.2em;
			left: 0;
			top: 0;
		}
	</style>
</head>
<body>
	<h1>{$title}</h1>
	{$message}
</body>
</html>
HTML;
  }
}
