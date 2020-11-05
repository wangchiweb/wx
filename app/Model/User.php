<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'user';//指定表
    protected $primaryKey = 'user_id';//指定主键
    public $timestamps = false;//表明模型是否应该被打上时间戳
}
