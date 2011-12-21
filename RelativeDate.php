<?php 

class RelativeDate {
	static function Format($time, $never='') {
		if (is_string($time)) $time = strtotime($time);
		if ($time<=0) return $never;
		
	    $today = strtotime(date('M j, Y'));
	    $reldays = ($time - $today)/86400;
	    if ($reldays >= 0 && $reldays < 1) {
	        return 'Today';
	    } else if ($reldays >= 1 && $reldays < 2) {
	        return 'Tomorrow';
	    } else if ($reldays >= -1 && $reldays < 0) {
	        return 'Yesterday';
	    }
	    if (abs($reldays) < 12) {
	        if ($reldays > 0) {
	            $reldays = floor($reldays);
	            return 'In ' . $reldays . ' Day' . ($reldays != 1 ? 's' : '');
	        } else {
	            $reldays = abs(floor($reldays));
	            return $reldays . ' Day'  . ($reldays != 1 ? 's' : '') . ' Ago';
	        }
	    }
	    if (abs($reldays) < 28) {
	        if ($reldays > 0) {
	            $reldays = floor($reldays/7);
	            return 'In ' . $reldays . ' Week' . ($reldays != 1 ? 's' : '');
	        } else {
	            $reldays = abs(floor($reldays/7));
	            return $reldays . ' Week'  . ($reldays != 1 ? 's' : '') . ' Ago';
	        }
	    }
	    if (abs($reldays) < 182) {
	        return date('l, M j',$time ? $time : time());
	    } else {
	        return date('l, M j, Y',$time ? $time : time());
	    }
	}
	
	static function Range($start, $stop) {
		if (is_string($start)) $start = strtotime($start);
		if (is_string($stop)) $stop = strtotime($stop);
		$date = array();
		
		if ($start>0) { //start date is defined, so event has a date.
			$date[] = date('F j', $start);
			if ($stop>0 && date('md',$start)!=date('md', $stop)) { //end date is different than start date, so multiday event
				$date[] = '-';
				if (date('m',$start)!=date('m', $stop)) $date[] = date('F ', $stop); //end date is a different month, so add month name
				$date[] = date('d', $stop); //end day
			}
			$date[] = date(', Y', $start); //year
		
			if ((int)date('Hi', $start)>0) { //start time is defined, so output time
				$date[] = '; ';
				$date[] = date('g:ia', $start);
				
				if ((int)date('Hi', $stop)>0 && (int)date('Hi', $stop)>(int)date('Hi', $start)) $date[] = " to ".date('g:ia', $stop);
			}
		}
		return implode('', $date);
	}
}
