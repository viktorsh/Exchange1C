<?php
namespace SB\Exchange1C;

abstract class Output
{
    abstract public function write($text);

    abstract public function writeln($text);

}