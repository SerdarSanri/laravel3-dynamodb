<?php



class DynamoDB {
	private static $client = null;
	
	private $table = null;
	private $select = array();
	private $where = array();
	private $error_code = null;
	private $error_message = null;

	
	public static function table($table) {
		require_once __DIR__ . "/aws.phar";	

		$aws = Aws\Common\Aws::factory(array(
			'key' => Config::get('dynamodb::config.key'),
			'secret' => Config::get('dynamodb::config.secret'),
			'region' => Config::get('dynamodb::config.region')
		));
		self::$client = $aws->get('dynamodb');	
	
	
		return new DynamoDB($table);
	}
		public static function normalizeItem( $item ) {
			$ret = array();
			foreach ($item as $k => $v) {
				if (isset($v['S']))
					$ret[$k] = $v['S'];
				if (isset($v['N']))
					$ret[$k] = (int) $v['N'];
			}
			return $ret;
		}
		public static function anormalizeItem( $item ) {
			$ret = array();
			foreach ($item as $k => $v) {
				$type = 'S';
				if (\is_numeric($v))
					$type = 'N';
				
				$ret[$k] = array( $type => (string) $v );
			}
			return $ret;
		}		
		public static function normalizeList($arr) {
			$ret = array();
			foreach ($arr as $k => $a) {
				$ret[$k] = self::normalizeItem($a);
			}
			return $ret;
		}
		public static function anormalizeList( $arr ) {
			$ret = array();
			foreach ($arr as $k => $v) {
				$type = 'S';
				if (\is_numeric($v))
					$type = 'N';
				
				$ret[$k] = array( $type => (string) $v );
			}
			return $ret;
		}
	
	
	
	public function __construct($table) {
		$this->table = $table;
		$this->consistent_read = true;
	}
	
	public function getLastError() {
		return $this->error_message;
	}
	public function select() {
		$this->select = array();
		foreach (func_get_args() as $field )
			$this->select[$field] = '';
			
		return $this;
	}
	public function addSelect($field) {
		$this->select[$field] = '';
		return $this;
	}
	public function where($key,$operation,$value) {
		if ($operation == '=') {
			$this->where[$key] = $value;		
		}
		return $this;
	}	
	
	
	public function consistentRead($cr) {
		$this->consistent_read = $cr;
	}
	
	public function get() {
		$query = array(
			"TableName" => $this->table,
			"Key" => self::anormalizeItem($this->where),
		);
		$query["ConsistentRead"] = $this->consistent_read;
		
		if (count($this->select))
			$query["AttributesToGet"] = array_keys($this->select);

		try {
			$response = self::$client->getItem($query)->toArray();
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			return false;
		}
		
		if (isset($response['Item']))
			return self::normalizeItem($response['Item']);

		return array();	
	}
	public function getItem($key) {

		$query = array(
			"TableName" => $this->table,
			"Key" => self::anormalizeList($key),
		);
		
		$query["ConsistentRead"] = $this->consistent_read;
		if (count($this->select))
			$query["AttributesToGet"] = array_keys($this->select);

		try {
			$response = self::$client->getItem($query)->toArray();
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			return false;
		}		

		if (isset($response['Item']))
			return self::normalizeItem($response['Item']);
			
		return array();
	}
	public function update($attrz) {
		$to_update = array();
		foreach ($attrz as $k => $v) {
			$type = 'S';
			if (is_numeric($v))
				$type = 'N';
				
			$to_update[$k] = array(
				'Value' => array($type => $v),
				'Action' => 'PUT'
			);
		}
		$query = array(
			"TableName" => $this->table,
			"Key" => self::anormalizeItem($this->where),
			"AttributeUpdates" => $to_update,
		);

		try {
			$response = self::$client->UpdateItem($query)->toArray();
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			return false;
		}
		return array();
	}
	
	public function insert($item) {
		$to_insert = self::anormalizeItem($item);

		$query = array(
			"TableName" => $this->table,
			"Item" => $to_insert,
		);

		try {
			$response = self::$client->PutItem($query)->toArray();
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			return false;
		}
		return array();
	}
	
	public function delete() {
		if (count(func_get_args())) {
			// delete attributes
			$to_delete = array();
			foreach (func_get_args() as $v) {
					
				$to_delete[$v] = array(
					'Action' => 'DELETE',
				);
			}
			$query = array(
				"TableName" => $this->table,
				"Key" => self::anormalizeItem($this->where),
				"AttributeUpdates" => $to_delete,
			);
			try {
				$response = self::$client->UpdateItem($query)->toArray();
			} catch ( \Exception $e ) {
				$this->error_message = $e->getMessage();
				return false;
			}
			return array();		

		} else {
			// delete item
			$query = array(
				"TableName" => $this->table,
				"Key" => self::anormalizeItem($this->where),
			);
			try {
				$response = self::$client->DeleteItem($query)->toArray();
			} catch ( \Exception $e ) {
				$this->error_message = $e->getMessage();
				return false;
			}
			return array();
		}
	}
}


?>