<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Tests\Controller;

use Doctrine\Common\Cache\FilesystemCache;
use Just\ThumbnailBundle\Services\ThumbnailService;
use svay\FaceDetector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class ThumbnailServiceTest
 * @package Just\ThumbnailBundle\Tests\Controller
 */
class ThumbnailServiceTest extends KernelTestCase
{
    /**
     * @var Filesystem
     */
    protected $placeholder;

    protected $fixturesDir;

    /**
     * @var ThumbnailService $thumbnailservice
     */
    protected $thumbnailservice;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $assetsDir = __DIR__.'/../Fixtures/assets/';
        $this->placeholder=$assetsDir.'noimage.jpg';
        $rootDir=__DIR__;
        //$cachingService=new ApcuCache();
        $cachingService=new FilesystemCache(__DIR__.'/../../../../../var/cache/test/');
        $cachingService->flushAll();

        $this->thumbnailservice = new ThumbnailService($assetsDir, null /*$this->placeholder*/, 100, $rootDir, $cachingService);
    }

    public function testImagePlaceholder(){
        $response = $this->thumbnailservice->generateResponseForImage('nontxisting.file', 100, 100, 'crop', $this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==200);
    }

    public function testImagePlaceholderNotFound(){
        $response = $this->thumbnailservice->generateResponseForImage('nontxisting.file', 100, 100, 'crop', 'nontxisting.file');
        $this->assertTrue($response->getStatusCode()==404);
    }
    public function testImageKaputt(){
        $response = $this->thumbnailservice->generateResponseForImage('kaputt.jpeg', 100, 100, 'crop', 'nontxisting.file');
        $this->assertTrue($response->getStatusCode()==404);
    }

    public function testCropModePng(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.png', 100, 100, 'crop', '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/png'));
        $this->assertTrue($response->getStatusCode()==200);
    }
    public function testCropModeGif(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.gif', 100, 100, 'crop', '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/gif'));
        $this->assertTrue($response->getStatusCode()==200);
    }
    public function testCropModeJpeg(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.jpeg', 100, 100, 'crop', '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==200);
    }
    public function testCropModeBmp(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.bmp', 100, 100, 'crop', '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==200);
    }
    public function testMaxModeJpeg(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.jpeg', 1000, 50, 'max', '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==200);
    }
    /*public function testFacedetection(){
        $detector = new FaceDetector();
        $result=$detector->faceDetect( __DIR__.'/../Fixtures/assets/catsanddog.jpeg');
        dump($result);
        $face=$detector->getFace();
        $canvas = imagecreatefromjpeg( __DIR__.'/../Fixtures/assets/catsanddog.jpeg');
        $color = imagecolorallocate($canvas, 255, 0, 0); //red

        imagerectangle(
            $canvas,
            $face['x'],
            $face['y'],
            $face['x']+$face['w'],
            $face['y']+ $face['w'],
            $color
        );


        imagejpeg($canvas,__DIR__.'/../../../../../var/cache/test/face.jpg');


    }*/
    public function testCenterCrop(){

        $response = $this->thumbnailservice->generateResponseForImage('catsanddog.jpeg', 150, 600, 'crop', '', 'auto'); //'1200x500');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));

        file_put_contents('/var/www/vhosts/justthumbnailtests/var/cache/test/cropcenter.jpg',$response->getContent());
        $this->assertTrue($response->getStatusCode()==2000);
    }


}
