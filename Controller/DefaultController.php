<?php

namespace JuSt\ThumbnailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('JuStThumbnailBundle:Default:index.html.twig', array('name' => $name));
    }
}
