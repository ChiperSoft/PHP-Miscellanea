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
		return new self($start, $stop);
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
	
	
	function difference($expanded = false, $format_future = 'in %s', $format_past = '%s ago') {
		//inspired by http://www.zachstronaut.com/posts/2009/01/20/php-relative-date-time-string.html
		
		$start = $this->start;
		if (!$start || $start->format('Y') < 1900) return null; //invalid date entered
		
		$stop = $this->stop;
		if ($stop === null) $stop = new DateTime();
		
		$future = $start > $stop;
		
		$etime = abs($stop->format('U') - $start->format('U'));
		
		if ($etime < 1) return 'now';
		
		$a = array(
			12 * 30 * 24 * 60 * 60  =>  'year',
			30 * 24 * 60 * 60		=>  'month',
			 7 * 24 * 60 * 60		=>	'week',
			24 * 60 * 60			=>  'day',
			60 * 60					=>  'hour',
			60						=>  'minute',
			 1						=>  'second'
		);
		
		$result = array();
		foreach ($a as $secs => $str) {
			$d = $etime / $secs;
			if ($d >= 1) {
				if ($expanded) {
					$r = floor($d);
					$result[$str] =  $r . ' ' . $str . ($r > 1 ? 's' : '');
					$etime -= $r * $secs;
				} else {
					$r = round($d);
					$result[] =  $r . ' ' . $str . ($r > 1 ? 's' : '');
					break;
				}
			}
		}
		
		$result = implode(', ', $result);
		return sprintf($future ? $format_future : $format_past, $result);
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

/*
$dr = DateRange::ThisWeek();
$dr->stop->modify('+1 month');
echo $dr->format('same_month');
*/

/*
$dr = new DateRange('2012-03-18 00:00:00');
echo $dr->difference();
*/