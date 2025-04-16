<?php
namespace TourCMS\OnBoarding\Config;
use Dotenv\Dotenv;
class env
{
    // This function sets the environment variables
    static function setEnvironment()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    // This function sets the environment variables
    static function getEnvVariable($varName)
    {
        env::setEnvironment();
        $envVariable = $_ENV[$varName];
        return $envVariable;
    }
}


