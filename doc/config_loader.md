Config Loader
=============

    use Maghead\ConfigLoader;
    $loader = ConfigLoader::getInstance();
    $loader->load();
    $loader->init(); // for application

    $loader->initForBuild(); // for command-line application
