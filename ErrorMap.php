<?php 

class ErrorMap extends BitMap {
	
	protected $indexes = array(
		'error',
		'warning',
		'parse',
		'notice',
		'core_error',
		'core_warning',
		'compile_error',
		'compile_warning',
		'user_error',
		'user_warning',
		'user_notice',
		'strict',
		'recoverable_error',
		'depreciated',
	);
	
	public function __construct($v) {
		$this->indexes = array_flip($this->indexes);
		parent::__construct($v);
	}
	
}

/*
$o = new ErrorMask(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
foreach ($o as $k=>$v) echo $k, ' => ', $v?'true':'false', "\n";
// error => true
// warning => true
// parse => true
// notice => true
// core_error => true
// core_warning => true
// compile_error => true
// compile_warning => true
// user_error => false
// user_warning => false
// user_notice => false
// strict => false
// recoverable_error => false
// depreciated => false
*/