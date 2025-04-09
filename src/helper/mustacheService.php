<?php
namespace TourCMS\OnBoarding\Helper;
use Mustache_Engine;
use Mustache_Autoloader;
use Mustache_Loader_FilesystemLoader;
use TourCMS\OnBoarding\Config\env;
class mustacheService
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
            'loader' => new Mustache_Loader_FilesystemLoader(env::getEnvVariable("BASE_PATH").'src/view/templates'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(env::getEnvVariable("BASE_PATH").'src/view/templates/partials'),
        ));

        if ($this->type == 'template') {
            $tpl = $mustache->loadTemplate($this->template);
        } else {
            $tpl = $mustache->loadPartial($this->template);
        }

        echo $tpl->render($this->context);
    }
}
