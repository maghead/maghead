<?php
if ( ! extension_loaded('fileutil') && ! function_exists('futil_pathsplit') ) {
    require 'phar://lazy.phar/FileUtil.php';
}
$console = new LazyRecord\Console;
$console->run($argv);
