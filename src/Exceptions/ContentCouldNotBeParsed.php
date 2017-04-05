<?php

namespace DevIT\MelodiMedia\Exceptions;

class ContentCouldNotBeParsed extends \Exception
{
    function __construct(\Exception $e)
    {
        $this->message = $e->getMessage();
    }
}
