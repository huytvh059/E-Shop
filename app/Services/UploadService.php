<?php
declare(strict_types=1);
namespace App\Services;
final class UploadService
{
    public static function images(array $files):array
    {
        $paths=[];$count=count($files['name']??[]);if($count>8){throw new \DomainException('Tối đa 8 ảnh mỗi lần tải.');}
        try{for($i=0;$i<$count;$i++){
            $error=$files['error'][$i]??UPLOAD_ERR_NO_FILE;if($error===UPLOAD_ERR_NO_FILE){continue;}
            if($error!==UPLOAD_ERR_OK||($files['size'][$i]??0)>5*1024*1024){throw new \DomainException('Mỗi ảnh tối đa 5 MB; vui lòng kiểm tra lại tệp.');}
            $tmp=$files['tmp_name'][$i];if(!is_uploaded_file($tmp)){throw new \DomainException('Nguồn ảnh không hợp lệ.');}
            $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);$extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
            $dimensions=getimagesize($tmp);if(!$extension||!$dimensions||$dimensions[0]>8000||$dimensions[1]>8000||$dimensions[0]*$dimensions[1]>20000000){throw new \DomainException('Chỉ nhận JPEG/PNG/WebP hợp lệ, tối đa 20 megapixel.');}
            // Giải mã và mã hóa lại để loại bỏ nội dung đính kèm trong ảnh.
            $image=match($mime){'image/jpeg'=>imagecreatefromjpeg($tmp),'image/png'=>imagecreatefrompng($tmp),'image/webp'=>imagecreatefromwebp($tmp)};
            if(!$image){throw new \DomainException('Không đọc được ảnh.');}
            $relative='/uploads/'.bin2hex(random_bytes(20)).'.'.$extension;$target=BASE_PATH.'/public'.$relative;
            $ok=match($mime){'image/jpeg'=>imagejpeg($image,$target,88),'image/png'=>imagepng($image,$target),'image/webp'=>imagewebp($image,$target,88)};imagedestroy($image);if(!$ok){throw new \RuntimeException('Image write failed');}$paths[]=$relative;
        }}catch(\Throwable $e){foreach($paths as $p){if(is_file(BASE_PATH.'/public'.$p)){unlink(BASE_PATH.'/public'.$p);}}throw $e;}
        return $paths;
    }
}
