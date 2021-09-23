<?php

namespace App;

class Utility
{

    public static function packagistVersionFilter(string $input)
    {
        return str_replace(['-'], ['.'], $input);
    }
}
