<?php
namespace App\StechMedia;

class Enum
{
    const FILE_TYPE_ALLOW = [];
    const ERROR_NO_FILE = [
        'code'=>'ERROR_NO_FILE',//Không tìm thấy
        'msg'=>'Bạn cần chọn file để upload!'
    ];
    const ERROR_KEY_NOTFOUND = [
        'code'=>'ERROR_KEY_NOTFOUND',//Không tìm thấy
        'msg'=>'Không tìm thấy apiKey trên header'
    ];


}