# php-database-library
PHP library for easy access to MySQL databases and the file system.

(View in "RAW" mode for better formatting.)

This code was written by Justin Eldracher in 2015.  Feel free to do whatever you want to this code,
just please let me know what you changed/added so I don't miss out on anything awesome! ;)
If you find any errors or would like to make comments, please leave a message at:
http://blindwarrior.16mb.com/writemsg.php

Method List:
	init([default table]);
		Initialize the database connection and select a database.  Set the table also, if one is specified.
	getall();
		Returns an associative matrix of the databases and their tables on the current MySQL connection.
	setdb(new_database_name);
		Change the active database.
	getdb();
		Returns the name of the active database.
	stable(new_table_name);
		Change the active table.
	gtable();
		Returns the name of the active table.
	setprime(new_primary_key);
		Changes the default Primary Key.
	getprime();
		Returns the current default Primary Key.
	all([column to sort by], [sort order], [custom table]);
		Returns either the entire active table or a custom table as a matrix.
	select([columns to select, either string or array], [assoc array for WHERE], [column to sort by], [sort order], [custom table]
		Returns a matrix for a SELECT statement, default is the entire table.
	toarray(mysql_result);
		Returns a matrix of a db result
	insert(array_or_matrix_of_values, [custom table]);
		Inserts an array or matrix into the active or custom table.	Array length must match the number of columns in the table.
		$db->insert(array(1, "Hello!", "Goodbye!"));  $db->insert($db->read("test.csv", "csv"));
	sqlstr(mixed_var);
		Adds single quotes around a string variable if it doesn't have them already.
	update(assoc_array_values_to_change, assoc_array_for_WHERE_statement, [custom table]);
		Updates a custom table or the active one using two associative arrays.
		$db->update(array("id" => 1, "greeting" => "Hi!"), array("parting" => "Goodbye!"));
	delete([identifier column, default to _prime_ variable], value_to_match, [boolean reset primary keys, default true]);
		Deletes a row based on the value of the first parameter.  $db->delete("id", 1);
	authorize(user_name, password);
		Checks if the given username and password are in the users table and unique.
	addauthuser(array_of_values, [custom index for password, default 1]);
		Adds an array of values to the users table.  First parameter MUST NOT include a Primary Key as the first item.
		Second parameter is the index of the password in the array of values.
	updateuser(assoc_array_values_to_change, assoc_array_for_WHERE_statement);
		Updates a record in the users table.
	chgpass(current_user, current_password, new_user, new_password);
		Updates only the username and password, if given username and password already exist.
	deluser(numeric_id);
		Deletes a user.
	addsetting(array_of_values);
		Inserts a row into the settings table. $db->addsetting(array(1, "font-family", "Times New Roman", "Page Font:", "*"));
	getsettings(["array" or "json", default "array"]);
		Returns either an associative array of the settings from the settings table or a json string
	savesetting(id, new_value);
		Changes the value of a setting by it's id.
	tocss(output_file_name, [matrix of values, default is settings table])
		Takes a matrix and turn it into a css file, written to specified file path.
	shift([starting id, default 1], [custom table]);
		Shifts all rows of a table down, starting at the primary key specified.
	reset([custom table]);
		Resets all the primary keys in a table.
	execute([sql query], [custom table]);
		Executes a given SQL query or a select all by default.
	rows([sql query], [custom table]);
		Returns the number of rows in an sql query, default is the entire table.
	read(file_name, [file type], [custom delimeter for type "csv"]);
		Reads a file and return contents based on value of $type.
		$type can be: "string", "array", "xml", "doc", "pdf", or "csv".
		print $db->read("test.txt"); print $db->read("test.doc", "doc"); print_r($db->read("test.csv", "csv", ",");
	write(file_name, file_contents, [custom delimeter for csv files]);
		Writes content to a file. If content is an array, file will be saved in delimited format.
		$db->write("demo.txt", $db->all(), "\t");
	ren(old_name, new_name);
		Renames a file.
	del(file_name);
		Deletes a file.
	is(file_name);
		Simplify file exists.
	getfiles(directory, [regular expression for desired files]);
		Returns a sorted array of all the files in a specified directory based on an array of desired file extensions.
		$db->getfiles("media/", "/\.mp3$|\.wav$/");
	get(input_variable, [send method: "post" or "get", default "post"]);
		Returns a variable sent through either POST or GET.
	datestring(date_string);
		Returns: "April 3, 2015" for "5/3/15".
	
Table Structure:
	All tables are assumed to have a Primary Key column named "id".
	
	The Settings table is something I have found convenient for customizing cms systems.
	-------------------------------------------------------------
	|	id	|	name	|	value 	|	  alias     |	  selector	|
	-------------------------------------------------------------
	|	1	  |	color	|	#ff0000	| Text Color: |	   body	  	|
	-------------------------------------------------------------
	| ... |	 ...	|	 ...	  |    ...	    |	    ...		  |
	-------------------------------------------------------------
	Coupled with $db->tocss("settings.css"), it provides an easy way for users to customize a web page.
	A simple loop can print out all the settings in a form, and then a foreach loop through the imput vars saves them.
	
Configuration:
	$_dbconn_ = Stores MySQL connection:  DON'T TOUCH!! ;)
	$_host_ = MySQL hostname, obviously. ;)
	$_user_ = MySQL username, obviously. ;)
	$_pass_ = MySQL guess what? ;)
	$_db_ = Default database for queries.
	$_authtable_ = Table for storing user info.
	$_settings_ = Table for storing user profile settings.
	$_table_ = Current table for queries.
	$_prime_ = Name of column used as Primary Key in all tables.
	$_debug_ = Boolean whether or not to print mysql_error for queries.
	$_showqueries_ Boolean whether or not to print out SQL queries, useful only when debugging. ;)
	$_csvdelim_ = Default delimiter for CSV and other delimited formats.
	$_antiword_ = Path to Antiword executible, needed for reading Microsoft Word documents.
	$_xpdf_ = Path to XPDF executible, needed for reading PDF documents.

For ease of updating and using on local and remote servers, save the following code
(with whatever additional configuration changes you want) as a new class.

<?php
include "DB.php";

Class your_custom_name extends DB {
	public function __construct($table = "") {
		$this->_host_ = "localhost";
		$this->_user_ = "my_user_name";
		$this->_pass_ = "my_password";
		$this->_db_ = "default_db";
		$this->_debug_ = true;
		$this->_showqueries_ = true;
		$this->_antiword_ = "C:/antiword/antiword.exe";
		$this->_xpdf_ = "C:/xpdf/bin32/pdftotext.exe";
		
		// Don't remove this line!
		$this->init($table);
	}
}
?>
