<?php

	header('Content-Type: application/json; charset=utf-8');

	include("../conn/connect.php");
	include("class.php");

	$userId = trim($_POST['txtUserId']);
	
	//ตรวจสอบว่าเคย authen แล้วหรือยัง
	//select Table Liff_User
	//Params $stdCode, Status = 1
	$sql = "SELECT * FROM Liff_User  WHERE (userId = ?) AND (Status = ?)";
	$params = array($userId, '1');
	$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

	$stmt = sqlsrv_query( $conn, $sql, $params, $options);

	$row = sqlsrv_num_rows($stmt);
	$className = new base();

	if($row === false) {
		echo json_encode(array('status' => '0', 'message'=> sqlsrv_errors()));
	}
	else
	{
		// เท่ากับ 0 แสดงว่าไม่เคย syn เข้าสู่กระบวนการเชื่อมโยงข้อมูล
		if($row == 0) {

			$className->unLinkFromUser($userId);
			echo json_encode(array('status' => '0', 'message'=> 'คุณยังไม่ได้เชื่อมโยงบัญชี Line กับ RSU Intranet โปรดคลิกที่ เมนู > เชื่อมโยงบัญชี.'));
		
		// > 0 แสดว่าเคยเชื่อมโยงแล้ว
		} else {
			
			$strSQL = "UPDATE Liff_User SET ";
				$strSQL .="Status = '0' ";
				$strSQL .="WHERE userId = '".$userId."' ";
				$stmt = sqlsrv_query($conn, $strSQL);

				if($stmt === false) {
					echo json_encode(array('status' => '0', 'message'=> sqlsrv_errors()));
				}
				else
				{
					$className->unLinkFromUser($userId);
					echo json_encode(array('status' => '1', 'message'=> 'ยกเลิกการเชื่อมโยงบัญชีเรียบร้อยแล้ว.'));
				}

		}
	}

	sqlsrv_close($conn);

?>