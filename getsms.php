<?php

include ( "Nexmo-lib/NexmoMessage.php" );

$dbuser ="root";
$dbpass= "eR1Izka8UxWhbgNp8D9N";
$dbname= "FHIsurvey";
//$error = '';



$chconn = mysql_connect('127.0.0.1', $dbuser, $dbpass) or die(mysql_error());
mysql_select_db($dbname) or die(mysql_error());






function saveSMS($txttext,  $txtfrom,  $txtnetwork, $txtmessage_id, $txtmessage_timestamp){
//Record into InboundSMS
		      	$sql2 = "INSERT INTO InboundSMS (fromtxt, totxt, content, date_created, date_received) VALUES ('$txtfrom','$txtnetwork', '$txttext', '$txtmessage_timestamp', now())";
		    	mysql_query($sql2);
		    	//echo 'sewa'.$sql2 ;

}


function getFormValue($formName){


	$sql3 = "SELECT form_id FROM forms WHERE  form_id ='$formName' ";
	$result  = mysql_query($sql3);
	$formNameRes = mysql_result($result, 0);
	if( mysql_result($result, 0)){
		return $formNameRes ;
	}
	$error = "The survey ID #'$formName' could not be identified";
	return false;
}


function getQuestionId($questionId, $formName){

$sql4 = "SELECT formsQuestion.id FROM formsQuestion, forms WHERE  formsQuestion.question_id ='$questionId' AND formsQuestion.question_form_id=forms.id AND forms.form_id='$formName'";
	$result  = mysql_query($sql4);
	$questionID = mysql_result($result, 0);
	if( $questionID){
		return $questionID ;
	}
	else
	{
		$error = "The Question ID '$questionId' was not identified";
		return false;
		
	}

}


function getQuestionChoice($choiceId, $questionId){


//$sql5 = "SELECT a.id FROM formsQuestionChoice as a, formsQuestion as b WHERE  a.question_id='$choiceId' and b.question_id='$questionId' ";
$sql5 = "SELECT a.id FROM formsQuestionChoice AS a, formsQuestion AS b WHERE  a.question_id=b.id AND b.id=$questionId AND a.choice_id='$choiceId'";


	$result  = mysql_query($sql5);
	$choiceIdRes = mysql_result($result, 0);
	if( $choiceIdRes){

		return $choiceIdRes ;
		
	}
	else
	{
		$error = "The question choice ID: [$choiceId] was not identified";
		return false;
		
	}

}


function SaveSurvey($formIdSub, $questionId, $choiceId, $txtfrom){
		$sql6 ="INSERT INTO surveyData  (id_choice, sms_number, date_created) values ($choiceId ,  $txtfrom, now())";
		      	mysql_query($sql6);
		    	
echo $sql6;

}



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
			//Get the value of the form
					$getFormValue= getFormValue($smsData[0]);

					if(getFormValue($smsData[0]) ){
							
						
							//Verify the ID of the question and the choice - to start let's split digit and letter
							 trim($smsData[1]) ;
							 preg_match('/\d+/',  $smsData[1], $questionId);  //Getting the Digit part of the answer
							 preg_match('/[A-Za-z]+/',  $smsData[1], $choice); //Getting the choice, letter part of the answer
							 
										 if ($questionID = getQuestionId($questionId[0], $smsData[0] )){							
														

														if (getQuestionChoice( $choice[0], $questionID)){
															$choiceId = getQuestionChoice( $choice[0], $questionID);
															//Save the information submitted into DB
															SaveSurvey($formIdSub, $questionId[0], $choiceId, $txtfrom);
															echo '<br> Your answer was saved' ;
															
														}
														else{ $error = "The question choice ID: $smsData[0]#$questionId[0]'$choice[0]' was not identified."; return $error;  }
											}
											else{$error = "The question ID: $smsData[0]#'$questionId[0]' was not identified."; return $error; }

					}
					else{ $error = "The survey ID: '$smsData[0]'# could not be identified."; return $error; }

						
		}


}



//Recieving SMS 

     $sms = new NexmoMessage('915555bc', '719f5772');
     if ($sms->inboundText()) {
     /* echo */   //$sms->reply('You said: ' . $sms->text . ' your phone number is: ' . $sms->from);
	      $txtfrom = $sms->from;
		  $txttext = $sms->text;
		  $txtnetwork = $sms->network;
		  $txtmessage_id = $sms->message_id;
		  $txtmessage_timestamp = $sms->message_timestamp;
      
			saveSMS($txttext, $txtfrom,  $txtnetwork, $txtmessage_id, $txtmessage_timestamp);
					if( getChoice($txttext,  $txtfrom) ){
					    //echo $txttext,  $txtfrom,  $txtnetwork, $txtmessage_id ;
					    $msg = getChoice($txttext);
					   $sms->reply (  $msg .' Please try again.' );

					}
     }

     else{

     	/*








     	*/



     }




//[Mon Feb 04 11:35:11 2013] [error] [client 174.36.197.201] PHP Parse error:  syntax error, unexpected T_OBJECT_OPERATOR, expecting ')' in /var/www/www/survey/getsms.php on line 21




/*

public $to = '';
	public $from = '';
	public $text = '';
	public $network = '';
	public $message_id = '';

*/




?>
