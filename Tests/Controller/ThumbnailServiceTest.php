<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Tests\Controller;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Just\ThumbnailBundle\Services\ThumbnailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class ThumbnailServiceTest
 * @package Just\ThumbnailBundle\Tests\Controller
 */
class ThumbnailServiceTest extends KernelTestCase
{

    protected $placeholder;

    protected $fixturesDir;

    /**
     * @var ThumbnailService $thumbnailservice
     */
    protected $thumbnailservice;

    protected $acceptableContentTypes= [
        "text/html",
        "application/xhtml+xml",
        "image/avif",
        "image/webp",
        "image/apng",
        "application/xml",
        "application/signed-exchange",
        "*/*"
    ];

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $assetsDir = __DIR__.'/../Fixtures/assets/';
        $this->placeholder=$assetsDir.'noimage.jpg';
        $rootDir=__DIR__;
        $cachingService=new FilesystemAdapter('',null,__DIR__.'/../../../../../var/cache/test/');
        $cachingService->prune();

        $this->thumbnailservice = new ThumbnailService($assetsDir, null /*$this->placeholder*/, 100, $rootDir, $cachingService);
    }

    public function testImagePlaceholder(){
        $response = $this->thumbnailservice->generateResponseForImage('nontxisting.file', 100, 100, 'crop', $this->acceptableContentTypes, $this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    public function testImagePlaceholderNotFound(){
        $response = $this->thumbnailservice->generateResponseForImage('nontxisting.file', 100, 100, 'crop', $this->acceptableContentTypes, 'nontxisting.file');
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
    }
    public function testImageKaputt(){
        $response = $this->thumbnailservice->generateResponseForImage('kaputt.jpeg', 100, 100, 'crop', $this->acceptableContentTypes, 'nontxisting.file');
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
    }

    public function testCropModePng(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.png', 100, 100, 'crop', $this->acceptableContentTypes, '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/png'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }
    public function testCropModeGif(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.gif', 100, 100, 'crop', $this->acceptableContentTypes, '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/gif'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }
    public function testCropModeJpeg(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.jpeg', 100, 100, 'crop', $this->acceptableContentTypes, '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }
    public function testCropModeBmp(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.bmp', 100, 100, 'crop', $this->acceptableContentTypes, '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }
    public function testMaxModeJpeg(){
        $response = $this->thumbnailservice->generateResponseForImage('cats.jpeg', 1000, 50, 'max', $this->acceptableContentTypes, '');//$this->placeholder);
        $this->assertTrue($response->headers->contains('Content-Type','image/jpeg'));
        $this->assertTrue($response->getStatusCode()==\Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


}
