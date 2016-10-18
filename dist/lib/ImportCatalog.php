<?php
/**
 * Created by PhpStorm.
 * User: viktor
 * Date: 12.10.2016
 * Time: 12:01
 */

namespace SB\Exchange1C;

/**
 * Класс реализует протокол обмена номенклатурой 1с и сайта
 * Осуществляет загрузку наменклатуры.
 *
 * Class ImportCatalog1C
 * @package SB
 */
class ImportCatalog
{
    protected $authCookie = '';

    protected $handlerUrl;

    protected $user;

    protected $password;

    protected $arInitResponse = [];

    public $timeout = 180;

    public $responseCharset  = 'WINDOWS-1251';

    /**
     * @var Output
     */
    protected $Output;

    function __construct($handlerUrl)
    {
        $this->handlerUrl = $handlerUrl;
    }

    public function setOutput(Output $Output)
    {
        $this->Output = $Output;
    }

    /**
     * Посылвает запрос на сайт
     *
     * @param $url
     * @param array $arOptions - парамктры курл
     *
     * @return string
     */
    protected function request($url, $arOptions = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        
        if ($this->authCookie)
            curl_setopt($curl, CURLOPT_COOKIE, $this->authCookie);

        curl_setopt($curl, CURLOPT_URL, $url);

        foreach($arOptions as $opt=>$value)
        {
            $opt = (int)$opt;

            curl_setopt($curl, $opt, $value);
        }

        $this->log('Request: ' . $url);

        $response = curl_exec($curl);

        if ($response === false)
            throw new \RuntimeException(curl_error($curl));

        curl_close($curl);

        // конвертируем если коировки различаются
        if (ini_get('default_charset') != $this->responseCharset)
        {
            $response = iconv($this->responseCharset, ini_get('default_charset'), $response);
        }

        $this->log('Response: >>>>>>>>>>>>>>>>>>>>>>');
        $this->log($response);
        $this->log('================================');

        return $response;
    }

    /**
     * Отправляет запрос на ваторизацию mode=checkauth type=catalog
     *
     * @param $user
     * @param $password
     *
     * @throws \RuntimeException
     */
    function checkAuthRequest($user, $password)
    {
        $this->user = $user;
        $this->password = $password;

        //Авторизация
        $url = $this->handlerUrl . '?mode=checkauth&type=catalog';

        $response = $this->request($url, [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $user . ':' . $password
            ]);

        if(!$response)
            throw new \RuntimeException('Auth error');

        $arResponseBody = explode("\n", $response);

        if((!trim($arResponseBody[0]) == 'success' && trim($arResponseBody[1]) == 'PHPSESSID'))
            throw new \RuntimeException('Bad response for checkout request');


        $this->authCookie = "PHPSESSID={$arResponseBody[2]}";
    }

    /**
     * Запрос параметров обмена  сайтас
     *
     * @return string
     */
    function initRequest()
    {
        $url = $this->handlerUrl . '?mode=init&type=catalog';

        $response = $this->request($url);

        $arResponse = [];
        parse_str(str_replace("\n", '&',$response), $arResponse);

        if (!(array_key_exists('zip', $arResponse) && array_key_exists('zip', $arResponse)))
            throw new \RuntimeException('Bad response for init request');

        $arResponse['file_limit'] = (int)$arResponse['file_limit'];

        if (!$arResponse['file_limit'])
            throw new \RuntimeException('Bad response for init request');

        $this->arInitResponse = $arResponse;

        return $response;
    }

    /**
     * Запрос отправки файла на сайт
     *
     * @param $srcFilePath
     * @param bool $filename
     *
     * @return string
     */
    function fileRequest($filename, $data)
    {
        if (!$filename)
            throw new \LogicException('Filename can not be empty');

        $url = $this->handlerUrl . "?type=catalog&mode=file&filename=$filename";

        $response = $this->request($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
        ]);

        if (strpos($response, 'success') !== 0)
            throw new \RuntimeException('Bad response for file request');

        return $response;
    }

    /**
     * Выполняет запрос импорта файла mode=import
     *
     * @return string
     */
    function importRequest($filename)
    {
        if (!$filename)
            throw new \InvalidArgumentException('Filename can not be empty');

        $url = $this->handlerUrl . "?mode=import&type=catalog&filename=$filename";

        $response = $this->request($url);

        $arResponse = explode("\n", $response);

        $status = array_shift($arResponse);

        if ($status != 'success' && $status != 'progress')
            throw new \RuntimeException('Bad response for file request');

        $arResponse['status'] = $status;
        $arResponse['message'] = array_shift($arResponse);
        $arResponse['response'] = $response;

        return $arResponse;
    }


    /**
     * Пыполняет запросы по отправки файла на сайт
     *
     * @param $srcFilePath
     */
    function uploadFile($srcFilePath)
    {
        if (!file_exists($srcFilePath))
            throw new \LogicException('Uploading file does not exist');

        if (!$this->arInitResponse)
            throw new \LogicException('You must send request init before request file');

        $arPathInfo = pathinfo($srcFilePath);

        $filename = $arPathInfo['basename'];

        if ($this->arInitResponse['zip'] == 'yes')
        {
            $zipFilePath = "{$arPathInfo['dirname']}/{$arPathInfo['filename']}.zip";

            $Zip = new \ZipArchive;
            $Zip->open($zipFilePath, \ZIPARCHIVE::CREATE);
            $Zip->addFile($srcFilePath, $arPathInfo['basename']);
            $Zip->close();

            $srcFilePath = $zipFilePath;

            $filename = pathinfo($srcFilePath, PATHINFO_BASENAME);
        }

        $size = filesize($srcFilePath);

        $fileLimit = $this->arInitResponse['file_limit'];
        $countPart = ceil($size / $fileLimit);

        $fh = fopen($srcFilePath, 'r');

        for($i=0;$i<$countPart;$i++)
        {
            $data = fread($fh, $fileLimit);

            $this->fileRequest($filename, $data);
        }

        fclose($fh);

        if (file_exists($zipFilePath))
            unlink($zipFilePath);
    }


    /**
     * Выполняет передачу и импорт файла
     *
     * @param $filePath
     */
    function importFile($filePath)
    {
        // запрос параметров type=init
        $this->initRequest();

        // загрузка файла type=file
        $this->uploadFile($filePath);

        $filename = pathinfo($filePath, PATHINFO_BASENAME);

        while(1)
        {
            $arResponse = $this->importRequest($filename);

            if ($arResponse['status'] == 'success')
                break;
        }
    }

    /**
     * Загружает все файлы, котрые есть в каталоге и подкаталоге как в папке webdata, которую создает 1с
     *
     * @param $dirPath
     */
    function importDirectory($dirPath)
    {
        $dirPath = rtrim($dirPath, DIRECTORY_SEPARATOR);

        $arDirs = array($dirPath);

        $arFiles = array();

        while(1)
        {
            if (empty($arDirs))
                break;

            $dirPath = array_shift($arDirs);

            $arGlob = glob($dirPath . DIRECTORY_SEPARATOR . '*');

            foreach($arGlob as $path)
            {
                if (is_dir($path))
                {
                    $arDirs[] = $path;
                }
                else
                {
                    $arFiles[] = $path;
                }
            }
        }

        foreach($arFiles as $filePath)
        {
            $this->importFile($filePath);
        }
    }

    /**
     * Выводит строку в лог(файл, консоль, переменная)
     *
     * @param $text
     */
    protected function log($text)
    {
        if (!$this->Output)
            return;

        $this->Output->writeln($text);
    }
}