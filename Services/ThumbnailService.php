<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Services;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Cache\CacheItem;
use Imagick;
use ImagickPixel;
use svay\FaceDetector;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Response;


class ThumbnailService
{

    private $debug=false;
    private $acceptableContentTypes;

    /**
     * ThumbnailService constructor.
     * @param string $imagesrootdir
     * @param string $placeholder
     * @param int $expiretime
     * @param string $projectDir
     * @param AdapterInterface $cachingService
     */

    public function __construct(private $imagesrootdir, private $placeholder, private $expiretime, private $projectDir, private AdapterInterface $cachingService, private $svgexportcommand=null)
    {
    }

    /**
     * @param string $img
     * @param string $maxxstring
     * @param string $maxystring
     * @param string $mode
     * @param string $placeholderparam
     * @param string $center '', 'auto' or '[(int) x]x[(int) y]'
     * @param int $quality
     * @param string $type 'gif','jpg','bmp','png','webp', or null for same output file type as input file
     * @return Response
     */
    public function generateResponseForImage($img, $maxxstring, $maxystring, $mode, $acceptableContentTypes, $placeholderparam='',$center='', $quality=75, $type=null)
    {
        $imagesrootdir = $this->imagesrootdir ?? $this->projectDir . '/public/';
        $placeholder = $placeholderparam != '' ? $placeholderparam : ($this->placeholder ?? null);
        $imgname = $imagesrootdir . ltrim($img, '/\\');
        $this->acceptableContentTypes=$acceptableContentTypes;
        if (!is_file($imgname) || !is_readable($imgname)) {
            if (is_null($placeholder)) return $this->createErrorResponse(404, "Image not found");
            $imgname = $placeholder;
        }
        try{
            $info = $this->getImageSize($imgname);
        }catch(\Exception $e){
            return $this->createErrorResponse(404, "Image and placeholder not found");
        }

        if (!$info) {
            if (is_null($placeholder)) return $this->createErrorResponse(404, "Image not readable");
            $imgname = $placeholder;
            try{
                $info = $this->getImageSize($imgname);
            }catch(\Exception $e){
                return $this->createErrorResponse(404, "Image not readable and placeholder not found");
            }
        }

        if($type=='' &&  $info[2] !== 50 && in_array('image/webp',$this->acceptableContentTypes)){
            $type='webp';
        }

        $ctime = filectime($imgname);
        $cachename = md5($imgname .'_'. $maxxstring .'_'. $maxystring .'_'. $mode . '_' . $center .'_'. $quality.'_'.$type.'_'. $ctime);
        $maxx=$maxxstring=='' ? null : intval($maxxstring,10);
        $maxy=$maxystring=='' ? null : intval($maxystring,10);
        //ist bereits im cache?
        if ($this->imageIsCached($cachename)) return  $this->getResponseForCachedImage($cachename, $ctime, $info);
        //thumbnail erstellen:
        try {
            $oimage = $this->getOriginalImage($imgname, $info);
        }catch(\Exception $e){
            return $this->createErrorResponse(500, $e->getMessage());
        }
        $image = $this->getImage($oimage, $info, $mode, $maxx, $maxy, $center);
        if ($image === false) return $this->createErrorResponse(404, "Image not readable");
        $response = $this->createResponseForImage($image, $info, $cachename, $ctime, $quality,$type);
        if ($image) imagedestroy($image);
        if ($oimage) imagedestroy($oimage);
        return $response;
    }

    /**
     * Create a new error response
     * @param int $statuscode
     * @param string $message
     * @return Response
     */
    private function createErrorResponse($statuscode, $message){
        $response=new Response();
        $response->setStatusCode($statuscode);
        $response->setContent($message);
        return $response;
    }

    /**
     * Create new response for image resource
     *
     * @param resource $image The original image resource
     * @param array $info Image info from getimagesize
     * @param string $cachename Name to use for storing new image in cache
     * @param int $ctime Timestamp modifiactiontime
     * @param int $quality 75, 0..100
     * @return Response
     */
    private function createResponseForImage($image, $info, $cachename, $ctime, $quality = 75, $type=null)
    {
        $expires = $this->expiretime ?? 1 * 24 * 60 * 60;
        ob_start(); // start a new output buffer
        if ($type == 'gif' || ($type==null && $info[2] == 1)) { //Original ist ein GIF
            imagegif($image, NULL);
        } else if ($type == 'jpg' || ($type==null && $info[2] == 2)) { //Original ist ein JPG
            imageinterlace($image, 1);
            imagejpeg($image, NULL, $quality);
        } else if ($type == 'png' || ($type==null && $info[2] == 3)) { //Original ist ein PNG
            imagepng($image, NULL, round($quality/10));
        } else if ($type == 'svg' || ($type==null && $info[2] == 50)) { //Original ist ein SVG
            imagepng($image, NULL, round($quality/10));
        } else if ($type == 'bmp' || ($type==null && $info[2] == 6)) { //Original ist ein BMP
            imageinterlace($image, 1);
            imagejpeg($image, NULL, $quality);
        } else { //Original ist ein WebP
            imagewebp($image, NULL, round($quality));
        }
        $ImageData = ob_get_contents();
        ob_end_clean(); // stop this output buffer
        $cacheItem = $this->cachingService->getItem('JustThumbnailBundle' . $cachename);
        $cacheItem->set(serialize($ImageData));
        $cacheItem->expiresAfter($this->expiretime);
        $this->cachingService->save($cacheItem);
        $response = new Response($ImageData);
        if ($type == 'gif' || ($type==null && $info[2] == 1)) { //Original ist ein GIF
            $response->headers->set('Content-Type', 'image/gif');
        } else if ($type == 'jpg' || ($type==null && $info[2] == 2)) { //Original ist ein JPG
            $response->headers->set('Content-Type', 'image/jpeg');
        } else if ($type == 'png' || ($type==null && $info[2] == 3)) { //Original ist ein PNG
            $response->headers->set('Content-Type', 'image/png');
        } else if ($type == 'svg' || ($type==null && $info[2] == 50)) { //Original ist ein SVG
            $response->headers->set('Content-Type', 'image/png');
        } else if ($type == 'bmp' || ($type==null && $info[2] == 6)) { //Original ist ein BMP
            $response->headers->set('Content-Type', 'image/jpeg');
        } else {
            $response->headers->set('Content-Type', 'image/webp');
        }
        $etag=md5($ImageData);
        $response->headers->set('Content-Length', strlen($ImageData));
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $ctime) . ' GMT');
        $response->headers->set('Cache-Control', 'max-age=' . $expires);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $ctime + $expires) . ' GMT');
        $response->headers->set('Etag', $etag);
        return $response;
    }

    /**
     * Get image with the new size
     *
     * @param resource $oimage The original image resource
     * @param array $info Image info from getimagesize
     * @param string $mode Resizing mode ("normal", "crop", "stretch" or "max")
     * @param int|null $maxx New image maximal width
     * @param int|null $maxy New image maximal height
     * @param string $center '', 'auto' or '[(int) x]x[(int) y]'
     * @return resource
     */
    private function getImage($oimage, $info, $mode, $maxx, $maxy, $center='')
    {
        $ogrx = $info[0];
        $ogry = $info[1];
        $imagesizes = $this->getNewimagesizes($mode, $maxx, $maxy, $ogrx, $ogry);
        $ngrx = $imagesizes['ngrx'];
        $ngry = $imagesizes['ngry'];
        $maxx = $imagesizes['maxx'];
        $maxy = $imagesizes['maxy'];

        if ($info[2] == 2 || $info[2] == 3 || $info[2] == 6 || $info[2]== 50) { //PNG, JPG, BMP, SVG
            if ($mode == 'normal' || $mode == 'max') {
                $image = imagecreatetruecolor($ngrx, $ngry);
            } else {
                $image = imagecreatetruecolor($maxx, $maxy);
                $resizedimage = imagecreatetruecolor(round($ngrx), round($ngry));
                if($info[2]==3){
                    imagesavealpha($image, true);
                    imagealphablending($image, false);
                    imagesavealpha($resizedimage, true);
                    imagealphablending($resizedimage, false);
                }
                imagecopyresampled($resizedimage, $oimage, 0, 0, 0, 0, $ngrx, $ngry, $ogrx, $ogry);
            }
        } else { //GIF
            if ($mode == 'normal' || $mode == 'max') {
                $image = imagecreate($ngrx, $ngry);
            } else {
                $image = imagecreate($maxx, $maxy);
                $resizedimage = imagecreate(round($ngrx), round($ngry));
                imagecopyresampled($resizedimage, $oimage, 0, 0, 0, 0, $ngrx, $ngry, $ogrx, $ogry);
            }
        }
        if ($info[2] == 3 || $info[2] == 50) { //PNG, SVG
            imagesavealpha($image, true);
            imagealphablending($image, false);
        }
        if ($mode == 'normal' || $mode == 'max') {
            imagecopyresampled($image, $oimage, 0, 0, 0, 0, $ngrx, $ngry, $ogrx, $ogry);
        } else if ($mode == 'crop') {
            if (isset($resizedimage)) {
                $dstxy = $this->findCenterForImage($resizedimage, $center, $imagesizes, $ogrx, $ogry);
                imagecopyresampled($image, $resizedimage, $dstxy['x'], $dstxy['y'], 0, 0, $ngrx, $ngry, $ngrx, $ngry);
            }
        } else if ($mode == 'stretch') {
            imagecopyresampled($image, $oimage, 0, 0, 0, 0, $maxx, $maxy, $ogrx, $ogry);
        }
        return $image;
    }

    /**
     * @param resource $oimage
     * @param string $center '', 'auto' or '[int],[int]' koordinaten des Mittelpunktes auf dem Originalbild
     * @param array $imagesizes
     * @param $ogrx
     * @param $ogry
     * @return array x,y Verschiebung des verkleinerten Bildes.
     */
    private function findCenterForImage($oimage, $center, $imagesizes, $ogrx, $ogry){
        $factor = $imagesizes['ngrx'] / $ogrx;
        $centerx = $ogrx / 2;
        $centery = $ogry / 2;
        if($center=='auto') { //face detection...
            $detector = new FaceDetector();
            $result = $detector->faceDetect($oimage);
            if ($result) {
                $face = $detector->getFace();

                $centerx=round(($face['x'] + $face['w'] / 2)/$factor);
                $centery=round(($face['y'] + $face['w'] / 2)/$factor);
                if ($this->debug) {
                    $color = imagecolorallocate($oimage, 255, 0, 0); //red

                    imagefilledrectangle(
                        $oimage,
                        $face['x'],
                        $face['y'],
                        $face['x'] + $face['w'],
                        $face['y'] + $face['w'],
                        $color
                    );
                }

            } else {
                $center = '';
            }
        }
        if($center!='auto'){
            if ($center == '') {
                $centerx = $ogrx / 2;
                $centery = $ogry / 2;
            } else {
                $centersplit = explode(',', $center);
                if (count($centersplit)==2){
                    $centerx = (float)$centersplit[0];
                    $centery = (float)$centersplit[1];
                }

            }
        }
        //centerx/centery = Mittelpunkt des Originalbildes (errechnet oder übergeben)
        $x= -round($centerx* $factor - $imagesizes['maxx']/2);
        $y= -round($centery* $factor - $imagesizes['maxy']/2);

        if ($x>0) $x=0;
        if ($y>0) $y=0;
        if ($x<-$imagesizes['ngrx']+$imagesizes['maxx']) $x=-$imagesizes['ngrx']+$imagesizes['maxx'];
        if ($y<-$imagesizes['ngry']+$imagesizes['maxy']) $y=-$imagesizes['ngry']+$imagesizes['maxy'];


        return ['x'=>$x,'y'=>$y];



    }

    /**
     * Calculate new image size
     *
     * @param string $mode  Resizing mode ("normal", "crop", "stretch" or "max")
     * @param int|null $maxx     New image maximal width
     * @param int|null $maxy     New image maximal height
     * @param int $ogrx     Original image width
     * @param int $ogry     Original image height
     * @return array
     */
    private function getNewimagesizes($mode, $maxx, $maxy, $ogrx, $ogry)
    {
        if ($mode == 'max') {
            $ngrx = $ogrx;
            $ngry = $ogry;
            if ($maxx !== null && $ngrx > $maxx) {
                $ngrx = $maxx;
                $ngry = ($ogry / $ogrx) * $maxx;
            }
            if ($maxy !== null && $ngry > $maxy) {
                $ngry = $maxy;
                $ngrx = ($ogrx / $ogry) * $maxy;
            }
        } else {
            if ($maxx === null) $maxx = ($ogrx / $ogry) * $maxy;
            if ($maxy === null) $maxy = ($ogry / $ogrx) * $maxx;
            if ($mode == 'crop') {
                if ($ogrx / $maxx > $ogry / $maxy) { //Breitformat
                    $ngry = $maxy;
                    $ngrx = ($ogrx * $maxy) / $ogry;
                } else { //Hochformat
                    $ngrx = $maxx;
                    $ngry = ($ogry * $maxx) / $ogrx;
                }
            } else {
                if ($ogrx / $maxx > $ogry / $maxy) { //Breitformat
                    $ngrx = $maxx;
                    $ngry = ($ogry * $maxx) / $ogrx;
                } else { //Hochformat
                    $ngry = $maxy;
                    $ngrx = ($ogrx * $maxy) / $ogry;
                }
            }
        }
        return ['ngrx' => $ngrx, 'ngry' => $ngry, 'maxx' => $maxx, 'maxy' => $maxy];
    }

    private function getOriginalImage($imgname, &$info)
    {
        if ($info[2] == 1) { //Original ist ein GIF
            try {
                $oimage = imagecreatefromgif($imgname);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else if ($info[2] == 2) { //Original ist ein JPG
            try {
                $oimage = imagecreatefromjpeg($imgname);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else if ($info[2] == 3) { //Original ist ein PNG
            try {
                $oimage = imagecreatefrompng($imgname);
                imagesavealpha($oimage, true);
                imagealphablending($oimage, false);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else if ($info[2] == 6) { //Original ist ein BMP
            try {
                $oimage = $this->imagecreatefrombmp($imgname);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else if ($info[2] == 50) { //Original ist ein svg
            if($this->svgexportcommand!==null) {
                ini_set('display_errors', 1);
                error_reporting(E_ALL);
                $temp_file = tempnam(sys_get_temp_dir(), 'ThumbnailService');
                //dump($this->svgexportcommand.' '.$imgname.' '.$temp_file);
                copy($imgname, $temp_file.'.svg');
                $lastline='';
                try {

                    $lastline = shell_exec($this->svgexportcommand.' '.$temp_file.'.svg '.$temp_file);
                    //dump($lastline);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage().', Output:'.$lastline);
                }
                $imgname=$temp_file;
            }



            try {
                $oimage = $this->imagecreatefromsvg($imgname);
            } catch (\Exception $e) {
                if(isset($temp_file)){
                    unlink($temp_file.'.svg');
                    unlink($temp_file);
                }

                throw new \Exception($e->getMessage());
            }
            if(isset($temp_file)){
                unlink($temp_file.'.svg');
                unlink($temp_file);
            }
            $info[0]=imagesx($oimage);
            $info[1]=imagesy($oimage);
            $info[4]='width="'.$info[0].'" height="'.$info[1].'"';
        } else {
            throw new \Exception("Error reading image");
        }
        return $oimage;
    }

    /**
     * Check if image exists in cache
     *
     * @param string $cachename
     * @return bool
     */
    private function imageIsCached($cachename){
        return $this->cachingService->hasItem('JustThumbnailBundle' . $cachename);
    }

    /**
     * Get response for cached image
     *
     * @param string $cachename
     * @param $ctime
     * @param array $info
     * @return bool|Response
     */
    private function getResponseForCachedImage($cachename, $ctime, $info)
    {
        $expires = $this->expiretime ?? 1 * 24 * 60 * 60;
        if ($cachefile = $this->cachingService->getItem('JustThumbnailBundle' . $cachename)) {
            //ist bereits im cache:
            $uscachefile = unserialize($cachefile->get());
            $info=getimagesizefromstring($uscachefile);
            $response = new Response($uscachefile);
            if ($info[2] == 1) { //Original ist ein GIF
                $response->headers->set('Content-Type', 'image/gif');
            } else if ($info[2] == 2) { //Original ist ein JPG
                $response->headers->set('Content-Type', 'image/jpeg');
            } else if ($info[2] == 3) { //Original ist ein PNG
                $response->headers->set('Content-Type', 'image/png');
            } else if ($info[2] == 6) { //Original ist ein BMP
                $response->headers->set('Content-Type', 'image/jpeg');
            } else if ($info[2] == 50) { //Original ist ein SVG
                $response->headers->set('Content-Type', 'image/png');
            } else { //Ist ein Webp
                $response->headers->set('Content-Type', 'image/webp');
            }
            $etag=md5($uscachefile);
            $response->headers->set('Content-Length', strlen($uscachefile));
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $ctime) . ' GMT');
            $response->headers->set('Cache-Control', 'max-age=' . $expires);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
            $response->headers->set('Etag', $etag);
            return $response;
        }
        return false;
    }

    /**
     * Create Image-Object from Windows-BMP-Image-File
     * http://php.net/manual/de/function.imagecreatefromwbmp.php
     *
     * @param string $p_sFile
     * @return resource
     * @throws \Exception
     */
    private function imagecreatefrombmp($p_sFile)
    {
        //    Load the image into a string
        $file = fopen($p_sFile, "rb");
        $read = fread($file, 10);
        while (!feof($file) && ($read <> "")) $read .= fread($file, 1024);
        $temp = unpack("H*", $read);
        $hex = $temp[1];
        $header = substr($hex, 0, 108);
        //    Process the header
        //    Structure: http://www.fastgraph.com/help/bmp_header_format.html
        if (str_starts_with($header, "424d")) {
            //    Cut it in parts of 2 bytes
            $header_parts = str_split($header, 2);
            //    Get the width        4 bytes
            $width = hexdec($header_parts[19] . $header_parts[18]);
            //    Get the height        4 bytes
            $height = hexdec($header_parts[23] . $header_parts[22]);
            //    Unset the header params
            unset($header_parts);
        }else {
            throw new \Exception('Image not readable.');
        }
        //    Define starting X and Y
        $x = 0;
        $y = 1;
        //    Create newimage
        $image = imagecreatetruecolor($width, $height);
        //    Grab the body from the image
        $body = substr($hex, 108);
        //    Calculate if padding at the end-line is needed
        //    Divided by two to keep overview.
        //    1 byte = 2 HEX-chars
        $body_size = (strlen($body) / 2);
        $header_size = ($width * $height);
        //    Use end-line padding? Only when needed
        $usePadding = ($body_size > ($header_size * 3) + 4);
        //    Using a for-loop with index-calculation instaid of str_split to avoid large memory consumption
        //    Calculate the next DWORD-position in the body
        for ($i = 0; $i < $body_size; $i += 3) {
            //    Calculate line-ending and padding
            if ($x >= $width) {
                //    If padding needed, ignore image-padding
                //    Shift i to the ending of the current 32-bit-block
                if ($usePadding) $i += $width % 4;
                //    Reset horizontal position
                $x = 0;
                //    Raise the height-position (bottom-up)
                $y++;
                //    Reached the image-height? Break the for-loop
                if ($y > $height) break;
            }
            //    Calculation of the RGB-pixel (defined as BGR in image-data)
            //    Define $i_pos as absolute position in the body
            $i_pos = $i * 2;
            $r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
            $g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
            $b = hexdec($body[$i_pos] . $body[$i_pos + 1]);
            //    Calculate and draw the pixel
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $height - $y, $color);
            //    Raise the horizontal position
            $x++;
        }
        //    Unset the body / free the memory
        unset($body);
        //    Return image-object
        return $image;
    }

    /**
     * Create Image-Object from SVG-Image-File
     *
     * @param string $p_sFile
     * @return resource
     * @throws \Exception
     */
    private function imagecreatefromsvg($filePath){
        $im = new Imagick();
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        $svg = file_get_contents($filePath);
        $im->readImageBlob($svg);
        //$im->transparentPaintImage("white", 0, 0, false);
        $im->setImageFormat("png32");
        //$im->writeImage('/var/www/vhosts/ehotelier/var/cache/svgimage.png');
        $img=imagecreatefromstring($im->getImagesBlob());
        imagealphablending($img, false);
        imagesavealpha($img, true);
        //imagepng($img,'/var/www/vhosts/ehotelier/var/cache/svgimage2.png' );
        $im->clear();
        $im->destroy();
        return $img;
    }

    /**
     * @throws \Exception
     */
    private function getImageSize($filePath){
        if($filePath==null){
            throw new \Exception('filePath is NULL');
        }
        $imageSize=getimagesize($filePath);
        if(!$imageSize && $this->isSvg($filePath)){
            $svgXML = simplexml_load_file($filePath);
            [$originX, $originY, $relWidth, $relHeight] = explode(' ', $svgXML['viewBox']);
            if($relWidth && $relHeight){
                $imageSize=[
                    $relWidth,
                    $relHeight,
                    50, //50 ist frei, wird als svg-Typ gesetzt
                    'width="'.$relWidth.'" height="'.$relHeight.'"',
                    'mime'=> 'image/svg+xml'
                ];
            }
        }
        return $imageSize;
    }
    public function isSvg($filePath )
    {
        return 'image/svg+xml' === mime_content_type(realpath($filePath)) || 'image/svg' === mime_content_type(realpath($filePath));
    }

}
