<?php
require "vendor/autoload.php";
require "functions.php";

$app = new \Slim\Slim();
$app->get('/results/form/:form/:questions(/)', function ($form, $questions) {

	$questionsArrs = processReqString($questions);
	if(questionIdsExist($questionsArrs, $form)) {
		if(hasArray($questionsArrs)) {
			$resultsArr = array();
			foreach($questionsArrs as $qArr) {
				if(is_array($qArr)) {
					//Related questions query
					$sql = buildRelatedSqlQuery($qArr, $form);
					$resultArr = getRelatedJsonArray($sql);
				}
				else {
					//Single question query
					$sql = buldSqlQuery(array ($qArr), $form);
					$resultArr = getJsonArray($sql);
				}
					$resultsArr["answers"][] = $resultArr["answers"];
			}

		$json = json_encode($resultsArr);
		echo $json;
		}
		else {
			$sql = buldSqlQuery($questionsArrs, $form);
			$resultArr = getJsonArray($sql);
			$resultsArr["answers"][] = $resultArr["answers"];
			$json = json_encode($resultsArr);
			echo $json;
		}
	}
	else echo "ERROR: Wrong FormID or QuestionID";
});

$app->get('/results/form/:form/:questions/graphs(/)', function ($form, $questions) {
	$url = 'html/graphs.html';
	$html = ' <!doctype html>
	<html><head>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>

	<script type="text/javascript">
         var form = "' . $form  . '";
         var questions = "' . $questions . '";
	 </script>
	';
	$html .= file_get_contents($url);
	echo $html;
});

$app->run();

?>
