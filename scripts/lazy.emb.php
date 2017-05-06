<?php
if ( ! extension_loaded('fileutil') && ! function_exists('futil_pathsplit') ) {
    require 'phar://lazy.phar/FileUtil.php';
}
$console = new Maghead\Console\Application;
$console->run($argv);
