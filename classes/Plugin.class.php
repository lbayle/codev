<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

class Plugin extends Model {
	
	private $id;
	private $pathname;
	private $name;
	private $properties;
	private $description;
	
	/**
	 * @param int $id The plugin id
	 * @param resource $details The plugin details
	 * @throws Exception if $id = 0
	 */
	public function __construct($id, $details) {
		if (0 == $id) {
			echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
			$e = new Exception("Creating an Command with id=0 is not allowed.");
			self::$logger->error("EXCEPTION Command constructor: ".$e->getMessage());
			self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
			throw $e;
		}
		
		$this->id = $id;
		$this->initialize($details);
	}
	
	/**
	 * Initialize the plugin
	 * @param resource $row The user details
	 */
	private function initialize($row) {
		if(NULL == $row) {
			$query = "SELECT * FROM `codev_plugin_table` " .
					"WHERE id = $this->id;";
			$result = SqlWrapper::getInstance()->sql_query($query);
			if (!$result) {
				echo "<span style='color:red'>ERROR: Query FAILED</span>";
				exit;
			}
	
			if(SqlWrapper::getInstance()->sql_num_rows($result)) {
				$row = SqlWrapper::getInstance()->sql_fetch_object($result);
			}
		}
	
		if(NULL != $row) {
			$this->pathname = $row->pathname;
			$this->name = $row->name;
			$this->properties = $row->properties;
			$this->description = $row->description;
		} else {
			$this->name = "(unknown $this->id)";
		}
	}
	
	/**
	 * @var Logger The logger
	 */
	private static $logger;
	
	/**
	 * Initialize complex static variables
	 * @static
	 */
	public static function staticInit() {
		self::$logger = Logger::getLogger(__CLASS__);
	}
	
	public static function getPlugins() {
		$query = "SELECT name,description FROM `codev_plugin_table` ";
		$query .= "ORDER BY name;";
		$result = SqlWrapper::getInstance()->sql_query($query);
		if (!$result) {
			return NULL;
		}
		$plugins = array();
		while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
			$plugins[$row->name] = $row->description;
		}
		return $plugins;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getDescription() {
		return $this->description;
	}
}

Plugin::staticInit();

?>