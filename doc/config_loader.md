Config Loader
=============

    use LazyRecord\ConfigLoader;
    $loader = ConfigLoader::getInstance();
    $loader->load();
    $loader->init(); // for application

    $loader->initForBuild(); // for command-line application
