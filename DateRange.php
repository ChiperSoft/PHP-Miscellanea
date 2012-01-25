<?php

class DateRange {
	var $start;
	var $end;
	
	static function ThisWeek() {
		$o = new self();
		$o->start = new DateTime('today -'.date('w').' days');
		$o->end = new DateTime('today +'.(6-date('w')).' days');
		return $o;
	}
	
	static function LastWeek() {
		$o = new self();
		$o->start = new DateTime('today -'.(date('w')+7).' days');
		$o->end = new DateTime('today -'.(date('w')+1).' days');
		return $o;
	}
	
	static function ThisMonth() {
		$o = new self();
		$o->start = new DateTime( date('m').'/01/'.date('Y') );
		$o->end = new DateTime(date('m').'/01/'.date('Y'). '+1 month -1 day');
		return $o;
	}
	
	static function LastMonth() {
		$o = new self();
		$o->start = new DateTime( (date('m')-1).'/01/'.date('Y') );
		$o->end = new DateTime( (date('m')-1).'/01/'.date('Y'). '+1 month -1 day' );
		return $o;
	}

	static function ThisYear() {
		$o = new self();
		$o->start = new DateTime( '01/01/'.date('Y') );
		$o->end = new DateTime( '12/31/'.date('Y') );
		return $o;
	}
	
	static function LastYear() {
		$o = new self();
		$o->start = new DateTime( '01/01/'.(date('Y')-1) );
		$o->end = new DateTime( '12/31/'.(date('Y')-1) );
		return $o;
	}
	
}
