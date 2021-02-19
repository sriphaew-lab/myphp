<?php

//include("../conn/connect.php");
//include("lib/nusoap.php");

class base { 

	private $channelAccessToken = "";
	private $richmenuId = "";

	// ตั้ง Rich Menu ให้ User
	function linkToUser($userId) {

		$strUrl = "https://api.line.me/v2/bot/user/{$userId}/richmenu/{$this->richmenuId}";

		$arrayPostData['userId'] = $userId;
		$arrayPostData['richMenuId'] = $this->richmenuId;

		$arrayHeader = array();
		$arrayHeader[] = "Authorization: Bearer {$this->channelAccessToken}";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $strUrl);
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);    
		curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($arrayPostData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
		$result = curl_exec($ch);
		curl_close ($ch);

	}

	function unLinkFromUser($userId) {

		$strUrl = "https://api.line.me/v2/bot/user/{$userId}/richmenu";


		$arrayPostData['userId'] = $userId;

		$arrayHeader = array();
		$arrayHeader[] = "Authorization: Bearer {$this->channelAccessToken}";

		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);
		curl_close ($ch);

	}

	public function grade($stdCode="", $stdFullName="") {

		$outp = "";
		$year = '';
		$term = '';

		$wsdl = "";  
		$client = new nusoap_client($wsdl,true);
		$client->soap_defencoding = 'UTF-8';
		$client->decode_utf8 = false;
		$err = $client->getError();

		$params = array(
              '_studentCode' => $stdCode,
              '_year' => $year,
              '_term' => $term
		);

		$termLabel = $term=="3"?"S":$term;
		
		$data = $client->call("GetGradeInTerm", $params);
		$grade =  $data["GetGradeInTermResult"]["diffgram"];

		$flexJSON = '{"payload": {"line": {"type": "flex","altText": "Flex Grade","contents": {'; 

		$flexJSON .= '"type": "bubble",';

		$flexJSON .= '"hero": {
						"type": "image",
						"url": "https://hook.rsu.ac.th/botcheckgrade/images/grade-hero.png",
						"size": "full",
						"aspectRatio": "20:8",
						"aspectMode": "cover"
						},';

	  
		$flexJSON .= '"body": {"type": "box","layout": "vertical","contents": [';

		$flexJSON .= '{
				"type": "text",
				"text": "' . $stdCode . '",
				"weight": "bold",
				"color": "#0094c1",
				"size": "lg"
			  },';

		$flexJSON .= '{
				"type": "text",
				"text": "'.$stdFullName.'",
				"weight": "bold",
				"size": "xl",
				"wrap": true
			  },';
		$flexJSON .= '{
				"type": "text",
				"text": "เกรดเทอม ' . $termLabel . ' ปีการศึกษา ' . $year . '",
				"size": "md",
				"color": "#aaaaaa",
				"wrap": true
			  },';
			  
		
		$flexJSON .= '{"type": "separator",	"margin": "xxl"},';

		$flexJSON .= '{"type": "box","layout": "vertical","margin": "xl","spacing": "sm","contents": [';
		

		$rSUBJ_CODE_Temp = array();
		$rSUBJ_CODE_Temp_1 = array();


		if( is_array($data["GetGradeInTermResult"]["diffgram"]) ) {
			
			$grade =  $data["GetGradeInTermResult"]["diffgram"]["DocumentElement"]["grade"];

			foreach($grade as $item) { //foreach element in $arr
				
				$subjectName = "";

				//ตรวจสอบ $item ว่าเป็น array หรือไม่
				if( is_array($item) ){//เป็น array แสดงว่าคืนค่าเกรดมา > 1 วิชา

						if(array_key_exists("TOPIC_CODE",$item)){

							if($item['TOPIC_CODE'] != ""){
								$subjectName = $item['TOPIC_NAME_ENG'];
							} else {
								$subjectName = $item['SUBJ_ENG_NAME'];
							}

						} else {
							$subjectName = $item['SUBJ_ENG_NAME'];
						}

						$flexJSON .= '{"type": "box","layout": "horizontal","contents": [';
						$flexJSON .= '{
							"type": "text",
							"text": "' . $item['SUBJ_CODE'] . '",
							"size": "md",
							"color": "#111111",
							"weight": "bold",
							"align": "start",
							"wrap": true
						  },
						  {
							"type": "text",
							"text": "' . $item['GRADE'] . ' ",
							"size": "md",
							"color": "#111111",
							"weight": "bold",
							"align": "end"
						  }';
						$flexJSON .= ']},';

						$flexJSON .= '{"type": "box","layout": "horizontal","contents": [';
							$flexJSON .= '{
								"type": "text",
								"text": "' . $subjectName . '",
								"size": "sm",
								"color": "#555555",
								"align": "start",
								"wrap": true
							  }';
						$flexJSON .= ']},';

				} else {//ไม่ใช่ array แสดงว่าคืนค่าเกรดมาวิชาเดียว
					
					if( !in_array($grade['SUBJ_CODE'], $rSUBJ_CODE_Temp_1) ) {
						
						if(array_key_exists("TOPIC_CODE",$grade)){

							if($grade['TOPIC_CODE'] != ""){
								$subjectName = $grade['TOPIC_NAME_ENG'];
							} else {
								$subjectName = $grade['SUBJ_ENG_NAME'];
							}

						} else {
							$subjectName = $grade['SUBJ_ENG_NAME'];
						}

						array_push($rSUBJ_CODE_Temp_1, $grade['SUBJ_CODE']);

						$flexJSON .= '{"type": "box","layout": "horizontal","contents": [';
							$flexJSON .= '{
								"type": "text",
								"text": "' . $grade['SUBJ_CODE'] . '",
								"size": "md",
								"color": "#111111",
								"weight": "bold",
								"align": "start",
								"wrap": true
							  },
							  {
								"type": "text",
								"text": "' . $grade['GRADE'] . ' ",
								"size": "md",
								"color": "#111111",
								"weight": "bold",
								"align": "end"
							}';
						$flexJSON .= ']},';
	
						$flexJSON .= '{"type": "box","layout": "horizontal","contents": [';
							$flexJSON .= '{
									"type": "text",
									"text": "' . $subjectName . '",
									"size": "sm",
									"color": "#555555",
									"align": "start",
									"wrap": true
							}';
						$flexJSON .= ']},';

					}
				}
			}
		
		} else {
			//$outp .= "ไม่พบข้อมูล. \n";
			$flexJSON .= '{"type": "box","layout": "horizontal","contents": [';
				$flexJSON .= '{
						"type": "text",
						"text": "ไม่พบข้อมูล.",
						"size": "sm",
						"color": "#555555",
						"align": "start",
						"wrap": true
				}';
			$flexJSON .= ']},';
		}


        $flexJSON .= '{"type": "separator", "margin": "xxl"}';
		  
		$flexJSON .= ']}';

	  $flexJSON .= ']},';


  	$flexJSON .= '"footer": {
	"type": "box",
	"layout": "horizontal",
	"contents": [
		  {
			"type": "text",
			"text": "หากต้องการตรวจสอบเกรดเทอมอื่นๆ สามารถตรวจสอบได้ที่ https://webportal.rsu.ac.th ค่ะ",
			"wrap": true,
			"size": "md",
			"color": "#0084B6",
			"action": {
			  "type": "uri",
			  "uri": "https://webportal.rsu.ac.th"
			}
		  }
		]
	}';


  	$flexJSON .= '}}}}';

	return json_decode($flexJSON, true);

	}


	public function study_timetable($stdCode="", $stdFullName=""){
		
		$year = '';
		$term = '';
		$outp = "";

		$wsdl = "";  
		$client = new nusoap_client($wsdl,true);
		$client->soap_defencoding = 'UTF-8';
		$client->decode_utf8 = false;
		$err = $client->getError();

		$params = array(
              '_stdCode' => $stdCode,
              '_year' => $year,
              '_term' => $term
		);
		
		$data = $client->call("FunctionUnion", $params);
		
		$rSUBJECT_CODE = array();
		$subjectCodeFirst = "";
		
		$termLabel = $term=="3"?"S":$term;


		$flexJSON = '{"payload": {"line": {"type": "flex","altText": "Flex Grade","contents": {';
			$flexJSON .= '"type": "bubble",';

			$flexJSON .= '"hero": {
							"type": "image",
							"url": "https://hook.rsu.ac.th/botcheckgrade/images/timetable-hero.png",
							"size": "full",
							"aspectRatio": "20:8",
							"aspectMode": "cover"
						},';
				// start body
				$flexJSON .= '"body": {"type": "box","layout": "vertical","contents": [';
					$flexJSON .= '{
						"type": "text",
						"text": "' . $stdCode . ' ",
						"weight": "bold",
						"color": "#0094c1",
						"size": "xl"
					  },
					  {
						"type": "text",
						"text": "' . $stdFullName . ' ",
						"weight": "bold",
						"size": "xl",
						"margin": "md",
						"wrap": true
					  },
					  {
						"type": "text",
						"text": "ตารางเรียน ปีการศึกษา ' . $year . ' เทอม ' . $termLabel . '",
						"size": "md",
						"color": "#aaaaaa",
						"wrap": true
					  },
					  {
						"type": "separator",
						"margin": "xxl"
					  },';

					//subject box
		$flexJSON .= '{
			"type": "box",
			"layout": "vertical",
			"margin": "xxl",
			"spacing": "sm",
			"contents": [';

		if( is_array($data["FunctionUnionResult"]["diffgram"]) ) {
			
			$timetable =  $data["FunctionUnionResult"]["diffgram"]["DocumentElement"]["unionTable"];

			foreach($timetable as $item) { //foreach element in $arr
				
				//ตรวจสอบ $item ว่าเป็น array หรือไม่ ถ้าเป็น array แสดงว่าคืนค่าตารางเรียน > 1 วิชา
				if( is_array($item) ) {

					if( $subjectCodeFirst != $item['SUBJECT_CODE'] ) {

						$subjectCodeFirst = $item['SUBJECT_CODE'];

						//subject title
						$flexJSON .= '{
							"type": "box",
							"layout": "horizontal",
							"contents": [
			  					{
									"type": "text",
									"text": "' . $item["SUBJECT_CODE"] . ' : ' . $item["SUBJ_ENG_NAME"] . ' ",
									"wrap": true,
									"size": "md",
									"weight": "bold",
									"color": "#000000",
									"flex": 0
			  					}
							]
		  				},';
						  //enf subject title
					}

					$Subj_type = "";
					if($item['LEC_SECTION'] != "") { 
						$Subj_type = "Lecture : " . $item['LEC_SECTION'];
					} else if($item['LAB_SECTION'] != "") {
						$Subj_type = "Lab : " . $item['LAB_SECTION'];
					}

					$room = "";
					if(isset($item['ROOM_CODE'])){ 
						$room = "Room : " . $item['ROOM_CODE']; 
					}

						//Section datail
		$flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $Subj_type . ' ",
				"size": "sm",
				"color": "#111111",
				"weight": "bold",
				"flex": 0
			  },
			  {
				"type": "text",
				"text": "' . $room . ' ",
				"size": "sm",
				"color": "#111111",
				"weight": "bold",
				"align": "end"
			  }
			]
		  },';

		  $sec_day = "";
		  if(isset($item['DAY_ENG_NAME'])){ 
			$sec_day = "Day : " . $item['DAY_ENG_NAME']; 
			}

		  $flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $sec_day . ' ",
				"wrap": true,
				"size": "sm",
				"color": "#555555"
			  }
			]
		  },';

		  $sec_time = "";

			if(isset($item['START_TIME'])) { 
				$sec_time .= "Time : ".  $item['START_TIME'];
			}
			if(isset($item['END_TIME'])) {
				$sec_time .= " - " . $item['END_TIME'];
			}

		  $flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $sec_time . ' ",
				"size": "sm",
				"color": "#555555",
				"flex": 0
			  }
			]
		  },';
		  //end section datail

				} else { //ไม่ใช่ array แสดงว่าคืนค่าเกรดมา 1 แถว
					
					if( !in_array($timetable['SUBJECT_CODE'], $rSUBJECT_CODE) ) {
						
						array_push($rSUBJECT_CODE, $timetable['SUBJECT_CODE']);

						//subject title
						$flexJSON .= '{
							"type": "box",
							"layout": "horizontal",
							"contents": [
			  					{
									"type": "text",
									"text": "' . $timetable["SUBJECT_CODE"] . ' : ' . $timetable["SUBJ_ENG_NAME"] . ' ",
									"wrap": true,
									"size": "md",
									"weight": "bold",
									"color": "#000000",
									"flex": 0
			  					}
							]
		  				},';
						  //enf subject title

					$Subj_type = "";
					if($timetable['LEC_SECTION'] != "") { 
						$Subj_type = "Lecture : " . $timetable['LEC_SECTION'];
					} else if($timetable['LAB_SECTION'] != "") {
						$Subj_type = "Lab : " . $timetable['LAB_SECTION'];
					}

					$room = "";
					if(isset($timetable['ROOM_CODE'])){ 
						$room = "Room : " . $timetable['ROOM_CODE']; 
					}

						//Section datail
		$flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $Subj_type . ' ",
				"size": "sm",
				"color": "#111111",
				"weight": "bold",
				"flex": 0
			  },
			  {
				"type": "text",
				"text": "' . $room . ' ",
				"size": "sm",
				"color": "#111111",
				"weight": "bold",
				"align": "end"
			  }
			]
		  },';

		  $sec_day = "";
		  if(isset($timetable['DAY_ENG_NAME'])){ 
			$sec_day = "Day : " . $timetable['DAY_ENG_NAME']; 
			}

		  $flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $sec_day . ' ",
				"wrap": true,
				"size": "sm",
				"color": "#555555"
			  }
			]
		  },';

		  $sec_time = "";

			if(isset($timetable['START_TIME'])) { 
				$sec_time .= "Time : ".  $timetable['START_TIME'];
			}
			if(isset($timetable['END_TIME'])) {
				$sec_time .= " - " . $timetable['END_TIME'];
			}

		  $flexJSON .= '{
			"type": "box",
			"layout": "horizontal",
			"contents": [
			  {
				"type": "text",
				"text": "' . $sec_time . ' ",
				"size": "sm",
				"color": "#555555",
				"flex": 0
			  }
			]
		  },';

					}
					
				}
			}
			//end forach
			$flexJSON = substr($flexJSON, 0, -1);

		} else {
			$flexJSON .= '{
				"type": "text",
				"text": "ไม่พบข้อมูล.",
				"wrap": true,
				"size": "md",
				"weight": "bold",
				"color": "#000000",
				"flex": 0
			  }';
		}

		$flexJSON .= ']},';//end subject box
		
		//end of a subject
		$flexJSON .= '{
				  "type": "separator",
				  "margin": "xxl"
				}';
		
		
		
		/*

		//Section datail
		$flexJSON .= '{
					  "type": "box",
					  "layout": "horizontal",
					  "contents": [
						{
						  "type": "text",
						  "text": "Lecture 01",
						  "size": "sm",
						  "color": "#111111",
						  "weight": "bold",
						  "flex": 0
						},
						{
						  "type": "text",
						  "text": "Room 4/2-302",
						  "size": "sm",
						  "color": "#111111",
						  "weight": "bold",
						  "align": "end"
						}
					  ]
					},
					{
					  "type": "box",
					  "layout": "horizontal",
					  "contents": [
						{
						  "type": "text",
						  "text": "Date : ",
						  "size": "sm",
						  "color": "#555555",
						  "flex": 0
						}
					  ]
					},
					{
					  "type": "box",
					  "layout": "horizontal",
					  "contents": [
						{
						  "type": "text",
						  "text": "Time : ",
						  "size": "sm",
						  "color": "#555555",
						  "flex": 0
						}
					  ]
					}';
					//end section datail
					*/

		

		// end body
		$flexJSON .= ']},';

		$flexJSON .= '"footer": {
				"type": "box",
				"layout": "horizontal",
				"contents": [
					  {
						"type": "text",
						"text": "หากต้องการตรวจสอบตารางเรียนเทอมอื่นๆ สามารถตรวจสอบได้ที่ https://webportal.rsu.ac.th ค่ะ",
						"wrap": true,
						"size": "md",
						"color": "#0084B6",
						"action": {
						  "type": "uri",
						  "uri": "https://webportal.rsu.ac.th"
						}
					  }
					]
				}';

		$flexJSON .= '}}}}';

		return json_decode($flexJSON, true);

	}

	public function exam_timetable($stdCode="", $stdFullName=""){

		$year = '';
		$term = '';
		$outp = "";
		$ExamType = 'F';

		$wsdl = "";  
		$client = new nusoap_client($wsdl,true);
		$client->soap_defencoding = 'UTF-8';
		$client->decode_utf8 = false;
		$err = $client->getError();

		$params = array(
			'_studentCode' => $stdCode,
			'_year' => $year,
			'_term' => $term,
			'_examType' => $ExamType
	  	);
	  
	  	$data = $client->call("GetExamDay", $params);
	  
	  	$rSUBJECT_CODE = array();
	  	$subjectCodeFirst = "";
	  
		$termLabel = $term=="3"?"S":$term;

		$flexJSON = '{"payload": {"line": {"type": "flex","altText": "Flex Grade","contents": {';
		$flexJSON .= '"type": "bubble","styles": { "footer": { "separator": true } },';

		$flexJSON .= '"hero": {
							"type": "image",
							"url": "https://hook.rsu.ac.th/botcheckgrade/images/examination-schedule.png",
							"size": "full",
							"aspectRatio": "20:8",
							"aspectMode": "cover"
						},';

		/* start body */
		$flexJSON .= '"body": { "type": "box", "layout": "vertical", "contents": [';
			
		$ExamTypeName = "";

		if($ExamType == "M") {
			$ExamTypeName = "กลางภาค";
		} else if($ExamType == "F") {
			$ExamTypeName = "ปลายภาค";
		}

		$flexJSON .= '{
        "type": "text",
        "text": "' . $stdCode . '",
        "weight": "bold",
        "color": "#0094c1",
        "size": "xl"
      },
      {
        "type": "text",
        "text": "' . $stdFullName . '",
        "weight": "bold",
        "size": "xl",
        "margin": "md",
        "wrap": true
      },
      {
        "type": "text",
        "text": "ตารางสอบ เทอม ' . $termLabel . ' ปีการศึกษา ' . $year . ' ประเภท ' . $ExamTypeName . ' ",
        "size": "sm",
        "color": "#aaaaaa",
        "wrap": true
      },
      {
        "type": "separator",
        "margin": "xxl"
	  },';
	  
	  //subject box
		$flexJSON .= '{
			"type": "box",
			"layout": "vertical",
			"margin": "xxl",
			"spacing": "sm",
			"contents": [';

		
		$rSUBJECT_CODE = array();
		$subjectCodeFirst = "";
		
		if( is_array($data["GetExamDayResult"]["diffgram"]) ) {
			
			$timetable =  $data["GetExamDayResult"]["diffgram"]["DocumentElement"]["tem"];

			foreach($timetable as $item) { //foreach element in $arr
				
				//ตรวจสอบ $item ว่าเป็น array หรือไม่ ถ้าเป็น array แสดงว่าคืนค่าตารางเรียน > 1 วิชา
				if(is_array($item) ) {

					if( $subjectCodeFirst != $item['SUBJECT_CODE'] ) {

						$subjectCodeFirst = $item['SUBJECT_CODE'];

						$flexJSON .= '{
							"type": "box",
							"layout": "horizontal",
							"contents": [
							  {
								"type": "text",
								"text": "' . $item['SUBJECT_CODE'] . ' : ' . $item['SUBJECT_NAME_ENG'] . '",
								"wrap": true,
								"size": "sm",
								"weight": "bold",
								"flex": 0
							  }
							]
						  },';

					}
					
					$Subject_Lec = "";
					$Lec_Room = "";

					if(isset($item['LEC_SECTION'])) { 
						$Subject_Lec = $item['LEC_SECTION'];
					}

					if(isset($item['ROOM_NO'])){ 
						$Lec_Room = $item['ROOM_NO'];
					}
						
					$flexJSON .= '{
            				"type": "box",
            				"layout": "horizontal",
            				"contents": [
              					{
                				"type": "text",
                				"text": "Lecture : ' . $Subject_Lec . '",
                				"size": "sm",
                				"color": "#111111",
                				"weight": "bold",
                				"flex": 0
              					},
              					{
                				"type": "text",
                				"text": "Room : ' . $Lec_Room . '",
                				"size": "sm",
                				"color": "#111111",
                				"weight": "bold",
                				"align": "end"
              					}
            				]
						  },';
						  
						
						

						if(isset($item['LEC_FINAL_DATE'])){ 
							$LecDate = "";
							if(trim($item['LEC_FINAL_DATE']) != ""){
								$LecDate = date('d-m-Y',strtotime($item['LEC_FINAL_DATE']));
							}

							$flexJSON .= '{
								"type": "box",
								"layout": "horizontal",
								"contents": [
								  {
									"type": "text",
									"text": "Date : ' . $LecDate . '",
									"size": "sm",
									"color": "#555555",
									"flex": 0
								  }
								]
							  },';
						}

						if(isset($item['LEC_FINAL_TIME'])) {
							$flexJSON .= '{
								"type": "box",
								"layout": "horizontal",
								"contents": [
								  {
									"type": "text",
									"text": "Time : ' .  $item['LEC_FINAL_TIME'] . '",
									"size": "sm",
									"color": "#555555",
									"flex": 0
								  }
								]
							  },';
						}

						$Subject_Lab = "";
						$Lab_Room = "";

					if(isset($item['LAB_SECTION'])) { 
						$Subject_Lab = $item['LAB_SECTION'];
					}

					if(isset($item['LAB_ROOM'])){ 
						$Lab_Room = $item['LAB_ROOM'];
					}

					$flexJSON .= '{
						"type": "box",
						"layout": "horizontal",
						"contents": [
						  {
							"type": "text",
							"text": "Lab :' . $Subject_Lab . '",
							"size": "sm",
							"color": "#111111",
							"weight": "bold",
							"flex": 0
						  },
						  {
							"type": "text",
							"text": "Room :' . $Lab_Room . '",
							"size": "sm",
							"color": "#111111",
							"weight": "bold",
							"align": "end"
						  }
						]
					  },';




						if(isset($item['LAB_FINAL_DATE'])) {
							$LabDate = "";
							if(trim($item['LAB_FINAL_DATE']) != ""){
								$LabDate = date('d-m-Y',strtotime($item['LAB_FINAL_DATE']));
							}
							$flexJSON .= '{
								"type": "box",
								"layout": "horizontal",
								"contents": [
								  {
									"type": "text",
									"text": "Date : ' . $LabDate . '",
									"size": "sm",
									"color": "#555555",
									"flex": 0
								  }
								]
							  },';
						}

						if(isset($item['LAB_FINAL_TIME'])) { 
							$flexJSON .= '{
								"type": "box",
								"layout": "horizontal",
								"contents": [
								  {
									"type": "text",
									"text": "Time : ' . $item['LAB_FINAL_TIME'] . '",
									"size": "sm",
									"color": "#555555",
									"flex": 0
								  }
								]
							  },';
						}

						$flexJSON .= '{
							"type": "separator",
							"margin": "md"
						  },';

				} else {//ไม่ใช่ array แสดงว่าคืนค่าเกรดมา 1 แถว

					
					if( !in_array($timetable['SUBJECT_CODE'], $rSUBJECT_CODE) ) {
						
						array_push($rSUBJECT_CODE, $timetable['SUBJECT_CODE']);

						$flexJSON .= '{
							"type": "box",
							"layout": "horizontal",
							"contents": [
							  {
								"type": "text",
								"text": "' . $timetable['SUBJECT_CODE'] . ' : ' . $timetable['SUBJECT_NAME_ENG'] . '",
								"wrap": true,
								"size": "sm",
								"weight": "bold",
								"flex": 0
							  }
							]
						  },';

						  $Subject_Lec = "";
						  $Lec_Room = "";
	  
						  if(isset($timetable['LEC_SECTION'])) { 
							  $Subject_Lec = $timetable['LEC_SECTION'];
						  }
	  
						  if(isset($timetable['ROOM_NO'])){ 
							  $Lec_Room = $timetable['ROOM_NO'];
						  }
							  
						  $flexJSON .= '{
								  "type": "box",
								  "layout": "horizontal",
								  "contents": [
										{
									  "type": "text",
									  "text": "Lecture : ' . $Subject_Lec . '",
									  "size": "sm",
									  "color": "#111111",
									  "weight": "bold",
									  "flex": 0
										},
										{
									  "type": "text",
									  "text": "Room : ' . $Lec_Room . '",
									  "size": "sm",
									  "color": "#111111",
									  "weight": "bold",
									  "align": "end"
										}
								  ]
								},';
								
							  
							  
	  
							  if(isset($timetable['LEC_FINAL_DATE'])){ 
								  $LecDate = "";
								  if(trim($timetable['LEC_FINAL_DATE']) != ""){
									  $LecDate = date('d-m-Y',strtotime($timetable['LEC_FINAL_DATE']));
								  }
	  
								  $flexJSON .= '{
									  "type": "box",
									  "layout": "horizontal",
									  "contents": [
										{
										  "type": "text",
										  "text": "Date : ' . $LecDate . '",
										  "size": "sm",
										  "color": "#555555",
										  "flex": 0
										}
									  ]
									},';
							  }
	  
							  if(isset($timetable['LEC_FINAL_TIME'])) {
								  $flexJSON .= '{
									  "type": "box",
									  "layout": "horizontal",
									  "contents": [
										{
										  "type": "text",
										  "text": "Time : ' .  $timetable['LEC_FINAL_TIME'] . '",
										  "size": "sm",
										  "color": "#555555",
										  "flex": 0
										}
									  ]
									},';
							  }
	  
							  $Subject_Lab = "";
							  $Lab_Room = "";
	  
						  if(isset($timetable['LAB_SECTION'])) { 
							  $Subject_Lab = $timetable['LAB_SECTION'];
						  }
	  
						  if(isset($timetable['LAB_ROOM'])){ 
							  $Lab_Room = $timetable['LAB_ROOM'];
						  }
	  
						  $flexJSON .= '{
							  "type": "box",
							  "layout": "horizontal",
							  "contents": [
								{
								  "type": "text",
								  "text": "Lab :' . $Subject_Lab . '",
								  "size": "sm",
								  "color": "#111111",
								  "weight": "bold",
								  "flex": 0
								},
								{
								  "type": "text",
								  "text": "Room :' . $Lab_Room . '",
								  "size": "sm",
								  "color": "#111111",
								  "weight": "bold",
								  "align": "end"
								}
							  ]
							},';
	  
							  if(isset($timetable['LAB_FINAL_DATE'])) {
								  $LabDate = "";
								  if(trim($timetable['LAB_FINAL_DATE']) != ""){
									  $LabDate = date('d-m-Y',strtotime($timetable['LAB_FINAL_DATE']));
								  }
								  $flexJSON .= '{
									"type": "box",
									"layout": "horizontal",
									"contents": [
									  {
										"type": "text",
										"text": "Date : ' . $LabDate . '",
										"size": "sm",
										"color": "#555555",
										"flex": 0
									  }
									]
								  },';
							  }
	  
							  if(isset($timetable['LAB_FINAL_TIME'])) {
								  $flexJSON .= '{
									  "type": "box",
									  "layout": "horizontal",
									  "contents": [
										{
										  "type": "text",
										  "text": "Time : ' . $timetable['LAB_FINAL_TIME'] . '",
										  "size": "sm",
										  "color": "#555555",
										  "flex": 0
										}
									  ]
									},';
							  }

						$flexJSON .= '{
							"type": "separator",
							"margin": "md"
						  },';
						
					}
				}

			}

			$flexJSON = substr($flexJSON, 0, -1);

		} else {
			$flexJSON .= '{
				"type": "text",
				"text": "ไม่พบข้อมูล.",
				"wrap": true,
				"size": "md",
				"weight": "bold",
				"color": "#000000",
				"flex": 0
			  }';
		}

	  $flexJSON .= ']},';//end subject box
	  
      $flexJSON .= '{
        "type": "box",
        "layout": "horizontal",
        "margin": "md",
        "contents": [
          {
            "type": "text",
            "text": "Footer",
            "size": "xxs",
            "color": "#aaaaaa",
            "flex": 0
          }
        ]
	  }';
	  
	  	/* end body */
		$flexJSON .= ']}';


		$flexJSON .= '}}}}';


		



		return json_decode($flexJSON, true);

	}
}

?>