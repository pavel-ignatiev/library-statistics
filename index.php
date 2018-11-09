<?php

# add modules
require_once('Config.php');
require_once('SQLiteConnection.php');
require_once('SQLiteOperate.php');
require_once('PHPExcel/PHPExcel/IOFactory.php');

# set debug options
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

# we're measuring time here
date_default_timezone_set('Europe/Berlin');

# include HTML templates
include 'template/header.html';
include 'template/form.html';

# parse user input
function testInput($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

function getSignature() { 
	if ( isset($_POST["signature"]) ) {   
		return testInput($_POST["signature"]); 
	} else {
		return '';
	}
}

# Initialize DB connection
function sqlInit() {

	# Create new PDO instance	 
	$pdo = (new SQLiteConnection())->connect();
	
	# Instantiate SQLite class
	return new SQLiteOperate($pdo);
	
}

function csvToExcel ( $inputFile, $outputFile ) {

	$objReader = PHPExcel_IOFactory::createReader('CSV');
	// If the file uses a delimiter other than a comma (e.g. a tab), then tell the reader
	// $objReader->setDelimiter("\t");
	// // If the file uses an encoding other than UTF-8 or ASCII, then tell the reader
	// $objReader->setInputEncoding('UTF-16LE');

	$objPHPExcel = $objReader->load( $inputFile );
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save( $outputFile );

}

# Convert a result of SQL query to HTML table
function printHtmlTable ( $sqlResultArray ) {
	
	if ( ! empty ($sqlResultArray) ) {
		$data = $sqlResultArray;
		$header = array_keys(current($data));
		$html_header = implode( '</th><th>', $header );
	} else {
		include 'template/emptyDB.html';
		include 'template/footer.html';
		die();
	}
	
	# Create CSV handler
	$fp = fopen('export.csv', 'w');

	# Write header
	fputcsv( $fp, $header );

	if (count($data) > 0):
		#		include 'template/tableHeader.html';
		echo '<table class="w3-table-all w3-striped">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . $html_header . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($data as $row):

			fputcsv( $fp, $row );

			array_map('htmlentities', $row);
			echo '<tr>';
			echo '<td>';
			echo implode('</td><td>', $row);
			echo '</td>';
			echo '</tr>';
		endforeach;
		echo '</tbody>';
		echo '</table>';
	endif;

	fclose($fp);

	csvToExcel( 'export.csv', 'export.xls' );

	echo '</table>';

}

# Print the whole DB
function showAll( $sqliteInstance ) {

	if ( ! empty($sqliteInstance) ) $sqlite = $sqliteInstance;
 
	$result = $sqlite->selectAll();

	include('template/download.html');

	printHtmlTable ( $result );

}

# Print absolute frequencies of each entry
function showAbsoluteFrequency( $sqliteInstance ) {

	if ( ! empty($sqliteInstance) ) $sqlite = $sqliteInstance;
 
	$result = $sqlite->getAbsoluteFrequency();

	include('template/download.html');

	printHtmlTable ( $result );

}

# Print updated entry
function showEntry ( $sqliteInstance, $signature ) {

	if ( ! empty($sqliteInstance) ) $sqlite = $sqliteInstance;

	$result = $sqlite->selectIdByName($signature);

	printHtmlTable ( $result );

	include 'template/footer.html';

	die();
}

# Update or create entry
function alterSignature ( $sqliteInstance, $signature) {

	if ( ! empty($sqliteInstance) ) $sqlite = $sqliteInstance;
		
	# insert new signature
	$sqlite->insertSignature($signature);
	include 'template/newEntryMsg.html';

}

function execAction ( $action, $sqliteInstance, $signature ) {
	switch ($action) {
	case "showall":
		showAll($sqliteInstance);
		break;
	case "absoluteFrequency":
		showAbsoluteFrequency($sqliteInstance);
		break;
	case "insert":
		alterSignature($sqliteInstance, $signature); 
		showEntry($sqliteInstance, $signature);
		break;
	case "emptySignature":
		include 'template/emptySignature.html';
		break;
	default:
		break;
	}
}

# main function
if ($_SERVER["REQUEST_METHOD"] == "POST") {

	$signature = getSignature();

	$sqlite = sqlInit();

	# Create new table if not existing
	$sqlite->createTable();

	# Choose main action to execute
	if ( isset($_POST["showall"])) {

		$action = "showall";
		
	} else if ( isset($_POST["absoluteFrequency"]) ) {

		$action = "absoluteFrequency";
			
	} else if (! empty($signature)) {

		$action = "insert";

	} else {

		$action = "emptySignature";

	}

	execAction ($action, $sqlite, $signature);

	if ( isset($_POST["ods"]) ) {
		echo "test";
		exportODS('test', 'test.ods');
	}	
}		

include 'template/footer.html';
die();
