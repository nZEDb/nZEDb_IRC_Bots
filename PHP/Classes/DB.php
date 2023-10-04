<?php /** @noinspection SpellCheckingInspection */

namespace nzedb\db;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class for handling connection to database (MySQL or PostgreSQL) using PDO.
 *
 * The class extends PDO, thereby exposing all of PDO's functionality directly
 * without the need to wrap each and every method here.
 *
 * Exceptions are caught and displayed to the user.
 * Properties are explicitly created, so IDEs can offer autocompletion for them.
 */
class DB extends PDO
{
	/**
	 * @var PDO|null Instance of PDO class.
	 */
	private static ?PDO $pdo = null;

	/**
	 * @var string Lower-cased name of DBMS in use.
	 */
	private string $DbSystem;

    /**
	 * @var array    Options passed into the constructor or defaulted.
	 */
	private array $opts;

	/**
	 * Constructor. Sets up all necessary properties. Instantiates a PDO object
	 * if needed, otherwise returns the current one.
     * @noinspection PhpMissingParentConstructorInspection
     */
	public function __construct(array $options = array())
	{
		$defaults = array(
			'checkVersion'	=> false,
			'createDb'		=> false, // create dbname if it does not exist?
			'dbhost'		=> defined('DB_HOST') ? DB_HOST : '',
			'dbname' 		=> defined('DB_NAME') ? DB_NAME : '',
			'dbpass' 		=> defined('DB_PASSWORD') ? DB_PASSWORD : '',
			'dbport'		=> defined('DB_PORT') ? DB_PORT : '',
			'dbsock'		=> defined('DB_SOCKET') ? DB_SOCKET : '',
			'dbtype'		=> 'mysql',
			'dbuser' 		=> defined('DB_USER') ? DB_USER : ''
		);
		$this->opts = $options + $defaults;

		if (!empty($this->opts['dbtype'])) {
			$this->DbSystem = strtolower($this->opts['dbtype']);
		}

		if (!(self::$pdo instanceof PDO)) {
			$this->initialiseDatabase();
		}

		return self::$pdo;
	}

	public function checkDbExists($name = null): bool
    {
		if (empty($name)) {
			$name = $this->opts['dbname'];
		}

		$found  = false;
		$tables = self::getTableList();
		foreach ($tables as $table) {
			if ($table['Database'] == $name) {
				//var_dump($tables);
				$found = true;
				break;
			}
		}
		return $found;
	}

	public function getTableList(): false|array
    {
		$result = self::$pdo->query('SHOW DATABASES');
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
	 * Init PDO instance.
	 */
	private function initialiseDatabase(): void
    {
		if (!empty($this->opts['dbsock'])) {
			$dsn = $this->DbSystem . ':unix_socket=' . $this->opts['dbsock'];
		} else {
			$dsn = $this->DbSystem . ':host=' . $this->opts['dbhost'];
			if (!empty($this->opts['dbport'])) {
				$dsn .= ';port=' . $this->opts['dbport'];
			}
		}
		$dsn .= ';charset=utf8';

		$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 180);
		if ($this->DbSystem === 'mysql') {
			$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
			$options[PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
		}

		$dsn1 = $dsn;
		// removed try/catch to let the instantiating code handle the problem (Install for
		// instance can output a message that connecting failed.
		self::$pdo = new PDO($dsn1, $this->opts['dbuser'], $this->opts['dbpass'], $options);

		$found = self::checkDbExists();
		if ($this->opts['dbtype'] === 'pgsql' && !$found) {
			throw new RuntimeException('Could not find your database: ' . $this->opts['dbname'] .
										', please see Install.txt for instructions on how to create a database.', 1);
		}

		if ($this->opts['createDb']) {
			if ($found) {
				try {
					self::$pdo->query("DROP DATABASE " . $this->opts['dbname']);
				} /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
					throw new RuntimeException("Error trying to drop your old database: '{$this->opts['dbname']}'", 2);
				}
				$found = self::checkDbExists();
			}

			if ($found) {
				var_dump(self::getTableList());
				throw new RuntimeException("Could not drop your old database: '{$this->opts['dbname']}'", 2);
			} else {
				self::$pdo->query("CREATE DATABASE `{$this->opts['dbname']}`  DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");

				if (!self::checkDbExists()) {
					throw new RuntimeException("Could not create new database: '{$this->opts['dbname']}'", 3);
				}
			}
		}
		self::$pdo->query("USE {$this->opts['dbname']}");
		//		var_dump('made it here');

		// In case PDO is not set to produce exceptions (PHP's default behaviour).
		if (self::$pdo === false) {
			$this->echoError(
				 "Unable to create connection to the Database!",
				 'initialiseDatabase',
				 1,
				 true
			);
		}

		// For backwards compatibility, no need for a patch.
		self::$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	/**
	 * Echo error, optionally exit.
	 *
	 * @param string $error    The error message.
	 * @param string $method   The method where the error occured.
	 * @param int $severity The severity of the error.
	 * @param bool $exit     Exit or not?
	 */
	protected function echoError(string $error, string $method, int $severity, bool $exit = false): void
    {
		echo "($method) $error [$severity]\n";

		if ($exit) {
			exit();
		}
	}

	/**
	 * Returns a string, escaped with single quotes, false on failure. http://www.php.net/manual/en/pdo.quote.php
	 *
	 * @param SimpleXMLElement|string|null $str
	 *
	 * @return string
	 */
	public function escapeString(SimpleXMLElement|string|null $str): string
    {
		if (is_null($str)) {
			return 'NULL';
		}

		return self::$pdo->quote($str);
	}

    /**
     * For inserting a row. Returns last insert ID. queryExec is better if you do not need the id.
     *
     * @param string $query
     *
     * @return bool|array
     */
	public function queryInsert(string $query): bool|array
    {
		if (empty($query)) {
			return false;
		}

		$i = 2;
		while($i < 11) {
			$result = $this->queryExecHelper($query, true);
			if (is_array($result) && isset($result['deadlock'])) {
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping.(" . ($i-1) . ")", 'queryInsert', 4);
					$i++;
				} else {
					break;
				}
			} elseif ($result === false) {
				break;
			} else {
				return $result;
			}
		}
		return false;
	}

    /**
     * Used for deleting, updating (and inserting without needing the last insert id).
     *
     * @param string $query
     *
     * @return PDOStatement|array|string|bool
     */
	public function queryExec(string $query): PDOStatement|array|string|bool
    {
		if (empty($query)) {
			return false;
		}

		$i = 2;
		while($i < 11) {
			$result = $this->queryExecHelper($query);
			if (is_array($result) && isset($result['deadlock'])) {
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping.(" . ($i-1) . ")", 'queryExec', 4);
					$i++;
				} else {
					break;
				}
			} elseif ($result === false) {
				break;
			} else {
				return $result;
			}
		}
		return false;
	}

    /**
     * Helper method for queryInsert and queryExec, checks for deadlocks.
     *
     * @param string $query
     * @param bool $insert
     *
     * @return PDOStatement|array|string|false
     */
	protected function queryExecHelper(string $query, bool $insert = false): PDOStatement|array|string|false
	{
		try {
			if ($insert === false ) {
				$run = self::$pdo->prepare($query);
				$run->execute();
				return $run;
			} else {
				if ($this->DbSystem === 'mysql') {
					$ins = self::$pdo->prepare($query);
					$ins->execute();
					return self::$pdo->lastInsertId();
				} else {
					$p = self::$pdo->prepare($query . ' RETURNING id');
					$p->execute();
					$r = $p->fetch(PDO::FETCH_ASSOC);
					return $r['id'];
				}
			}

		} catch (PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			if (
				$e->errorInfo[1] == 1213 ||
				$e->errorInfo[0] == 40001 ||
				$e->errorInfo[1] == 1205 ||
				$e->getMessage() == 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction'
			) {
				return array('deadlock' => true, 'message' => $e->getMessage());
			}
var_dump($e->getMessage());
			return array ('deadlock' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * Direct query. Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	 *
	 * @param string $statement
	 *
	 * @return bool|int
	 */
	public function Exec(string $statement): false|int
    {
		if (empty($statement)) {
			return false;
		}

		try {
			return self::$pdo->exec($statement);

		} catch (PDOException $e) {
			$this->echoError($e->getMessage(), 'Exec', 4);
			return false;
		}
	}


    /**
     * Returns an array of result (empty array if no results or an error occurs)
     * Optional: Pass true to cache the result with memcache.
     *
     * @param string $query SQL to execute.
     * @param int|null $fetchMode
     * @param mixed ...$fetch_mode_args
     *
     * @return PDOStatement|array|string|bool Array of results (possibly empty) on success, empty array on failure.
     */
	public function query(string $query, ?int $fetchMode = null, ...$fetch_mode_args): PDOStatement|array|string|bool
	{
		if (empty($query)) {
			return false;
		}

		$result = $this->queryArray($query);

		return ($result === false) ? array() : $result;
	}

	/**
	 * Main method for creating results as an array.
	 *
	 * @param string $query SQL to execute.
	 *
	 * @return array|boolean Array of results on success or false on failure.
	 */
	public function queryArray(string $query): bool|array
    {
		if (empty($query)) {
			return false;
		}

		$result = $this->queryDirect($query);
		if ($result === false) {
			return false;
		}

		$rows = array();
		foreach ($result as $row) {
			$rows[] = $row;
		}

		return (!isset($rows)) ? false : $rows;
	}

    /**
     * Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
     *
     * @param string $query The query to run.
     *
     * @return PDOStatement|bool
     */
	public function queryDirect(string $query): PDOStatement|bool
    {
		if (empty($query)) {
			return false;
		}

		try {
			$result = self::$pdo->query($query);
		} catch (PDOException $e) {
			$this->echoError($e->getMessage(), 'queryDirect', 4);
			$result = false;
		}
		return $result;
	}

    /**
     * Returns the first row of the query.
     *
     * @param string $query
     *
     * @return PDOStatement|array|string|bool
     */
	public function queryOneRow(string $query): PDOStatement|array|string|bool
    {
		$rows = $this->query($query);

		if (!$rows || count($rows) == 0) {
			$rows = false;
		}

		return is_array($rows) ? $rows[0] : $rows;
	}

    /**
	 * Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	 *
	 * @param bool $admin
	 * @param string $type
	 *
	 * @return int
	 */
	public function optimise(bool $admin = false, string $type = ''): int
    {
		$tableCount = 0;
		if ($this->DbSystem === 'mysql') {
			if ($type === 'true' || $type === 'full' || $type === 'analyze') {
				$allTables = $this->query('SHOW TABLE STATUS');
			} else {
				$allTables = $this->query('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005');
			}
			$tableCount = count($allTables);
			if ($type === 'all' || $type === 'full') {
				$tables = '';
				foreach ($allTables as $table) {
					$tables .= $table['name'] . ', ';
				}
				$tables = rtrim(trim($tables),',');
				if ($admin === false) {
					echo 'Optimizing tables: ' . $tables;
				}
				$this->queryExec("OPTIMIZE LOCAL TABLE $tables");
			} else {
				foreach ($allTables as $table) {
					if ($type === 'analyze') {
						$this->queryExec('ANALYZE LOCAL TABLE `' . $table['name'] . '`');
					} else {
						if ($admin === false) {
							echo 'Optimizing table: ' . $table['name'];
						}
						if (strtolower($table['engine']) == 'myisam') {
							$this->queryExec('REPAIR TABLE `' . $table['name'] . '`');
						}
						$this->queryExec('OPTIMIZE LOCAL TABLE `' . $table['name'] . '`');
					}
				}
			}
			if ($type !== 'analyze') {
				$this->queryExec('FLUSH TABLES');
			}
		} else if ($this->DbSystem === 'pgsql') {
			$allTables = $this->query("SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public'");
			$tableCount = count($allTables);
			foreach ($allTables as $table) {
				if ($admin === false) {
					echo 'Vacuuming table: ' . $table['name'] . ".\n";
				}
				$this->query('VACUUM (ANALYZE) ' . $table['name']);
			}
		}
		return $tableCount;
	}

    /**
     * PHP interpretation of MySQL's from_unixtime method.
     * @param int $utime UnixTime
     *
     * @return string
     */
	public function from_unixtime(int $utime): string
    {
		if ($this->DbSystem === 'mysql') {
			return 'FROM_UNIXTIME(' . $utime . ')';
		} else {
			return 'TO_TIMESTAMP(' . $utime . ')::TIMESTAMP';
		}
	}

    /**
	 * Checks whether the connection to the server is working. Optionally restart a new connection.
	 * NOTE: Restart does not happen if PDO is not using exceptions (PHP's default configuration).
	 * In this case check the return value === false.
	 *
	 * @param boolean $restart Whether an attempt should be made to reinitialise the Db object on failure.
	 *
	 * @return boolean
	 */
	public function ping(bool $restart = false): bool
    {
		try {
			return (bool) self::$pdo->query('SELECT 1+1');
		} catch (PDOException) {
			if ($restart) {
				$this->initialiseDatabase();
			}
			return false;
		}
	}

}
