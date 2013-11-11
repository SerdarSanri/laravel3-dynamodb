<?php



class DynamoDB {
	private static $client = null;
	
	private $table = null;
	private $select = array();
	private $where = array();
	private $whereOther = array();
	private $consistent_read = true;
	private $limit = null;
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
				if (\gettype($v) == 'integer')
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
				if (\gettype($v) == 'integer')
					$type = 'N';
				
				$ret[$k] = array( $type => (string) $v );
			}
			return $ret;
		}
		private function anormalizeQuery () {
			$anormal = array();
			foreach ($this->where as $key => $value) {
				$anormal[$key] = array(
					'ComparisonOperator' => 'EQ',
					'AttributeValueList' => self::anormalizeItem(array($value)) ,
				);
			}
			foreach ($this->whereOther as $key => $value ) {
				$whereVal = array();
				$whereVal[$value['type']] = $value['value'];
				$anormal[$key] = array(
					'ComparisonOperator' => $value['type'],
					'AttributeValueList' => is_array($value['value']) ? array(
						array('S' => $value['value'][0]),
						array('S' => $value['value'][1])
					): self::anormalizeItem(array($value['value'])),	
				);
			}
			return $anormal;
		}
	
	
	
	public function __construct($table) {
		$this->table = $table;
		$this->consistent_read = true;
	}
	
	public function resetAfterQuery() {
		$this->table = null;
		$this->select = array();
		$this->where = array();
		$this->whereOther = array();
		$this->consistent_read = true;
		$this->limit = null;
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
		} else {
			if ($operation == '<=') $operation = 'LE';
			if ($operation == '<') $operation = 'LT';
			if ($operation == '>=') $operation = 'GE';
			if ($operation == '>') $operation = 'GT';

			$this->whereOther[$key] = array(
				'type' => $operation,
				'value'=> $value,
			);
		}
		return $this;
	}	
	
	public function take($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function consistentRead($cr) {
		$this->consistent_read = $cr;
		return $this;
	}
	
	public function get() {
		if (count($this->whereOther))
			return $this->query();
	
		return $this->getItem($this->where);
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
			$this->resetAfterQuery();
			$this->error_message = $e->getMessage();
			return false;
		}		

		$this->resetAfterQuery();

		if (isset($response['Item']))
			return self::normalizeItem($response['Item']);
			
		return array();
	}
	public function query() {

		$query = array(
			"TableName" => $this->table,
			"KeyConditions" => $this->anormalizeQuery(),
		);

		$query["ConsistentRead"] = $this->consistent_read;
		
		if ($this->limit !== null)
			$query['Limit'] = $this->limit;

		//if (this.direction !== null) {
		//	if (this.direction == 'DESC')
		//		thisQuery['ScanIndexForward'] = false;
		//}
		//if ( this.index !== null ) {
		//	thisQuery['IndexName'] = this.index;
		//}
		//if ( this.ExclusiveStartKey !== null ) {
		//	thisQuery['ExclusiveStartKey'] = this.ExclusiveStartKey;
		//}
		if (count($this->select))
			$query["AttributesToGet"] = array_keys($this->select);

		try {
			$response = self::$client->Query($query)->toArray();
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			return false;
		}	

		
		//$this.LastEvaluatedKey = data.LastEvaluatedKey === undefined ? null : data.LastEvaluatedKey;
		if (isset($response['Items']))
			return self::normalizeList($response['Items']);
			
		return array();	
	}
	public function update($attrz) {
		$to_update = array();
		foreach ($attrz as $k => $v) {
			$type = 'S';
			if (\gettype($v) == 'integer')
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
	
	function scan() {	
		try {
			$response = self::$client->scan(array(
				"TableName" => $this->table,
			));
		} catch ( \Exception $e ) {
			die($e->getMessage());
		//	$this->error_message = $e->getMessage();
			return false;
		}
		return self::normalizeList($response['Items']);
	}
}


?>