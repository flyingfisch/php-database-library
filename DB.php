<?php
/*
This code was written by Justin Eldracher in 2015.  Feel free to do whatever you want to this code,
just please let me know what you changed/added so I don't miss out on anything awesome! ;)
If you find any errors or would like to make comments, please leave a message at:
http://blindwarrior.16mb.com/writemsg.php
*/

Class DB {
	// Database configuration:
	protected $_dbconn_ = null;
	protected $_host_ = "localhost";
	protected $_user_ = "root";
	protected $_pass_ = "secret";
	protected $_db_ = "mydb";
	protected $_authtable_ = "users";
	protected $_settings_ = "settings";
	protected $_table_ = "";
	protected $_prime_ = "id";
	protected $_debug_ = false;
	protected $_showqueries_ = false;
	
	// File System configuration:
	protected $_csvdelim_ = ",";
	protected $_antiword_ = "C:/antiword/antiword.exe";
	protected $_xpdf_ = "C:/xpdf/bin32/pdftotext.exe";
	
	public function __construct($table = "") {
		$this->init($table);
	}
	
	public function init($table = "") {
		$this->_dbconn_ = @mysql_connect($this->_host_, $this->_user_, $this->_pass_);
		@mysql_select_db($this->_db_);
		$this->stable($table);
	}
	
	public function getall() {
		$dbs = mysql_list_dbs($this->_dbconn_);
		$db_array = array();
		$d = 0;
		while ($d < mysql_num_rows($dbs)) {
			$thisdb = mysql_tablename($dbs, $d);
			$dbtables = array();
			$tbs = mysql_list_tables($thisdb);
			$t = 0;
			while ($t < mysql_num_rows($tbs)) {
				array_push($dbtables, mysql_tablename($tbs, $t));
				$t++;
			}
			$db_array[$thisdb] = $dbtables;
			$d++;
		}
		$this->setdb($this->_db_);
		return $db_array;
	}
	
	public function setdb($name) {
		if ($name != "") {
			$this->_db_ = $name;
			mysql_select_db($name);
		} else {
			return 0;
		}
	}
	
	public function getdb() {
		return $this->_db_;
	}
	
	public function stable($name) {
		if ($name != "") {
			$this->_table_ = $name;
		} else {
			return 0;
		}
	}
	
	public function gtable() {
		return $this->_table_;
	}
	
	public function setprime($prime) {
		if ($prime != "") {
			$this->_prime_ = $prime;
			return 1;
		} else {
			return 0;
		}
	}
	
	public function getprime() {
		return $this->_prime_;
	}
	
	public function all($col = "", $dir = "ASC", $table = "") {
		return $this->select("*", null, $col, $dir, $table);
	}
	
	public function select($cols = "*", $cond = null, $sorter = "", $dir = "ASC", $table = "") {
		$dir = strtoupper($dir);
		if ($table == "") { $table = $this->_table_;}
		if ($sorter == "") { $sorter = $this->_prime_;}
		$columns = "";
		if (is_array($cols)) {
			foreach ($cols as $item) {
				$columns .= "$item, ";
			}
			$columns = substr($columns, 0, strlen($columns) - 2);
		} else {
			$columns = $cols;
		}
		$sql = "SELECT $columns FROM $table ";
		if ($cond != null) {
			$sql .= "WHERE ";
			foreach ($cond as $col => $val) {
				$sql .= "$col = {$this->sqlstr($val)} AND ";
			}
			$sql = substr($sql, 0, strlen($sql) - 4);
		}
		$sql .= "ORDER BY $sorter $dir";
		return $this->toarray($this->execute($sql));
	}
	
	public function toarray($dbresult) {
		$tablerows = array();
		$index = 0;
		if (@mysql_num_rows($dbresult) != 0) {
			while ($row = mysql_fetch_assoc($dbresult)) {
				$tablerows[$index] = $row;
				$index++;
			}
			return $tablerows;
		} else {
			return 0;
		}
	}
	
	public function insert($array, $table = "") {
		if ($table == "") {$table = $this->_table_;}
		if (is_array($array[0])) {
			$result = 0;
			for ($i = 0; $i < count($array); $i++) {
				$resutl = $this->sqlinsert($array[$i], $table);
			}
			return $result;
		} else {
			return $this->sqlinsert($array, $table);
		}
	}
	
	protected function sqlinsert($array, $table) {
		$sql = "INSERT INTO $table VALUES (";
		for ($i = 0; $i < count($array); $i++) {
			if ($i == count($array) - 1) {
				$sql .= $this->sqlstr($array[$i]) . ")";
			} else {
				$sql .= $this->sqlstr($array[$i]) . ", ";
			}
		}
		return $this->execute($sql);
	}
	
	public function sqlstr($var) {
		if (is_string($var)) {
			if (substr($var, 0, 1) != "'" && substr($var, 0, 10) != "password('") {
				$var = "'$var'";
				return $var;
			} else {
				return $var;
			}
		} else {
			return $var;
		}
	}
	
	public function update($array, $condition, $table = "") {
		if ($table == "") {$table = $this->_table_;}
		$sql = "UPDATE $table SET ";
		foreach ($array as $col => $val) {
			$sql .= "$col = {$this->sqlstr($val)}, ";
		}
		$sql = substr($sql, 0, strlen($sql) - 2) . " WHERE ";
		foreach ($condition as $col => $val) {
			$sql .= "$col = {$this->sqlstr($val)} AND ";
		}
		$sql = substr($sql, 0, strlen($sql) - 5);
		return $this->execute($sql);
	}
	
	public function delete($col = "", $value, $reorder = true) {
		if ($col == "") {$col = $this->_prime_;}
		$r = $this->execute("DELETE FROM " . $this->_table_ . " WHERE $col = $value");
			if ($reorder == true) {
				$this->reset();
			}
		return $r;
	}
	
	public function authorize($user, $pass) {
		$r = $this->execute("SELECT * FROM " . $this->_authtable_ . " WHERE user = '$user' AND pass = password('$pass')");
		if (mysql_num_rows($r) == 1) {
			return mysql_fetch_assoc($r);
		} else {
			return 0;
		}
	}
	
	public function addauthuser($array, $pindex = 1) {
		$array[$pindex] = "password({$this->sqlstr($array[$pindex])})";
		$r = $this->insert(array_merge(array($this->rows("SELECT * FROM " . $this->_authtable_) + 1), $array), "users");
		if ($r == 1) {
			return 1;
		} else {
			return 0;
		}
	}
	
	public function updateuser($array, $condition) {
		return $this->update($array, $condition, $this->_authtable_);
	}
	
	public function chgpass($oldusr, $oldpass, $newusr, $newpass) {
		$user = $this->authorize($oldusr, $oldpass);
		if ($user != false) {
			return $this->update(
				array("user" => $newusr, "pass" => "password({$this->sqlstr($newpass)})"),
				array($this->_prime_ => $user[$this->_prime_]),
				$this->_authtable_);
		} else {
			return 0;
		}
	}
	
	public function deluser($id) {
		$r = $this->execute("DELETE FROM " . $this->_authtable_ . " WHERE " . $this->_prime_ . " = $id");
		$this->reset($this->_authtable_);
		return $r;
	}
	
	public function addsetting($array) {
		return $this->insert($array, $this->_settings_);
	}
	
	public function getsettings($type = "array") {
		$type = strtolower($type);
		$assoc = $this->all("", "", $this->_settings_);
		if ($assoc != false) {
			if ($type == "array") {
				return $assoc;
			} else {
				return json_encode($assoc);
			}
		} else {
			return 0;
		}
	}
	
	public function savesetting($id, $val) {
		return $this->update(array("value" => $val), array("id" => $id), $this->_settings_);
	}
	
	public function tocss($file, $css = null) {
		if ($css == null) { $css = $this->getsettings();}
		$cssfile = "";
		$l = "{";
		$r = "}";
		for ($i = 0; $i < count($css); $i++) {
			$sel = $css[$i]["selector"];
			if ($sel != "") {
				$name = $css[$i]["name"];
				$val = $css[$i]["value"];
				$cssfile .= <<<HERE
$sel $l
	$name: $val;
$r

HERE;
			}
		}
		$this->write($file, $cssfile);
	}
	
	public function shift($start = 1, $table = "") {
		$this->reset($table);
		$r = $this->all("", $table);
		for ($i = count($r) + 1; $i > $start; $i--) {
			$this->update(array($this->_prime_ => $i), array($this->_prime_ => $i - 1), $table);
		}
	}
	
	public function reset($table = "") {
		if ($table == "") {$table = $this->_table_;}
		$r = $this->execute("", $table);
		$idarray = array();
		while($row = mysql_fetch_assoc($r)) {
			array_push($idarray, $row[$this->_prime_]);
		}
		$lastid = 0;
		for ($a = 0; $a < count($idarray); $a++) {
			$old = $idarray[$lastid];
			$new = $a + 1;
			if ($old != $new) {
				$this->update(array($this->_prime_ => $new), array($this->_prime_ => $old), $table);
			}
			$lastid++;
		}
	}
	
	public function execute($sql = "", $table = "") {
		if ($table == "") {$table = $this->_table_;}
		if ($sql == "") {$sql = "SELECT * FROM $table";}
		if ($this->_showqueries_ == true) {
			print $sql;
		}
		if ($this->_debug_ == true) {
			$r = mysql_query($sql, $this->_dbconn_);
			print mysql_error();
			return $r;
		} else {
			return mysql_query($sql, $this->_dbconn_);
		}
	}
	
	public function rows($sql = "", $table = "") {
		return mysql_num_rows($this->execute($sql, $table));
	}
	
	/* File System functions */
	
	public function read($file, $type = "string", $csvdelim = "") {
		if ($csvdelim == "") { $csvdelim = $this->_csvdelim_;}
		$type = strtolower($type);
		$fcontents = "";
		if ($this->is($file)) {
			$f = file($file);
			if ($type == "array") {
				$fcontents = $f;
			} elseif ($type == "string") {
				foreach ($f as $line) {
					$fcontents .= $line;
				}
			} elseif ($type == "xml") {
				$fcontents = simplexml_load_file($file);
			} elseif ($type == "doc") {
				if ($this->is($this->_antiword_)) {
					$fcontents = shell_exec("{$this->_antiword_} " . $file . " -t");
				} else {
					return 0;
				}
			} elseif ($type == "pdf") {
				if ($this->is($this->_xpdf_)) {
					$fcontents = shell_exec("{$this->_xpdf_} " . $file . " -");
					// remove extra characters at the end of the file:
					$fcontents = substr($fcontents, 0, strlen($fcontents) - 2);
				} else {
					return 0;
				}
			} elseif ($type == "csv") {
				$f = fopen($file, "r");
				$i = 0;
				while ($row = fgetcsv($f, filesize($file), $csvdelim)) {
					$fcontents[$i] = $row;
					$i++;
				}
				fclose($f);
			}
			return $fcontents;
		} else {
			return 0;
		}
	}
	
	public function write($file, $contents, $csvdelim = "") {
		if ($csvdelim == "") { $csvdelim = $this->_csvdelim_;}
		$f = fopen($file, "w+");
		if (is_array($contents)) {
			foreach ($contents as $line) {
				fputcsv($f, $line, $csvdelim);
			}
		} else {
			fputs($f, $contents);
		}
		fclose($f);
	}
	
	public function ren($file1, $file2) {
		if ($this->is($file1)) {
			return rename($file1, $file2);
		} else {
			return 0;
		}
	}	
	
	public function del($file) {
		return unlink($file);
	}
	
	public function is($file) {
		return file_exists($file);
	}
	
	public function getfiles($dir, $regexp = "") {
		$files = array();
		$d = @opendir($dir);
		while ($file = @readdir($d)) {
			if ($file != "." && $file != "..") {
				array_push($files, $file);
			}
		}
		@closedir($d);
		if ($regexp != "") {
			$files = preg_grep($regexp, $files);
		}
		sort($files);
		if (count($files) > 0) {
			return $files;
		} else {
			return 0;
		}
	}
	
	/* Miscellaneous function to make life easier. ;) */
	
	public function get($var, $type = "post") {
		$type = strtolower($type);
		if ($type == "post") {
			return mysql_real_escape_string(filter_input(INPUT_POST, $var));
		} else {
			return mysql_real_escape_string(filter_input(INPUT_GET, $var));
		}
	}
	
	public function datestring($date) {
		$a = explode("/", $date);
		$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		return $months[$a[0] - 1] . " " .  $a[1] . ", 20" . $a[2];
	}
}
?>
