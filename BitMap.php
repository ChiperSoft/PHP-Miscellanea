<?php 

class BitMap implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
	
	protected $value = 0;
	protected $indexes = array();
	
	function __construct($v = 0) {
		$this->value = $v;
	}
	
	function __toString() {
		return (string)$this->value;
	}
	
	public function getValue() {
		return $this->value;
	}

	function offset($key) {
		if (is_numeric($key)) {
			return pow(2, $key) ;
		} elseif (isset($this->indexes[$key])) {
			return pow(2, $this->indexes[$key]);
		}
		return 0;
	}
	
	function isTrue($offset) {
		return (boolean)($offset === ($this->value & $offset));
	}

	function toggle($offset) {
		$this->value = $this->value ^ $offset;
		return $this;
	}
	
	function change($offset, $value = true) {
		if ($this->isTrue($offset) !== (boolean)$value) {
			$this->toggle($offset);
		}
		return $this;
	}
	
	function set($key, $value = true) {
		$this->change($this->offset($key), $value);
		return $this;
	}
	
	function get($key) {
		return $this->isTrue($this->offset($key));
	}
	
/**
	properties
*/
	
	public function __get($key) {
		if (isset($this[$key])) {
			return $this[$key];
		}
		throw new OutOfBoundsException("$key is not a valid bit index");
	}
	
	public function __set($key, $value) {
		$this[$key] = $value;
	}
	
/**
	arrayaccess
*/

	public function offsetGet($key){
		return $this->isTrue($this->offset($key));
	}

	public function offsetSet($key, $value){
		$this->change($this->offset($key), (boolean)$value);
	}

	public function offsetExists($key) {
		return (is_numeric($key) || isset($this->indexes[$key]));
	}

	public function offsetUnset($key){
		$this->change($this->offset($key), false);
	}

/**
	Iterator
*/

	// protected $iteration_position;
	// function rewind() {
	// 	$this->iteration_position = 0;
	// }
	// 
	// function current() {
	// 	$indexes = array_values($this->indexes);
	// 	return $this->isTrue($indexes[$this->iteration_position]);
	// }
	// 
	// function key() {
	// 	$indexes = array_keys($this->indexes);
	// 	return $indexes[$this->iteration_position];
	// }
	// 
	// function next() {
	// 	$this->iteration_position++;
	// }
	// 
	// function valid() {
	// 	return $this->iteration_position < count($this->indexes);
	// }
	
	
	public function getIterator() {
		$result = array();
		foreach ($this->indexes as $key=>$index) {
			$result[$key] = $this->isTrue($index);
		}
		
		return new ArrayIterator($result);
	}

/**
	Serializable
*/

	public function serialize() {
		return $this->value;
	}

	public function unserialize($data) {
		$this->value = (int)$data;
	}

/**
	Countable
*/

	function count() {
		return count($this->indexes);
	}
	
}

/*
$o = new BitMap();
$o[0] = true;
$o[4] = true;
echo $o; // 17
*/

