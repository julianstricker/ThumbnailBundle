<?php

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
        $placeholder = $request->get('placeholder', '');
        /** @var ThumbnailService $thumbnailservice */
        $thumbnailservice = $this->get('just_thumbnail');
        $response = $thumbnailservice->generateResponseForImage($img, $maxx, $maxy, $mode, $placeholder);
        return $response;
    }

}
