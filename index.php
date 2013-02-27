<?php
require "vendor/autoload.php";
require "surveyresult.php";

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
			foreach($questionsArrs as $qArr) {
				if(is_array($qArr)) {
					echo "The connected questions query is not implemented yet<br />";
				}
				else {
					echo "Single question query<br />";
					$sql = buldSqlQuery(array ($qArr), $idForm);
					echo jasonResult($sql);
				}
			}
		}
		else {
			$sql = buldSqlQuery($questionsArrs, $idForm);
			echo jasonResult($sql);
		}
	}
	else echo "ERROR: Wrong FormID or QuestionID";
});


$app->run();

?>






