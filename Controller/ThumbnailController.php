<?php
/**
 * This file is part of the "JustThumbnailBundle" project.
 * Copyright (c) 2016 Julian Stricker.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Just\ThumbnailBundle\Controller;

use Just\ThumbnailBundle\Services\ThumbnailService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThumbnailController extends Controller {

    /**
     * @param Request $request
     * @return Response
     */
    public function thumbnailAction(Request $request) {
        if (1 !== ini_get('gd.jpeg_ignore_warning')) {
            $logger = $this->get('logger');
            $logger->warning('The JustThumbnailBundle needs to have gd.jpeg_ignore_warning set to "1". Please set gd.jpeg_ignore_warning to "1" in your php.ini, and restart your webserver.');
        }

        $img = $request->get('img', null);
        $maxx = $request->get('maxx', '');
        $maxy = $request->get('maxy', '');
        $mode = $request->get('mode', 'normal');
        $quality = intval($request->get('quality', '75'),10);
        $placeholder = $request->get('placeholder', '');
        $center = $request->get('center', '');
        /** @var ThumbnailService $thumbnailservice */
        $thumbnailservice = $this->get('just_thumbnail');
        $response = $thumbnailservice->generateResponseForImage($img, $maxx, $maxy, $mode, $placeholder,$center, $quality);
        return $response;
    }

}
