<?php
namespace LazyRecord;
use Exception;

function twig_var_export($obj)
{
	return var_export($obj,true);
}

class CodeGen
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

		$this->preventFileDir($targetFile);

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

	private function preventFileDir($path,$mode = 0755)
	{
		$dir = dirname($path);
		if( ! file_exists($dir) )
			mkdir( $dir , $mode, true );
	}

}

