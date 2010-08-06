<?php

class shopCart implements Iterator
{
	private $_id;
	private $_pos = 0;

	public function __construct($id = "not_specified")
	{
		$this->_id = "shopCart_{$id}";
		if (isset($_SESSION[$this->id])) {
			$this->_items = unserialize($_SESSION[$this->id]);
		} else {
			$_SESSION[$this->id] = array();
		}
	}

	public function add($item, $cantidad = 1)
	{
		$id = is_object($item) ? spl_object_hash($item) : $item;
		if (!($obj = $this->get($item))) {
			$obj = new stdClass();
			$obj->item = $item;
		}
		$obj->cantidad+= $cantidad;
		$_SESSION[$this->id][$id] = $obj;
	}

	public function order()
	{
		asort($_SESSION[$this->id]);
	}
	public function exists($item)
	{
		$id = is_object($item) ? spl_object_hash($item) : $item;
		return isset($_SESSION[$this->id][$id]);
	}

	public function remove($item)
	{
		$id = is_object($item) ? spl_object_hash($item) : $item;
		unset($_SESSION[$this->id][$id]);
	}

	public function get($item)
	{
		$id = is_object($item) ? spl_object_hash($item) : $item;
		return ($_SESSION[$this->id][$id]);
	}

	public function getAll()
	{
		return array_keys($_SESSION[$this->id]);
	}

	public function count() { return count($_SESSION[$this->id]); }

	public function clear()
	{
		$_SESSION[$this->id] = array();
		$this->_pos = 0;
	}


	// implementación de iterador
	public function current() 	{ return current($_SESSION[$this->id]); }
	public function key()		{ return $this->_pos; }
	public function valid()		{ return $this->_pos < $this->count(); }
	public function rewind() 	{ reset($_SESSION[$this->id]); $this->_post = 0; }
	public function next() 		{ $this->_pos++; return next($_SESSION[$this->id]); }
	public function dump()		{ var_dump($_SESSION[$this->id]); }
}
