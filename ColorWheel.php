<?php

class ColorWheel {
	var $hues;
	var $values;
	
	function ColorWheel($totalColors, $saturation=100, $brightness=100) {
		$distance = 360 / max(5,$totalColors);
		for ($i=0;$i<$totalColors;$i++) {
			$this->hues[$i] = $distance * $i;
			
			$color = $this->hsbToRgb($this->hues[$i], $saturation, $brightness);
			$this->values[$i] = sprintf('%02X%02X%02X', $color['red'], $color['green'], $color['blue']);
		}
	}
	
	function Fetch($index, $saturation=100, $brightness=100) {
		$color = $this->hsbToRgb($hues[$index], $saturation, $brightness);
		return '#'.dechex($color['red']).dechex($color['green']).dechex($color['blue']);
	}
	
	private function hsbToRgb($hue, $saturation, $brightness) {
	    $hue = $this->significantRound($hue, 3);
	    if ($hue < 0 || $hue > 360) {
	        throw new LengthException('Argument $hue is not a number between 0 and 360');
	    }
	    $hue = $hue == 360 ? 0 : $hue;
	    $saturation = $this->significantRound($saturation, 3);
	    if ($saturation < 0 || $saturation > 100) {
	        throw new LengthException('Argument $saturation is not a number between 0 and 100');
	    }
	    $brightness = $this->significantRound($brightness, 3);
	    if ($brightness < 0 || $brightness > 100) {
	        throw new LengthException('Argument $brightness is not a number between 0 and 100.');
	    }
	    $hexBrightness = (int) round($brightness * 2.55);
	    if ($saturation == 0) {
	        return array('red' => $hexBrightness, 'green' => $hexBrightness, 'blue' => $hexBrightness);
	    }
	    $Hi = floor($hue / 60);
	    $f = $hue / 60 - $Hi;
	    $p = (int) round($brightness * (100 - $saturation) * .0255);
	    $q = (int) round($brightness * (100 - $f * $saturation) * .0255);
	    $t = (int) round($brightness * (100 - (1 - $f) * $saturation) * .0255);
	    switch ($Hi) {
	        case 0:
	            return array('red' => $hexBrightness, 'green' => $t, 'blue' => $p);
	        case 1:
	            return array('red' => $q, 'green' => $hexBrightness, 'blue' => $p);
	        case 2:
	            return array('red' => $p, 'green' => $hexBrightness, 'blue' => $t);
	        case 3:
	            return array('red' => $p, 'green' => $q, 'blue' => $hexBrightness);
	        case 4:
	            return array('red' => $t, 'green' => $p, 'blue' => $hexBrightness);
	        case 5:
	            return array('red' => $hexBrightness, 'green' => $p, 'blue' => $q);
	    }
	    return false;
	}
	
	function significantRound($number, $precision) {
	    if (!is_numeric($number)) {
	        throw new InvalidArgumentException('Argument $number must be an number.');
	    }
	    if (!is_int($precision)) {
	        throw new InvalidArgumentException('Argument $precision must be an integer.');
	    }
	    return round($number, $precision - strlen(floor($number)));
	}
}



?>