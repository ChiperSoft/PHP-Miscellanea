<?php

class DateRange {
	var $start;
	var $stop;
	
	var $formats = array(
		'full'			=>array('F jS, Y',' - ','F jS, Y'),
		'same_year'		=>array('F jS',' - ','F jS, Y'),
		'same_month'	=>array('F jS',' - ','jS, Y'),
		'same_day'		=>array('F jS, Y', '', '')
	);
	
	function __construct($start=null, $stop=null) {
		
		$this->start = $this->parse($start);
		$this->stop = $this->parse($stop);

	}
	
	static function Init($start = null, $stop = null) {
		$o = new self();
		return $o;
	}
	
	private function parse($input) {
		if ($input !== null) {
			if ($input instanceof DateTime) return $input;
	 		else {
				if (is_string($input)) {
					try {
						return new DateTime($input);
					} catch (Exception $e) {
						return null;
					}
				} elseif (is_integer($input) && $input>0) {
					$ret = new DateTime();
					$ret->setTimestamp($input);
					return $ret;
				} else {
					return null;
				}
			}
		}
	}
	
	
	function format($force = false, $formats = array()) {
		$formats = array_merge($this->formats, $formats);
		$out = array();
		if ($this->start && !$this->stop) {
			$start = $this->start;
			$stop = $this->start;
		} elseif (!$this->start && $this->stop) {
			$start = $this->stop;
			$stop = $this->stop;
		} else {
			$start = $this->start;
			$stop = $this->stop;
		}
		
		if ($force) $format = $formats[$force];
		else {
			if ($start->format('Y') != $stop->format('Y')) {
				$format = $formats['full'];
			} elseif ($start->format('Ym') != $stop->format('Ym')) {
				$format = $formats['same_year'];
			} elseif ($start->format('Ymd') != $stop->format('Ymd')) {
				$format = $formats['same_month'];
			} else {
				$format = $formats['same_day'];
			}
		}
		
		$format[0] = $start->format($format[0]);
		$format[2] = $stop->format($format[2]);
		
		return implode('', $format);
	}
	
	
/**
	Static Presets
*/
	
	static function ThisWeek() {
		return new self('today -'.date('w').' days' , 'today +'.(6-date('w')).' days');
	}
	
	static function LastWeek() {
		return new self('today -'.(date('w')+7).' days' , 'today -'.(date('w')+1).' days');
	}
	
	static function ThisMonth() {
		return new self( date('m').'/01/'.date('Y') , date('m').'/01/'.date('Y'). '+1 month -1 day');
	}
	
	static function LastMonth() {
		return new self( (date('m')-1).'/01/'.date('Y') , (date('m')-1).'/01/'.date('Y'). '+1 month -1 day' );
	}

	static function ThisYear() {
		return new self( '01/01/'.date('Y') , '12/31/'.date('Y') );
	}
	
	static function LastYear() {
		return new self( '01/01/'.(date('Y')-1) , '12/31/'.(date('Y')-1) );
	}
	
}

// $dr = DateRange::ThisWeek();
// $dr->stop->modify('+1 month');
// echo $dr->format('same_month');
