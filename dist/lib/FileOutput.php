<?php
namespace SB\Exchange1C;

class FileOutput extends Output
{
    protected $fh;

    protected $filePath;

    /**
     * Constructor
     *
     * TODO: need to add creating directory
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->fh = fopen($filePath, 'a');
        $this->filePath = $filePath;
    }

    public function write($text)
    {
        fwrite($this->fh, $text);
    }

    public function writeln($text)
    {
        fwrite($this->fh, $text. PHP_EOL);
    }

    public function __destruct()
    {
        fclose($this->fh);
    }
}