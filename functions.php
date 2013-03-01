<?php
/**
 * Defines functions for generating the JSON string of results based on given Form ID and Question ID(s)
 */


$dbuser ="root";
$dbpass= "eR1Izka8UxWhbgNp8D9N";
$dbname= "FHIsurvey";

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
		$sql = "SELECT id FROM formsQuestion WHERE  question_id ='$questionId' and question_form_id='$formId'  ";

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
 * Gets the form real ID by user friendly ID
 * @param  string $form user friendly ID
 * @return string or null
 */
function getFormId($form) {

	global $db;
	$sql = "SELECT id FROM forms WHERE form_id=" . $form;
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
 * Builds the SQL query based on given form ID and array of question IDs
 * @param  array  $arr  Array of questions
 * @param  string $form Form ID
 * @return string       Resulting SQL string
 */
function buldSqlQuery($arr, $form) {
	$formId = getFormId($form);
	$sql = "SELECT count(b.id) AS vote, b.choice_desc AS choice, b.id AS choice_id, c.description AS question, c.id AS questionId, d.description AS formName FROM surveyData AS a, formsQuestionChoice AS b, formsQuestion AS c, forms AS d WHERE (a.id_choice=b.id  AND b.question_id=c.id AND c.question_form_id=$formId) AND (";

	foreach ($arr as $key => $value) {
		$sql .= "(c.question_id=" . $value . " AND c.id = b.question_id AND c.question_form_id=" . $formId . ($key<(sizeof($arr)-1)?") OR ":")");
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
	$sql = "SELECT forms.description AS formName, fq1.description AS question1, fq1.id AS question_id1, fqc1.choice_desc AS choice1, fqc1.id AS choice_id1 ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= ", question".($key+1)." ";
		$sql .= ", question_id".($key+1)." ";
		$sql .= ", choice".($key+1)." ";
		$sql .= ", choice_id".($key+1)." ";
	}	
	$sql .= ", COUNT(fqc1.choice_desc) AS votes FROM ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "(SELECT DISTINCT sd".($key+1).".sms_number AS sender, fqc".($key+1).".choice_desc AS choice".($key+1).", fqc".($key+1).".id AS choice_id".($key+1).", fq".($key+1).".description AS question".($key+1).", fq".($key+1).".id AS question_id".($key+1).", f".($key+1).".description AS formName FROM surveyData AS sd".($key+1).", formsQuestionChoice AS fqc".($key+1).", formsQuestion AS fq".($key+1).", forms AS f".($key+1)." WHERE sd".($key+1).".id_choice = fqc".($key+1).".id AND fqc".($key+1).".question_id = fq".($key+1).".id AND f".($key+1).".id =".$formId." AND fq".($key+1).".question_form_id = f".($key+1).".id AND fq".($key+1).".question_id =".$value.") AS x".($key+1).", ";
	}
	$sql .= "forms, surveyData AS sd1, formsQuestion AS fq1, formsQuestionChoice AS fqc1 WHERE ";
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "sd1.sms_number = x".($key+1).".sender".($key<(sizeof($arr)-1)?" AND ":" ");
	}
	$sql .= "AND fq1.id = fqc1.question_id AND sd1.id_choice = fqc1.id AND fq1.question_id =".$arr[0]." GROUP BY choice1, ";	
	foreach ($arr as $key => $value) {
		if($key == 0) continue;
		$sql .= "choice".($key+1).($key<(sizeof($arr)-1)?", ":" ");
	}
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
		
		return array("answers"=>$jsonArray);
	}
	else return null;
}


?>