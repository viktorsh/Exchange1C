<?php
namespace SB\Exchange1C;

class ConsoleOutput extends Output
{
    public function __construct()
    {

    }

    public function write($text)
    {
        echo $text;
    }

    public function writeln($text)
    {
        echo $text . PHP_EOL;
    }
}