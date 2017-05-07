<?php
if (! extension_loaded('fileutil') && ! function_exists('futil_pathsplit') ) {
    require 'phar://maghead.phar/FileUtil.php';
}
use Maghead\Console\Application;
$app = new Application;
$app->run($argv);
