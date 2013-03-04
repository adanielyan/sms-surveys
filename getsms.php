<?php

require_once ( "Nexmo-lib/NexmoMessage.php" );
require_once "functions.php";


function getChoice($txttext, $txtfrom){
// Interpret the content received, identified the survey id and put it on on SurveyData table;
	trim($txttext);
	//This split is just for one text message per question
	$smsData = preg_split("/[#]/", $txttext);
	if(($size =count($smsData))==2){
			//echo' The SMS data were split to: '.$size. 'array';
			echo '<br> first split:' . $smsData[0];
			echo '<br> second split:' . $smsData[1];
			$formIdSub =  $smsData[0] ;
			if(formExists($smsData[0]) ){				
				//Verify the ID of the question and the choice - to start let's split digit and letter
				 trim($smsData[1]) ;
				 preg_match('/\d+/',  $smsData[1], $questionId);  //Getting the Digit part of the answer
				 preg_match('/[A-Za-z]+/',  $smsData[1], $choice); //Getting the choice, letter part of the answer
				 
				 if ($questionID = getQuestionId($questionId[0], $smsData[0] )){
				 	if(!alreadyAnswered($txtfrom, $questionID)) {
						if ($choiceId = getChoiceId( $choice[0], $questionID)){
							//Save the information submitted into DB
							SaveSurvey($choiceId, $txtfrom);
							echo '<br> Your answer was saved';						
						}
						else { echo $error = "The question choice ID: $smsData[0]#$questionId[0]'$choice[0]' was not identified."; return $error;  }
					}
					else { echo $error = "You already answered question $smsData[0]#$questionId[0]."; return $error;  }
				}
				else { echo $error = "The question ID: $smsData[0]#'$questionId[0]' was not identified."; return $error; }
			}
			else { echo $error = "The survey ID: '$smsData[0]'# could not be identified."; return $error; }
		}
}


$sms = new NexmoMessage('915555bc', '719f5772');
if ($sms->inboundText()) {
	//echo $sms->reply('You said: ' . $sms->text . ' your phone number is: ' . $sms->from);
	$txtfrom = $sms->from;
	$txttext = $sms->text;
	$txtnetwork = $sms->network;
	$txtmessage_id = $sms->message_id;
	$txtmessage_timestamp = $sms->message_timestamp;  
	saveSMS($txttext, $txtfrom,  $txtnetwork, $txtmessage_id, $txtmessage_timestamp);

	if( $choice = getChoice($txttext,  $txtfrom) ){
		//echo $txttext,  $txtfrom,  $txtnetwork, $txtmessage_id ;
		$msg = $choice;
		$sms->reply (  $msg .' Please try again.' );
	}
}

else{

 	/*

 	*/
}

?>