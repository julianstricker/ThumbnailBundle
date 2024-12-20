<?php

namespace Just\ThumbnailBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class JustThumbnailExtension extends AbstractExtension {
    
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [new TwigFunction('thumbnail', [$this, 'thumbnail'],['is_safe' => ['html']])];
    }

    /**
     * @param object $params
     * @return int
     */
    public function thumbnail($params) {
        $extension=$params['extension'] ?? '';
        $maxx=$params['maxx'] ?? '';
        $maxy=$params['maxy'] ?? '';
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

