<?php

class DateRange {
	var $start;
	var $stop;
	
	function __construct($start=null, $stop=null) {
		
		$this->start = $this->parse($start);
		$this->stop = $this->parse($stop);

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

