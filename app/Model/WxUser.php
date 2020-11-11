<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WxUser extends Model
{
    protected $table = 'wx_user';//指定表
    protected $primaryKey = 'wx_user_id';//指定主键
    public $timestamps = false;//表明模型是否应该被打上时间戳
}
