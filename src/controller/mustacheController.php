<?php
namespace TourCMS\OnBoarding\Controller;
use Mustache_Engine;
use Mustache_Autoloader;
use Mustache_Loader_FilesystemLoader;
class mustacheController
{
    public $template;
    public $context;
    public $type;
    public function __construct($template, $context, $type)
    {
        $this->template = $template;
        $this->context = $context;
        $this->type = $type;
    }
    public function mustacheRenderer()
    {
        Mustache_Autoloader::register();
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader('/var/www/html/onboardingBelen/src/view/templates'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader('/var/www/html/onboardingBelen/src/view/templates/partials'),
        ));

        if ($this->type == 'template') {
            $tpl = $mustache->loadTemplate($this->template);
        } else {
            $tpl = $mustache->loadPartial($this->template);
        }

        echo $tpl->render($this->context);
    }
}
