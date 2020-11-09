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
    public function test3(){
        echo '<pre>';
        print_r($_GET); 
        echo '<pre>';
    }
    public function test4(){
        echo '<pre>';
        print_r($_POST); 
        echo '<pre>';
    }
}
