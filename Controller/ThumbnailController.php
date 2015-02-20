<?php

namespace Just\ThumbnailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThumbnailController extends Controller {

    public function thumbnailAction(Request $request) {
        ini_set("gd.jpeg_ignore_warning", 1);
        $img = $request->get('img', null);
        $maxx = $request->get('maxx', '');
        $maxy = $request->get('maxy', '');
        $mode = $request->get('mode', 'normal');
        $imagesrootdir =  $this->container->hasParameter('just_thumbnailbundle.imagesrootdir') ? $this->container->getParameter('just_thumbnailbundle.imagesrootdir') : $this->container->getParameter('kernel.root_dir') . '/../web/';
        $placeholder = $this->container->hasParameter('just_thumbnailbundle.placeholder') ? $this->container->getParameter('just_thumbnailbundle.placeholder') : null;
        $placeholder = $request->get('placeholder', $placeholder);
        
        $imgname = $imagesrootdir . ltrim($img,'/\\');
        if (!is_file($imgname) || !is_readable($imgname)) {
            if (is_null($placeholder)){
                throw new NotFoundHttpException("Image not found");
            }
            $imgname = $placeholder;
        }
        $info = getimagesize($imgname);
        $ctime = filectime($imgname);
        if (!$info) {
            if (is_null($placeholder)){
                throw new NotFoundHttpException("Image not found");
            }
            $imgname = $placeholder;
            $info = getimagesize($imgname);
            $ctime = filectime($imgname);
        }
        $expires = 1 * 24 * 60 * 60;
        $cachename = md5($imgname . $maxx . $maxy . $mode . $ctime);
        if ($cachefile = $this->get('cache')->fetch('JustThumbnailBundle' . $cachename)) {
            //ist bereits im cache:
            $uscachefile = unserialize($cachefile);
            $response = new Response($uscachefile);
            $response->headers->set('Content-Type', 'image/jpeg');
            $response->headers->set('Content-Length', strlen($uscachefile));
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $ctime) . ' GMT');
            $response->headers->set('Cache-Control', 'maxage=' . $expires);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
            return $response;
        } else {
            //thumbnail erstellen:      
            if ($info[2] == 1) { //Original ist ein GIF
                $oimage = @imagecreatefromgif($imgname);
            } else if ($info[2] == 2) { //Original ist ein JPG
                $oimage = @imagecreatefromjpeg($imgname);
            } else if ($info[2] == 3) { //Original ist ein PNG
                $oimage = @imagecreatefrompng($imgname);
            } else if ($info[2] == 6) { //Original ist ein BMP
                $oimage = $this->imagecreatefrombmp($imgname);
            } else {
//                print_r($info);
                throw new HttpException(500, "Error reading image");
                //throw $this->createNotFoundException('The image does not exist or is not readable');
            }
            $ogrx = $info[0];
            $ogry = $info[1];


            if ($mode == 'max') {
                $ngrx = $ogrx;
                $ngry = $ogry;
                if ($maxx != '' && $ngrx > $maxx) {
                    $ngrx = $maxx;
                    $ngry = ($ogry / $ogrx) * $maxx;
                }
                if ($maxy != '' && $ngry > $maxy) {
                    $ngry = $maxy;
                    $ngrx = ($ogrx / $ogry) * $maxy;
                }
            } else {
                if ($maxx == '') $maxx = ($ogrx / $ogry) * $maxy;
                if ($maxy == '') $maxy = ($ogry / $ogrx) * $maxx;
                if ($mode == 'crop') {
                    if ($ogrx / $maxx > $ogry / $maxy) { //Breitformat
                        $ngry = $maxy;
                        $ngrx = ( $ogrx * $maxy ) / $ogry;
                    } else { //Hochformat
                        $ngrx = $maxx;
                        $ngry = ( $ogry * $maxx ) / $ogrx;
                    }
                } else {
                    if ($ogrx / $maxx > $ogry / $maxy) { //Breitformat
                        $ngrx = $maxx;
                        $ngry = ( $ogry * $maxx ) / $ogrx;
                    } else { //Hochformat
                        $ngry = $maxy;
                        $ngrx = ( $ogrx * $maxy ) / $ogry;
                    }
                }
            }
            if ($info[2] == 2 || $info[2] == 3 || $info[0] == 6) { //PNG, JPG
                if ($mode == 'normal' || $mode == 'max') {
                    $image = imagecreatetruecolor($ngrx, $ngry);
                } else {
                    $image = imagecreatetruecolor($maxx, $maxy);
                }
            } else { //GIF
                if ($mode == 'normal' || $mode == 'max') {
                    $image = imagecreate($ngrx, $ngry);
                } else {
                    $image = imagecreate($maxx, $maxy);
                }
            }
            if ($info[2] == 3) { //PNG
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $farbe_body = imagecolorallocate($image, 0, 0, 0);
                $trans = imagecolortransparent($image, $farbe_body);
            } else if ($info[2] == 1) { //GIF
                $farbe_body = imagecolorallocate($image, 255, 255, 255);
            }
            if ($mode == 'normal' || $mode == 'max') {
                imagecopyresampled($image, $oimage, 0, 0, 0, 0, $ngrx, $ngry, $ogrx, $ogry);
            } else if ($mode == 'crop') {
                imagecopyresampled($image, $oimage, -($ngrx - $maxx) / 2, -($ngry - $maxy) / 2, 0, 0, $ngrx, $ngry, $ogrx, $ogry);
            } else if ($mode == 'stretch') {
                imagecopyresampled($image, $oimage, 0, 0, 0, 0, $maxx, $maxy, $ogrx, $ogry);
            }


            ob_start(); // start a new output buffer


            if ($info[2] == 1) { //Original ist ein GIF
                imagegif($image, NULL);
            } else if ($info[2] == 2) { //Original ist ein JPG
                imageinterlace($image, 1);
                imagejpeg($image, NULL, 100);
            } else if ($info[2] == 3) { //Original ist ein PNG
                imagepng($image, NULL);
            } else if ($info[2] == 6) { //Original ist ein BMP
                imageinterlace($image, 1);
                imagejpeg($image, NULL, 100);
            }

            $ImageData = ob_get_contents();
            ob_end_clean(); // stop this output buffer
            $this->get('cache')->save('JustThumbnailBundle' . $cachename, serialize($ImageData));

            $response = new Response($ImageData);

            if ($info[2] == 1) { //Original ist ein GIF
                $response->headers->set('Content-Type', 'image/gif');
            } else if ($info[2] == 2) { //Original ist ein JPG
                $response->headers->set('Content-Type', 'image/jpeg');
            } else if ($info[2] == 3) { //Original ist ein PNG
                $response->headers->set('Content-Type', 'image/png');
            } else if ($info[2] == 6) { //Original ist ein BMP
                $response->headers->set('Content-Type', 'image/jpeg');
            }

            $response->headers->set('Content-Length', strlen($ImageData));
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $ctime) . ' GMT');
            $response->headers->set('Cache-Control', 'maxage=' . $expires);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

            imagedestroy($image);
            imagedestroy($oimage);
            return $response;
        }
    }

    private function imagecreatefrombmp($p_sFile) {
        //    Load the image into a string 
        $file = fopen($p_sFile, "rb");
        $read = fread($file, 10);
        while (!feof($file) && ($read <> "")) $read .= fread($file, 1024);
        $temp = unpack("H*", $read);
        $hex = $temp[1];
        $header = substr($hex, 0, 108);
        //    Process the header 
        //    Structure: http://www.fastgraph.com/help/bmp_header_format.html 
        if (substr($header, 0, 4) == "424d") {
            //    Cut it in parts of 2 bytes 
            $header_parts = str_split($header, 2);
            //    Get the width        4 bytes 
            $width = hexdec($header_parts[19] . $header_parts[18]);
            //    Get the height        4 bytes 
            $height = hexdec($header_parts[23] . $header_parts[22]);
            //    Unset the header params 
            unset($header_parts);
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
        for ($i = 0; $i < $body_size; $i+=3) {
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
}
