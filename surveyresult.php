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
 * Converts $sql query results into JSON
 * @param  string $sql SQL string
 * @return string      JSON string
 */
function jasonResult($sql){

	if($sql) {

		global $db;
		if(!$db) connectDb();

	   	$jsonArray = array("answers"=> array());
		$questionId = 0;
		$result = $db->query($sql);
		$index = -1;
		foreach($result as $row) {
			
			if($row["questionId"] != $questionId) {
				$index++;
				$jsonArray["answers"][] = array("question"=>$row["question"], "type"=>"multiple");//, "answer"=>array(array("title"=>$row["choice"], "votes"=>$row["vote"])));
			}
			
			$jsonArray["answers"][$index]["answer"][] = array("title"=>$row["choice"], "votes"=>$row["vote"]);
			$questionId = $row["questionId"];
		}
		
		$json = json_encode($jsonArray);
		echo $json;

		//$jsonArray = array("answers"=> array(array("question"=>"Please provide us with your technical area of expertise","type"=>"multiple", "answer"=>array(array("title"=>"Financial Services", "votes"=>"111111"), array("title"=>"Enterprise Development", "votes"=>"222"), array("title"=>"Economic Strengthening", "votes"=>"333"), array("title"=>"Other", "votes"=>array("IT", "Health", "Administrative")))), array("question"=>"What is you level of Interest in cross sectoral programming?", "type"=>"multiple", "answer"=>array(array("title"=>"Financial Services", "votes"=>"111111"), array("title"=>"Enterprise Development", "votes"=>"222"), array("title"=>"Economic Strengthening", "votes"=>"333"), array("title"=>"Other", "votes"=>array("IT", "Health", "Administrative"))))));
	}
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
	$sql = "SELECT count(b.id) AS vote, b.choice_desc AS choice, c.description AS question, c.id AS questionId, d.description AS formName FROM surveyData AS a, formsQuestionChoice AS b, formsQuestion AS c, forms AS d WHERE (a.id_choice=b.id  AND b.question_id=c.id AND c.question_form_id=$formId) AND (";

	foreach ($arr as $key => $value) {
		$sql .= "(c.question_id=" . $value . " AND c.id = b.question_id AND c.question_form_id=" . $formId . ($key<(sizeof($arr)-1)?") OR ":")");
	}

	$sql .=  ") GROUP BY b.id";
	return $sql;
}


?>