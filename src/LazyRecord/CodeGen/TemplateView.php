<?php
namespace LazyRecord\CodeGen;
use Exception;
use SerializerKit\PhpSerializer;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_Filter_Function;

function twig_var_export($obj)
{
    $ser = new PhpSerializer;
    $ser->return = false;
    return $ser->encode( $obj );
}

class TemplateView
{
    private $loader;
    private $twig;

    public $stash = array();
    public $overwrite = false;

    public function __construct( $dirs , $options = array() )
    {
        $this->loader = new \Twig_Loader_Filesystem( $dirs );
        $this->twig = new \Twig_Environment($this->loader, array(
            'cache' => false,
            'auto_reload' => true,
            'autoescape' => false,
            'debug' => true,
        ) + $options );

        $this->twig->addFilter('export', new \Twig_Filter_Function('\LazyRecord\twig_var_export'));
    }

    public function __set($n,$v)
    {
        $this->stash[ $n ] = $v;
    }

    public function __get($n)
    {
        return $this->stash[ $n ];
    }

    public function renderFile($file)
    {
        $template = $this->twig->loadTemplate($file);
        return $template->render( $this->stash );
    }

    public function generateFile($targetFile,$sourceFile)
    {

        $this->mkdir($targetFile);

        if( ! $this->overwrite && file_exists($targetFile) ) {
            // skip
            return;
        }

        $content = $this->renderFile( $sourceFile );
        if( file_put_contents( $targetFile, $content ) !== false ) {
            return $targetFile;
        }

        throw new Exception("CodeGen failed. $targetFile");
    }

    private function mkdir($path,$mode = 0755)
    {
        $dir = dirname($path);
        if( ! file_exists($dir) )
            mkdir( $dir , $mode, true );
    }


}

