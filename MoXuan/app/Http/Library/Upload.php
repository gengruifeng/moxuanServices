<?php
namespace App\Http\Library;

class Upload
{

    public static function uploadDo($file)
    {
        $newName = rand (10000,99999).'-'.time ().substr ($file['name'], strpos ($file['name'], '.'));
        $path = PUBLIC_PATH.'/day_record_img/'.$newName;
        move_uploaded_file ($file['tmp_name'], $path);
        return MOXUAN_URL_SUFFIX.'/day_record_img/'.$newName;
    }
}