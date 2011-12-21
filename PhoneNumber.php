<?php

class PhoneNumber {
	var $value;
	var $valid;
	
	var $nation;
	var $areacode;
	var $exchange;
	var $number;
	var $extension;
	
	var $formatted;
	
	function __construct($input) {
		if (preg_match('/x\s?(\d+)$/', $input, $m)) {
			$this->extension = (int)$m[1];
			$input = str_replace($m[0],'',$input);
		}
		
		if (is_array($input)) $input = implode('',$input);
		$input = preg_replace('/[^0-9]/','',$input);
		if ($input[0]=='1') $input = substr($input,1);
		
		$len = strlen($input);
		
		if (!$len) return; //result is now empty, stop processing.
		
		$this->valid = ( $len >= 10 || $len == 7 );
		
		$this->value = $input;
		
		//work backwards, since first elements can be optional
		if ($len>10) $this->nation 	= substr($input, 0, $len-10);
		if ($len> 7) $this->areacode = substr($input, -10,3);
		$this->exchange = substr($input, -7,3);
		$this->number   = substr($input, -4);
		
		if ($this->valid) {
			$this->formatted = '';
			if ($this->nation) $this->formatted .= "{$this->nation} ";
			if ($this->areacode) $this->formatted .= "({$this->areacode}) ";
			$this->formatted .= "{$this->exchange}-{$this->number}";
			if ($this->extension) $this->formatted .= " x{$this->extension}";
		} else $this->formatted = $value;
	}
	
	static function Format($input) {
		$p = new self($input);
		if ($p->valid) return $p->formatted;
		else return $p->value;
	}

	static function Sanitize($input) {
		$p = new self($input);
		return $p->value;
	}
	
}
