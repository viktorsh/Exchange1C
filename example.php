<?php
/**
 * Пример:
 * загрузка файла import
 * Исходный файл лежит локально тамже где запускается скрипт
 * Удаленный сайк может быть как этот же сервир либо любой другой удаленный
 * Скрипт выполняет все теже запросы что выполняет 1с при обмене
 *
 */

// эти переменные надо заполнить своими значениями
// Скрипт для обена
$importUrl  = 'http://site.ru/bitrix/admin/1c_exchange.php';

// Логин и пароль для подключения к удаленному сайту
$login      = 'exchange';
$password   = 'exchange';

// Загружаемый файл на сайт
$filePath   = $_SERVER['DOCUMENT_ROOT'] . '/import___2476d272-c65f-4df4-8f02-925a600119c4.xml';

// Подключаем библиотеку dist/__autoloader.php или dist/__include.php
include_once ('dist/__autoload.php');

// Создаем объект ImportCatalog
$Import = new \SB\Exchange1C\ImportCatalog($importUrl);

// Подключаем вывод логов в переменную. Также есть возможно в файл FileOutput и консоль ConsoleOutput
$Output = new \SB\Exchange1C\BufferOutput();

// Загружаем в обект $Import объект вывода
$Import->setOutput($Output);

// Авторизация
$Import->checkAuthRequest($login, $password);

// Файл католога, выполняет запросы по загрузки файла на сервер и непосредственно импорт в инфоблоки
$Import->importFile($filePath);

echo '<pre>';
var_dump($Output->getBuffer());
echo '</pre>';


