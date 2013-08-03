<?php



class DynamoDB {
	private static $client = null;
	private $table = null;

	
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

	
	
	
	public function __construct($table) {
		$this->table = $table;
		$this->consistent_read = true;
	}
	public function get($key) {
		$response = self::$client->getItem(array(
			"TableName" => $this->table,
			"Key" => self::anormalizeItem($key),
			"ConsistentRead" => $this->consistent_read,
		))->toArray();	
		return self::normalizeItem($response['Item']);
	}
}


?>