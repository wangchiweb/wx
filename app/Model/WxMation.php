<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WxMation extends Model
{
    protected $table = 'wx_mation';//指定表
    protected $primaryKey = 'wx_mation_id';//指定主键
    public $timestamps = false;//表明模型是否应该被打上时间戳
}
