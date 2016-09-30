<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Tests\Controller;

use Doctrine\Common\Cache\FilesystemCache;
use Just\ThumbnailBundle\Services\ThumbnailService;
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


}
