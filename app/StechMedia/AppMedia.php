<?php

namespace App\StechMedia;


use App\StechMedia\Enum;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Exception\ImageException;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use League\Flysystem\Filesystem;
use StechCore\Helpers\sBug;
use StechCore\Helpers\sView;
use Symfony\Component\Console\Input\Input;

use function PHPUnit\Framework\returnSelf;

//https://cdn.dxmb.vn/uploads/size/...realpath in home
class AppMedia extends BaseController
{
    private $file_type_allow = [];

    /**
     * @throws \Illuminate\Validation\ValidationException
     * requrire request att
     * Checklist:
     * - Xử lý upload ảnh,
     * - Xử lý upload file
     * - Bổ sung thêm upload image với base64
     */
    public function do_upload()
    {
        //làm nhanh phần upload cho kịp
        $file = request()->file('file');
        if (!$file) {
            return sView::outputJsonError(Enum::ERROR_KEY_NOTFOUND['msg'], Enum::ERROR_KEY_NOTFOUND['code']);
        }
        if ($file->getError()) {
            return sView::outputJsonError($file->getErrorMessage(), $file->getClientOriginalName());
        }

        $apiKey = request()->header('apiKey');
        //todo check thêm việc mã hóa key
        if (isset(config('key')[$apiKey])) {
            $ownerFolder = config('key')[$apiKey]['folder'] . '/';
        } else {
            $ownerFolder = '';
        }
        $sub_folder_user = request()->header('folder');

        if ($sub_folder_user) {
            $uploadFolder       = config('filesystem.root_folder') . $ownerFolder . $sub_folder_user . '/';
        } else {
            $subFolder          = date('Y/md/');
            $uploadFolder       = config('filesystem.root_folder') . $ownerFolder . $subFolder;
        }

        $destinationPath    = $this->public_path($uploadFolder); // upload path
        $_file_name         = $file->getClientOriginalName(); //$file['name']; // renameing image
        $nameFileWithOutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $_file_name);
        $extension          = pathinfo($_file_name, PATHINFO_EXTENSION);
        $fileNew            = date('Hi') . '-' . Helper::convertToSlug($nameFileWithOutExt) . '.' . $extension;
        try {
            //
            if (strpos($file->getClientMimeType(), 'image/') !== false) {
                if ($file->getSize() > 10000000) {
                    $return = [
                        'error'     => true,
                        'success'   => false,
                        'msg'       => 'kích thước file không vượt quá 10MB'
                    ];
                    return response()->json($return, 200);
                }
                $image = Image::make($file->getPathname());

                $width = $image->getWidth();
                if ($width > config('image.big')) {
                    $width = config('image.big');
                }
                $image = $image->resize(400, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('jpeg', 100);

                $disk = Storage::build([
                    'driver' => 'local',
                    'root'   => $this->public_path(),
                ]);
                $disk->put($uploadFolder . $fileNew, $image);
                // $file_webp = self::webpConvert2($uploadFolder . $fileNew);
                // $virtualLink = ['src' => $this->buildVirtualLink($uploadFolder, $fileNew)];
                // $virtualLink_webp = ['src' => $this->buildVirtualLink($uploadFolder, $fileNew).'.webp'];

                $return = [
                    'msg'              => 'Upload file thành công',
                    'file_name'        => $_file_name,
                    'file_type'        => $file->getClientMimeType(),
                    'relative'         => $uploadFolder . $fileNew,
                    'location'         => url($uploadFolder) . '/' . $fileNew, //dành cho ông tinymce
                    'full_size_link'   => url($uploadFolder) . '/' . $fileNew,
                    // 'mini_size_link'   => route('image.mini', $virtualLink),
                    // 'small_size_link'  => route('image.small', $virtualLink),
                    // 'medium_size_link' => route('image.medium', $virtualLink),
                    // 'big_size_link'    => route('image.big', $virtualLink),
                    // 'crop_size_link'   => route('image.crop', array_merge($virtualLink, ['width' => 480, 'height' => 360])),
                    // 'thumb_size_link'  => route('image.thumb', array_merge($virtualLink, ['width' => 480, 'height' => 360])),
                    'origin'           => url($uploadFolder) . '/' . $fileNew,
                    // 'webp'             => $file_webp,
                    // 'webp_thumb_size_link'  => route('image.webp.thumb', array_merge($virtualLink_webp, ['width' => 480, 'height' => 360])),
                ];
            } else {
                if ($file->getSize() > 50000000) {
                    $return = [
                        'error'     => true,
                        'success'   => false,
                        'msg'       => 'kích thước file không vượt quá 50MB'
                    ];
                    return response()->json($return, 200);
                }
                $file->move($destinationPath, $fileNew);
                $return = [
                    'msg'       => 'Upload file thành công',
                    'file_name' => $_file_name,
                    'file_type' => $file->getClientMimeType(),
                    'relative'  => $uploadFolder . $fileNew,
                    'origin'    => url($uploadFolder) . '/' . $fileNew,
                ];
            }
            $return['code']    = 'DONE';
            $return['success'] = true;

            return response()->json($return, 200);
        } catch (\Exception $exception) {
            $return = [
                'file_name' => $_file_name,
                'file_type' => $file->getClientMimeType(),
                'error'     => true,
                'success'   => false,
                'msg'       => $exception->getMessage()
            ];
            pushNotification($exception);
            return response()->json($return, 200);
        }
    }

    public function move_file()
    {
        return response()->json(100, 200);
        try {
            $path = request()->file('path');
            File::move($path, $path);
            return response()->json([], 200);
        } catch (Exception $exception) {
            return response()->json($exception, 500);
        }
    }

    private function buildVirtualLink($folder, $file)
    {
        return str_replace(config('filesystem.root_folder'), '', $folder) . $file;
    }

    public function to_image_small()
    {
        $this->_gen_image('small');
    }

    public function to_image_mini()
    {
        $this->_gen_image('mini');
    }

    public function to_image_medium()
    {
        $this->_gen_image('medium');
    }

    public function to_image_big()
    {
        $this->_gen_image('big');
    }

    private function public_path($path = null)
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }

    private function buildSize(&$width, &$height, &$image)
    {
    }

    public function to_image_thumb()
    {
        $this->_gen_image_size('thumb');
    }

    public function to_image_thumb_webp()
    {
        $this->_gen_image_size_webp('thumb');
    }

    public function to_image_crop()
    {
        $this->_gen_image_size('crop');
    }

    public function test_function()
    {
        $fdgdfgdfg = $this->GUID();
        dd($fdgdfgdfg);
    }
    /**
     * hàm convert
     */
    private function _gen_image_size($type = 'thumb')
    {
        $width   = request('width', null);
        $height  = request('height', null);
        $source  = $this->public_path(config('filesystem.root_folder') . request('src'));
        try {
            $image = Image::make($source);
            $_width = $image->getWidth();
            $_height = $image->getHeight();

            if ($_width < $width) {
                $width = $_width;
            }
            if ($_height < $height) {
                $height = $_height;
            }
            if ($width == 0) {
                $width = null;
            }
            if ($height == 0) {
                $height = null;
            }
            if ('thumb' == $type) {
                $image = $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('jpeg', 95);
            } elseif ('crop' == $type) {
                $image = $image->fit($width, $height, function ($constraint) {
                    $constraint->upsize();
                })->encode('jpeg', 95);
            }

            header('Content-Type: image/jpeg');
            echo $image;
            $image->destroy();
        } catch (ImageException $imageException) {
            self::showNoImage();
        }
    }
    public function resize_image()
    {
       
        $width   = request('width', 0);
        $type  = request('type', null);
        $source  = request('src');
        // check xem type la gi
        $path ='';
      
        try {
            if($type == 'webp'){
                $ext = explode('.',$source);
                $path_file_name = $ext[0];
                $ext = $ext[count($ext)-1];
               
                $path = $this->public_path("$path_file_name.webp");
                $path_name ="/$path_file_name.webp";
                if($width){
                    $path_name ="/$path_file_name-$width.webp";
                   $path = $this->public_path("$path_file_name-$width.webp");
                }
                if(file_exists($path)){
                    return redirect($path_name,301);
                }
    
            }else{ // neu khong co type
               
                 $ext = explode('.',$source);
                 $path_file_name = $ext[0];
                 $ext = $ext[count($ext)-1];
                
                 $path = $this->public_path("$path_file_name.$ext");
                 $path_name ="/$path_file_name.$ext";
                 if($width){
                    $path = $this->public_path("$path_file_name-$width.$ext");
                    $path_name ="/$path_file_name-$width.$ext";
                 }
                
                 if(file_exists($path)){
                    // dd($path_name);
                    return redirect($path_name,301);
                 }

            }
            $source  = $this->public_path(request('src'));
           
            $image = Image::make($source);

            $_width = $image->getWidth();

            if ($_width < $width || $width == 0) {
                $width = $_width;
            }

            if($type == 'webp'){
                $image = $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('webp',100);
                $disk = Storage::build([
                    'driver' => 'local',
                    'root'   => '/'
                ]);
                $disk->put($path, $image);
                header('Content-Type: image/webp');
                echo $image;
                $image->destroy();
            }else{
                $image = $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('jpeg',100);
                $disk = Storage::build([
                    'driver' => 'local',
                    'root'   => '/'
                ]);
                $disk->put($path, $image);
                header('Content-Type: image/jpeg');
                echo $image;
                $image->destroy();
            }
         
        } catch (ImageException $imageException) {
            self::showNoImage();
        }
    }
    private function _gen_image_size_webp($type = 'thumb')
    {
        $width   = request('width', null);
        $height  = request('height', null);
        $source  = $this->public_path(config('filesystem.root_folder') . request('src'));
        try {
            $image = Image::make($source);
            $_width = $image->getWidth();
            $_height = $image->getHeight();

            if ($_width < $width) {
                $width = $_width;
            }
            if ($_height < $height) {
                $height = $_height;
            }
            if ($width == 0) {
                $width = null;
            }
            if ($height == 0) {
                $height = null;
            }
            if ('thumb' == $type) {
                $image = $image->resize($width, $height)->encode('webp', 95);
            } elseif ('crop' == $type) {
                $image = $image->fit($width, $height)->encode('webp', 95);
            }
            header('Content-Type: image/webp');
            echo $image;
            $image->destroy();
        } catch (ImageException $imageException) {
            self::showNoImage();
        }
    }


    function _gen_image($size = 'big')
    {
        @ini_set("memory_limit", "-1");
        @set_time_limit(0);
        $source = request('s');
        $source = base64_decode($source);
        if (!$source) {
            $source = $this->public_path(config('filesystem.root_folder') . request('src'));
        } else {
            $sourceObj = parse_url($source);
        }
        try {
            $image = Image::make($source);
            $width = $image->getWidth();
            if ($width > config('image.' . $size)) {
                $width = config('image.' . $size);
            }
            $image = $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('jpeg', 95);
            header('Content-Type: image/jpeg');
            echo $image;
            $image->destroy();
        } catch (ImageException $exception) {
            self::showNoImage();
        }

    }
    static function showNoImage()
    {
        $content = base64_decode('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');
        header('Content-Type: image/jpeg');
        echo  $content;
        die();
    }
    function webpConvert2($file, $compression_quality = 80)
    {
        // check if file exists
        if (!file_exists($file)) {
            return false;
        }
      
        $file_type = exif_imagetype($file);
        //https://www.php.net/manual/en/function.exif-imagetype.php
        //exif_imagetype($file);
        // 1    IMAGETYPE_GIF
        // 2    IMAGETYPE_JPEG
        // 3    IMAGETYPE_PNG
        // 6    IMAGETYPE_BMP
        // 15   IMAGETYPE_WBMP
        // 16   IMAGETYPE_XBM
        $output_file =  $file . '.webp';
        if (file_exists($output_file)) {
            return $output_file;
        }
        if (function_exists('imagewebp')) {
            switch ($file_type) {
                case '1': //IMAGETYPE_GIF
                    $image = imagecreatefromgif($file);
                    break;
                case '2': //IMAGETYPE_JPEG
                    $image = imagecreatefromjpeg($file);
                    break;
                case '3': //IMAGETYPE_PNG
                        $image = imagecreatefrompng($file);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;
                case '6': // IMAGETYPE_BMP
                    $image = imagecreatefrombmp($file);
                    break;
                case '15': //IMAGETYPE_Webp
                return false;
                    break;
                case '16': //IMAGETYPE_XBM
                    $image = imagecreatefromxbm($file);
                    break;
                default:
                    return false;
            }
            // Save the image
            $result = imagewebp($image, $output_file, $compression_quality);
            if (false === $result) {
                return false;
            }
            // Free up memory
            imagedestroy($image);
            return $output_file;
        } elseif (class_exists('Imagick')) {
            $image = new \Imagick();
            $image->readImage($file);
            if ($file_type === "3") {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($compression_quality);
                $image->setOption('webp:lossless', 'true');
            }
            $image->writeImage($output_file);
            return $output_file;
        }
        return false;
    }
    function GUID()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }
}
