<?php

use App\Helpers\Morilog\Jalalian;

if (! function_exists('jdate')) {

    /**
     * @param string $str
     * @return Jalalian
     */
    function jdate($str = null)
    {
        return Jalalian::forge($str);
    }
}
