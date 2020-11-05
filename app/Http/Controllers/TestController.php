<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\User;

class TestController extends Controller{
    public function test1(){
        $res=User::get()->toArray();
        dd($res);
    }
    public function test2(){
        echo phpinfo();
    }
}
