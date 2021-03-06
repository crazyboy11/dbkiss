<?php
/**
* @package   Warp Theme Framework
* @file      option.php
* @version   6.0.7
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright 2007 - 2011 YOOtheme GmbH
* @license   YOOtheme Proprietary Use License (http://www.yootheme.com/license)
*/

/*
	Class: OptionWarpHelper
		Option helper class, store option data
*/
class OptionWarpHelper extends WarpHelper {

    /*
		Variable: file
			Option file.
    */
	protected $file;

    /*
		Variable: data
			Option data.
    */
	protected $data;

	/*
		Function: __construct
			Class Constructor.
	*/
	public function __construct() {
		parent::__construct();
		
		// load data
		$this->file = $this['system']->cache_path.sprintf('/%s.php', basename($this['path']->path('template:')));
		$this->data = $this['data']->create(file_exists($this->file) ? file_get_contents($this->file) : null);
	}

	/*
		Function: get
			Get a value from data

		Parameters:
			$name - String
			$default - Mixed
		Returns:
			Mixed
	*/
	public function get($name, $default = null) {
		return $this->data->get($name, $default);
	}

 	/*
		Function: set
			Set a value

		Parameters:
			$name - String
			$value - Mixed

		Returns:
			Void
	*/
	public function set($name, $value) {
		$this->data->set($name, $value);
		@file_put_contents($this->file, (string) $this->data);
	}

}