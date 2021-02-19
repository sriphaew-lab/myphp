<?php
	ini_set('display_errors', 1);
	error_reporting(~0);
	
   $serverName = "";
   $userName = "";
   $userPassword = "";
   $dbName = "";



	$connectionInfo = array("Database"=>$dbName, "UID"=>$userName, "PWD"=>$userPassword, "MultipleActiveResultSets"=>true, "CharacterSet"  => 'UTF-8');

	$conn = sqlsrv_connect( $serverName, $connectionInfo);

	if($conn)
	{
		//echo "Database Connected.";
	}
	else
	{
		//die(json_encode(sqlsrv_errors(), true));
	}

?>