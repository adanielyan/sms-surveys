<?php
require "vendor/autoload.php";
require "functions.php";

/*

$app = new \Slim\Slim();
$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});

$app->run();
*/
$app = new \Slim\Slim();
$app->get('/results/form/:idForm/:questions', function ($idForm, $questions) {

//$question = explode("|", $questions);
//print_r ($question);
	$questionsArrs = processReqString($questions);
	if(questionIdsExist($questionsArrs, $idForm)) {
		if(hasArray($questionsArrs)) {
			$resultsArr = array();
			foreach($questionsArrs as $qArr) {
				if(is_array($qArr)) {
//					echo "Related questions query<br />";
					$sql = buildRelatedSqlQuery($qArr, $idForm);
//					echo $sql;
					$resultArr = getRelatedJsonArray($sql);
				}
				else {
//					echo "Single question query<br />";
					$sql = buldSqlQuery(array ($qArr), $idForm);
					$resultArr = getJsonArray($sql);
//					echo $sql;
				}
				
				$resultsArr["answers"][] = $resultArr["answers"];
			}

//			print_r($resultsArr);
			$json = json_encode($resultsArr);
			echo $json;
		}
		else {
//			echo "STANDARD<br />";
			$sql = buldSqlQuery($questionsArrs, $idForm);
//			echo $sql;
			$resultArr = getJsonArray($sql);
			$resultsArr["answers"][] = $resultArr["answers"];
//			print_r($resultsArr); echo "<br />";
			$json = json_encode($resultsArr);
			echo $json;
		}
	}
	else echo "ERROR: Wrong FormID or QuestionID";
});

$app->get('/results/display/graphs', function () {
	$url = 'html/graphs.html';
	$html = file_get_contents($url);
	echo $html;
});

$app->run();

?>