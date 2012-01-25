<?php 

/**

	GOOD TEST NUMBERS:
	4005519200000004 (Visa)
	5555555555554444 (MasterCard)
	378282246310005 (American Express)
	6011111111111117 (Discover)
	
	BAD TEST NUMBERS:
	4000111111111115 (Visa)
	5105105105105100 (MasterCard)
	378734493671000 (American Express)
	6011000990139424 (Discover)
	

	CVV Value	CVV Response Code
	200			N (does not match)
	201			U (not verified)
	301			S (issuer does not participate)
	blank		I (not provided)
	anything 	M (matches)

*/


class CreditCard {
	var $number;
	var $cvv;
	var $date;
	var $name;
	var $address;
	var $city;
	var $state;
	var $zip;
	var $country;
	
	function __construct($o=null) {
		if ($o) {
			if (is_array($o)) {
				
				$this->number = $o['number'];
				$this->cvv = $o['cvv'];
				$this->name = max($o['name'], $o['cardholder_name']);
				$this->address = max($o['address'], $o['street_address']);
				$this->city = $o['city'];
				$this->state = $o['state'];
				$this->zip = $o['zip'];
				$this->country = $o['country']?$o['country']:'US';
				
				$d = max($o['date'], $o['expires'], $o['expiration'], $o['expiration_date']);
				if ($d) {
					if ($d instanceof DateTime) $this->date = $d;
					elseif (is_string($d)) $this->date = new DateTime($d);
					elseif (is_integer($d)) {
						$this->date = new DateTime();
						$this->date->setTimestamp($d);
					}
					elseif (is_array($d)) {
						$this->date = new DateTime();
						$this->date->setDate($d['year'], $d['month'], 1);
					}
				} elseif (isset($o['expiration_month']) && isset($o['expiration_year'])) {
					$this->date = new DateTime();
					$this->date->setDate($o['expiration_year'], $o['expiration_month'], 1);
				}
				
			} else {
				$this->number = $o;
			}
		}
	}
	
	public function __get($name) {
		switch ($name) {
		case 'valid':
			return $this->checkLuhn();
		case 'type':
			return $this->detectType();
		case 'expired':
			return ($this->date < new DateTime());
		case 'masked':
			return str_pad(substr($this->number,-4),strlen($this->number),'*',STR_PAD_LEFT);
		}
	}
	

	private function checkLuhn() {
		$len = strlen($this->number);
		$par = $len % 2;
		
		if (!$len) return false;
		
		$tot = 0;
		for ($i=0; $i < $len; $i++) {
			$digit=(int)$this->number[$i];
			if ($i % 2 == $par) {
				$digit = $digit * 2;
				if ($digit>9) $digit -= 9;
			}
			$tot += $digit;
		}
		return ($tot % 10)==0?true:false;
	}
	
	
	private function detectType() { 
		if(!$this->number) return false;

		if(preg_match("/^5[1-5]\d{14}$/", $this->number)) return 'mastercard';
		else if(preg_match("/^4(\d{12}|\d{15})$/", $this->number)) return 'visa';
		else if(preg_match("/^3[47]\d{13}$/", $this->number)) return 'amex';
		else if(preg_match("/^[300-305]\d{11}$/", $this->number) || preg_match("/^3[68]\d{12}$/", $this->number)) return 'dinners';
		else if(preg_match("/^6011\d{12}$/", $this->number)) return 'discover';
		else if(preg_match("/^2(014|149)\d{11}$/", $this->number)) return 'enroute';
		else if(preg_match("/^3\d{15}$/", $this->number) || preg_match("/^(2131|1800)\d{11}$/", $this->number)) return 'jcb';

		return false;
	} 

}
