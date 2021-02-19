<?php

header('Content-Type: application/json; charset=utf-8');

include("../conn/connect.php");
include("class.php");

$result = false;

$userId = trim($_POST['txtUserId']);

$sql = "SELECT * FROM Liff_User  WHERE (userId = ?) AND (Status = ?)";
$params = array($userId, '1');
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

$stmt = sqlsrv_query( $conn, $sql, $params, $options);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$row = sqlsrv_num_rows($stmt);

if($row === false) {
	echo json_encode(array('status' => '0', 'message'=> sqlsrv_errors()));
}
else
{
	//ไม่ error
	// Set Rich Menu
	$className = new base();

	$row > 0 ? $className->linkToUser($userId) : $className->unLinkFromUser($userId);
	//return number rows
	echo json_encode(array('status' => '1', 'message' => $row, 'stdData' => $result));
}

?>