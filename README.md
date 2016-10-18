#Exchange1C - Библиотека для загрузки файлов 1с в CommerceML из каталога

Реализует стандартный протокол обмена номенклатурой сайта и 1с. Используется когда надо загрузить файлы с фтп или, каталога на сайте. 
Билиотека выполняет все теже запросы что выполняет 1с при обмене. В основном применяется для сайтов на Битрикс, 
но также можно применят на сайтах реализованых на любых других цмс или фреймверках, которые поддерживают протокол обмена с 1с.  

на данный момент реализованы запросы:
* Авторизации 
* Запрос параметров 
* Закгрузка файлов каталога
* Импорт каталога

##Установка 
Клонируем репозиторий или скачиваем архив и подключаем библиотеку
```php
include 'dist/__autoload.php';
// или
include 'dist/__include.php';
```

##Использование
 
Вся логика заложена в класс ImportCatalog
 
###Основные методы класса ImportCatalog

 * `ImportCatalog::checkAuthRequest($user, $password)` - Отправляет запрос на ваторизацию mode=checkauth type=catalog
 * `ImportCatalog::initRequest()` - Запрос параметров обмена  сайтас mode=init type=catalog
 * `ImportCatalog::fileRequest($filename, $data)` - Запрос отправки файла на сайт mode=file type=catalog
 * `ImportCatalog::importRequest($filename)` - Выполняет запрос импорта файла mode=import
 * `ImportCatalog::uploadFile($srcFilePath)` - Пыполняет запросы по отправки файла на сайт. Вызывает методы fileRequest
 * `ImportCatalog::importFile($filePath)` - Выполняет передачу и импорт файла. Вызывает методы importRequest, uploadFile
 * `ImportCatalog::importDirectory($filePath)` - Загружает все файлы, котрые есть в каталоге и подкаталоге как в папке webdata, которую создает 1с
 
Обычно для реализации обмено достаточно вызывать два метода: checkAuthRequest, importFile или importDirectory. Которыделаю все требуемые запросы
 
###Контексты вывода процесса импорта
 
В процессе импорта часто требуется выводить информацию о ходе выполнения загрузки, для этого реализованы три класса контекста вывода.
 
* `BufferOutput` - логирует в переменную
* `ConsoleOutput` - логирует в консоль
* `FileOutput` - логирует в файл

Объект соответстующего класса передается в метод `ImportCatalog::setOutput($Output)`

###Пример
```php
<?php
/**
 * Пример: загрузка файла который лежит на сервере где запускается этот скрипт
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
```
 
