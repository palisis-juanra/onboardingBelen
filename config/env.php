<?php
namespace TourCMS\OnBoarding\Config;
use Dotenv\Dotenv;
class env
{
    static function setEnviroment()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

# Marketplace
    static function getEnvVariable($varName)
    {
        env::setEnviroment();
        $envVariable = $_ENV[$varName];
        return $envVariable;
    }
}


