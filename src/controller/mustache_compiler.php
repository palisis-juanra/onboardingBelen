<?php
require("vendor/autoload.php");


function mustache_renderer($template,$context,$type) 
{
    Mustache_Autoloader::register();
    $mustache = new Mustache_Engine(array(
        'loader' => new Mustache_Loader_FilesystemLoader('src/view/templates'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader('src/view/templates/partials'),
    ));

    if ($type=='template') {
        $tpl = $mustache->loadTemplate($template); 
    } else {
        $tpl = $mustache->loadPartial($template);
    }
    
    
    echo $tpl->render($context);
}