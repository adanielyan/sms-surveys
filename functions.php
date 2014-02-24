<?php
/**
 * Defines functions for processing text messages and for generating quesry results in JSON format.
 */


$dbuser ="data";
$dbpass= "VzRmWlquf7HJNUHhGS7e";
$dbname= "data";
$db = null;

/**
 * Database connector
 */
function connectDb()
{
	global $db, $dbname, $dbuser, $dbpass;
	$db = new PDO('mysql:host=localhost;dbname='. $dbname .';charset=utf8', $dbuser, $dbpass);
}

/**
 * Saves data submitted by user as it is
 * @param  string $txttext
 * @param  string $txtfrom             
 * @param  string $txtnetwork          
 * @param  string $txtmessage_id       
 * @param  string $txtmessage_timestamp
 * @return integer or false
 */
function saveSMS($txttext,  $txtfrom,  $txtnetwork, $txtmessage_id, $txtmessage_timestamp){
	global $db;
	if(!$db) connectDb();

  	$sql = "INSERT INTO inbound_sms (fromtxt, totxt, content, date_created, date_received) VALUES ('$txtfrom','$txtnetwork', '$txttext', '$txtmessage_timestamp', now())";
	$result = false;
	try {
		$result = $db->exec($sql);

	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}
	return $result;
}

/**
 * Gets choice id by given choice 
 * @param  string $choice   choice (i.e. a, b or c)
 * @param  string $questionId question.id
 * @return string or null
 */
function getChoiceId($choice, $questionId){
	global $db;
	if(!$db) connectDb();

	$sql = "SELECT a.id FROM choices AS a, questions AS b WHERE  a.question_id=b.id AND b.id=$questionId AND a.choice='$choice'";

	$result = null;
	try {
		echo $sql; echo '<br />';
		$result = $db->query($sql);

	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}
	return $result===false?null:$result->fetch(PDO::FETCH_OBJ)->id;

}

/**
 * Saves processed data submitted by user
 * @param string $choiceId  
 * @param string $txtfrom   sender's phone number
 * @return integer or false
 */
function SaveSurvey($choiceId, $txtfrom){
	global $db;
	if(!$db) connectDb();

	$sql ="INSERT INTO survey_data  (choice_id, sms_number, date_created) values ($choiceId, $txtfrom, now())";
	$result = false;
	try {
		$result = $db->exec($sql);

	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}
	return $result;
}

/**
 * Gets the real id of question by user friendly id and form id
 * @param  string $questionId User friendly id
 * @param  string $form       form id
 * @return string or null
 */
function getQuestionId($questionId, $form){

	global $db;
	if(!$db) connectDb();

	$formId = getFormId($form);
	if($formId) {
		$sql = "SELECT id FROM questions WHERE  question ='$questionId' and form_id='$formId'  ";

		try {
			$result = $db->query($sql)->fetch(PDO::FETCH_OBJ);
		}
		catch (PDOException $e) {
			$error = $e->getMessage();
		}

		if($result) 
			return $result->id;
		else
			return null;
	}
} 

/**
 * Splits the given coma separated questions into array
 * @param  string $a
 * @return array
 */
function splitQuestions($a) {

	if(strpos($a, ",")) {
		$t = preg_split("/,/", $a);
		return $t;
	}
	else {
		$t = array($a);
		return $a;
	}
}

/**
 * Checks if given array contains arrays
 * @param  array
 * @return boolean
 */
function hasArray($arr) {
	foreach ($arr as $value) {
		if(is_array($value))
			return true;
	}
	return false;
}

/**
 * Converts submitted string of question IDs into array
 * @param  string $req
 * @return array
 */
function processReqString ($req) {

	$question = explode("|", $req);
	if(sizeof($req) ==0 ) {
		echo  'error: The provided string is incorrect';
		return null;
	}
	$arr = array_map("splitQuestions", $question);
	return $arr;


}

/**
 * Checks if questions with given IDs exist
 * @param  array $arr  array of questions
 * @param  string $form form ID
 * @return boolean Returns false even if one of the given question IDs doesn't exist
 */
function questionIdsExist($arr, $form) {

	$formId = getFormId($form);
	if($formId) {
		foreach ($arr as $value) {
			if(is_array($value)) {
				questionIdsExist($value, $form);
			}
			else {
				if(getQuestionId($value, $form) == null) return false;
			}
		}
		return true;
	}
	else return false;
}

/**
 * Checks if the user has already answered that question
 * @param  string $phone  user's phone number
 * @param  string $question  question ID
 * @param  string $form form ID
 */
function alreadyAnswered($sender, $questionId) {

	global $db;
	$sql = "SELECT DISTINCT survey_data.id FROM survey_data, (SELECT choices.id AS cid FROM choices WHERE question_id =".$questionId.") AS c WHERE survey_data.sms_number =  '".$sender."' AND c.cid=survey_data.choice_id";

	// $result  = mysql_query($sql);
	if(!$db) connectDb();
	try {
		$result = $db->query($sql)->fetch(PDO::FETCH_OBJ);
	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}

	if($result) 
		return true;
	else
		return false;
}

/**
 * Gets the form real ID by user friendly ID
 * @param  string $form user friendly ID
 * @return string or null
 */
function getFormId($form) {

	global $db;
	$sql = "SELECT id FROM forms WHERE form=" . $form;
	// $result  = mysql_query($sql);
	if(!$db) connectDb();
	try {
		$result = $db->query($sql)->fetch(PDO::FETCH_OBJ);
	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}

	if($result) 
		return $result->id;
	else
		return null;
}

/**
 * Gets form name by id
 * @param  string $formId
 * @return string or null
 */
function getFormName($formId){

	global $db;
	$sql3 = "SELECT form FROM forms WHERE  form ='$formId' ";

	if(!$db) connectDb();
	try {
		$result = $db->query($sql)->fetch(PDO::FETCH_OBJ);
	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}

	if($result) 
		return $result->form;
	else
		return null;
}

/**
 * Checks is form with given name exists
 * @param  string $formName
 * @return boolean
 */
function formExists($formName){
	global $db;
	$sql = "SELECT form FROM forms WHERE  form ='$formName' ";

	if(!$db) connectDb();
	try {
		$result = $db->query($sql);
	}
	catch (PDOException $e) {
		$error = $e->getMessage();
	}

	if($result!==false) 
		return true;
	else
		return false;
}

/**
 * Builds the SQL query based on given form ID and array of question IDs
 * @param  array  $arr  Array of questions
 * @param  string $form Form ID
 * @return string       Resulting SQL string
 */
function buldSqlQuery($arr, $form) {
	$formId = getFormId($form);

	$sql = "SELECT count(b.id) AS vote, b.description AS choice, b.id AS choice_id, c.description AS question, c.id AS questionId, d.description AS formName FROM survey_data AS a LEFT JOIN choices AS b ON a.choice_id = b.id LEFT JOIN questions AS c ON c.id = b.question_id LEFT JOIN forms AS d ON c.form_id = d.id WHERE c.form_id=$formId AND (";

	foreach ($arr as $key => $value) {
		$sql .= "(c.question=" . $value . " AND c.form_id=" . $formId . ($key<(sizeof($arr)-1)?") OR ":")");
	}

	$sql .=  ") GROUP BY b.id";
	return $sql;
}

/**
 * Builds the SQL query based on given form ID and array of connected question IDs
 * @param  array  $arr  Array of questions
 * @param  string $form Form ID
 * @return string       Resulting SQL string of for getting the connected answers
 */
function buildRelatedSqlQuery($arr, $form) {
	$formId = getFormId($form);
	$sql = "SELECT forms.description AS formName, fq1.description AS question1, fq1.id AS question_id1, fqc1.description AS choice1, fqc1.id AS choice_id1 ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= ", question".($key+1)." ";
		$sql .= ", question_id".($key+1)." ";
		$sql .= ", choice".($key+1)." ";
		$sql .= ", choice_id".($key+1)." ";
	}	
	$sql .= ", COUNT(fqc1.description) AS votes FROM ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "(SELECT DISTINCT sd".($key+1).".sms_number AS sender, fqc".($key+1).".description AS choice".($key+1).", fqc".($key+1).".id AS choice_id".($key+1).", fq".($key+1).".description AS question".($key+1).", fq".($key+1).".id AS question_id".($key+1).", f".($key+1).".description AS formName, f".($key+1).".id AS form_id FROM survey_data AS sd".($key+1)." LEFT JOIN choices AS fqc".($key+1)." ON sd".($key+1).".choice_id = fqc".($key+1).".id LEFT JOIN questions AS fq".($key+1)." ON fqc".($key+1).".question_id = fq".($key+1).".id LEFT JOIN forms AS f".($key+1)." ON fq".($key+1).".form_id = f".($key+1).".id WHERE f".($key+1).".id =".$formId." AND fq".($key+1).".question =".$value.") AS x".($key+1).", ";
	}
	$sql .= "survey_data AS sd1 LEFT JOIN choices AS fqc1 ON sd1.choice_id = fqc1.id LEFT JOIN questions AS fq1 ON fq1.id = fqc1.question_id LEFT JOIN forms ON fq1.form_id = forms.id  WHERE ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "sd1.sms_number = x".($key+1).".sender AND ";
		$sql .= "forms.id = x".($key+1).".form_id".($key<(sizeof($arr)-1)?" AND ":" ");
	}
	$sql .= "AND fq1.question =".$arr[0]." GROUP BY choice1, ";	
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "choice".($key+1).($key<(sizeof($arr)-1)?", ":" ");
	}
	//echo $sql;
	return $sql;
}

/**
 * Queries DB using $sql query and converts results into Array for further conversion to JSON string
 * @param  string $sql SQL string
 * @return array or null      Array for further conversion to JSON string
 */
function getJsonArray($sql){

	if($sql) {

		global $db;
		if(!$db) connectDb();
		$result = $db->query($sql);

		$questionId = 0;
		$jsonArray = array("answers"=>array());
		$index = -1;
		foreach($result as $row) {
			if($row["questionId"] != $questionId) {
				$index++;
				$jsonArray["answers"][] = array("question"=>$row["question"], "type"=>"multiple", "form_name"=>$row["formName"]);
			}
			
			$jsonArray["answers"][$index]["answer"][] = array("option_id"=>$row["choice_id"], "option"=>$row["choice"], "votes"=>$row["vote"]);
			$questionId = $row["questionId"];
		}
		
		return $jsonArray;
		
		//$jsonArray = array("answers"=> array(array("question"=>"Please provide us with your technical area of expertise","type"=>"multiple", "answer"=>array(array("title"=>"Financial Services", "votes"=>"111111"), array("title"=>"Enterprise Development", "votes"=>"222"), array("title"=>"Economic Strengthening", "votes"=>"333"), array("title"=>"Other", "votes"=>array("IT", "Health", "Administrative")))), array("question"=>"What is you level of Interest in cross sectoral programming?", "type"=>"multiple", "answer"=>array(array("title"=>"Financial Services", "votes"=>"111111"), array("title"=>"Enterprise Development", "votes"=>"222"), array("title"=>"Economic Strengthening", "votes"=>"333"), array("title"=>"Other", "votes"=>array("IT", "Health", "Administrative"))))));
	}
	else return null;
}

/**
 * Queries DB for related answers using $sql query and converts results into Array for further conversion to JSON string
 * @param  string $sql SQL string
 * @return array or null      Array of related answers for further conversion to JSON string
 */
function getRelatedJsonArray($sql){

	if($sql) {

		global $db;
		if(!$db) connectDb();

	   	$jsonArray = array("groups"=> array());
		$choiceId = 0;
		$total = 0;
		$result = $db->query($sql);
		$index = -1;
		foreach($result as $row) {
			$jsonArray["grouping_question"] = $row["question1"];
			$jsonArray["grouping_question_id"] = $row["question_id1"];
			$jsonArray["form_name"]=$row["formName"];
			if($row["choice_id1"] != $choiceId) {
				$index++;
				$jsonArray["groups"][] = array("option_id"=>$row["choice_id1"], "option"=>$row["choice1"], "answer"=>array("question"=>$row["question2"], "options"=>array()));
			}
			
			$jsonArray["groups"][$index]["answer"]["options"][] = array("title"=>$row["choice2"], "votes"=>$row["votes"]);
			$choiceId = $row["choice_id1"];
		}
		$jsonArray =  array($jsonArray);
		return array("answers"=>$jsonArray);
	}
	else return null;
}


?>
