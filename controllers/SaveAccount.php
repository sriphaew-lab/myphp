<?php

	header('Content-Type: application/json; charset=utf-8');

	include("../lib/nusoap.php");
	include("../conn/connect.php");
	include("class.php");

	$userId = trim($_POST['txtUserId']);
	$accessToken = trim($_POST['txtAccessToken']);
	$stdCode = trim($_POST['txtStdCode']);
	$password = trim($_POST['txtPassword']);
	$studentFullName = '';

	$subStdCode = substr($stdCode, 0, 1);

	if(($subStdCode == 'u') || ($subStdCode == 'U')){
		//echo $stdCode;
	} else {
		$stdCode = "U" . $stdCode;
	}
	
	//ตรวจสอบว่าเคย authen แล้วหรือยัง
	//select Table Liff_User
	//Params $stdCode, Status = 1
	$sql = "SELECT * FROM Liff_User  WHERE (StudentCode = ?) AND (Status = ?)";
	$params = array($stdCode, '1');
	$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

	$stmt = sqlsrv_query( $conn, $sql, $params, $options);

	$row = sqlsrv_num_rows($stmt);

	if($row === false) {
		echo json_encode(array('status' => '0', 'message'=> sqlsrv_errors()));
	}
	else
	{
		// เท่ากับ 0 แสดงว่าไม่เคย syn เข้าสู่กระบวนการเชื่อมโยงข้อมูล
		if($row == 0) {

			//ตรวจสอบการ authen || URL = ใส่ url
			$client = new nusoap_client("URL",true);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = false;

			$params = array(
				'_user' => $stdCode, '_pwd' => $password
			);
	
			$data = $client->call("AuthenLogin",$params); 
			
			$data_clean = $data['AuthenLoginResult']['diffgram'];
	
			//ถ้า Std กับ Pwd ถูกต้อง respone ต้องไม่เป็นค่าว่าง
			if($data_clean != "") {

				$StdDetail = $data_clean['DocumentElement']['mytable'];
				$studentFullName = $StdDetail['TITLE_NAME'] . ' ' . $StdDetail['FIRST_NAME'] . ' ' . $StdDetail['LAST_NAME'];
				
				$sql = "INSERT INTO Liff_User (accessToken, userId, StudentCode, StudentFullName, Status) VALUES (?, ?, ?, ?, ?)";
				$params = array($accessToken, $userId, $stdCode, $studentFullName, '1');

				$stmt = sqlsrv_query( $conn, $sql, $params);

				if($stmt === false) {
					echo json_encode(array('status' => '0', 'message'=> sqlsrv_errors()));
				}
				else
				{
					$className = new base();
					$className->linkToUser($userId);

					echo json_encode(array('status' => '1', 'message'=> 'สวัสดี ' . $studentFullName . ' ระบบทำการผูกบัญชี intranet กับ Line bot สำเร็จแล้ว.'));
				}

			} else {
				//username or Password incorrect
				echo json_encode(array('status' => '0', 'message'=> 'รหัสนักศึกษา หรือ รหัสผ่าน ไม่ถูกต้อง.'));
			}
		
		// > 0 แสดว่าเคยเชื่อมโยงแล้วไม่ให้เชื่อมโยงซ้ำ
		}else{
			echo json_encode(array('status' => '0', 'message'=> 'บัญชีนี้เคยเชื่อมโยงกับ Line bot แล้ว ไม่สามารถเชื่อมโยงซ้ำได้.'));
		}
	}


	sqlsrv_close($conn);

?>