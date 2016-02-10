<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Just\ThumbnailBundle\Twig;

class JustThumbnailExtension extends \Twig_Extension {
    
    protected $router;

    public function __construct($router)
    {
        $this->router = $router;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('thumbnail', array($this, 'thumbnail'),array('is_safe' => array('html')))
        );
    }

    /**
     * @param object $params
     * @return int
     */
    public function thumbnail($params) {
        $extension=isset($params['extension']) ? $params['extension'] : '';
        $maxx=isset($params['maxx']) ? $params['maxx'] : '';
        $maxy=isset($params['maxy']) ? $params['maxy'] : '';
        $route='just_thumbnail_';
        if ($maxx!='' && $maxy!=''){
            $route='just_thumbnail';
        }else if ($maxx!=''){
             $route='just_thumbnail_x';
        }else if ($maxy!=''){
             $route='just_thumbnail_y';
        }
        if ($extension!='') $route.='_extension';
        $uri = $this->router->generate($route, $params);
        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'just_thumbnail_twig_extension';
    }
}

