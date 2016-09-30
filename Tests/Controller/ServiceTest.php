<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ServiceTest
 * @package Just\ThumbnailBundle\Tests\Controller
 */
class ServiceTest extends KernelTestCase
{


    public function testService()
    {
        self::bootKernel();
        $thumbnailservice = static::$kernel->getContainer()->get('just_thumbnail');
        $this->assertInstanceOf('Just\ThumbnailBundle\Services\ThumbnailService', $thumbnailservice);
    }

}
