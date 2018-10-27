<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FaganChalabizada\EPOS\Facade;

use Illuminate\Support\Facades\Facade;

class EPOS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'EPOS';
    }
}
