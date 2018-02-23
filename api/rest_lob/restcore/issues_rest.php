<?php

$g_app->group('/issues', function() use ( $g_app ) {
	$g_app->get( '', 'rest_issues_get' );
	$g_app->get( '/', 'rest_issues_get' );
	$g_app->get( '/{id}', 'rest_issues_get' );
	$g_app->get( '/{id}/', 'rest_issues_get' );
	$g_app->get( '/name/{issueName}', 'rest_issue_get_by_name' );
	$g_app->get( '/name/{issueName}/', 'rest_issue_get_by_name' );
	$g_app->patch( '', 'rest_issue_update' );
	$g_app->patch( '/', 'rest_issue_update' );
	$g_app->patch( '/{id}', 'rest_issue_update' );
	$g_app->patch( '/{id}/', 'rest_issue_update' );
});

/**
 * A method that does the work to handle getting an issue via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_issues_get( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$issue_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );

	$team=new Team($_SESSION['teamid']);
	
	if(!is_null($issue_id)) {
		if(Issue::exists($issue_id)){
			$issue=new Issue($issue_id);

			if (!empty($issue->getCommandList())){
				$t_result=array();
				foreach($issue->getCommandList() as $item){
					array_push($t_result, array('command' => $item, 'id'=>$issue_id, 'name'=> $issue->getSummary()));
				}				
			}
			else{
				return $p_response->withStatus( 400 )->withJson("The issue ".$issue_id." isn't in any command");
			}
			
		}
		else{
			return $p_response->withStatus( 400 )->withJson( "Issue with id ". $issue_id." doesn't exists" );
		}
	}
	else{
		$commands=$team->getCommands();

		$arr = array(1, 2, 3, 4);
		$t_result=array();
		foreach ($commands as &$command) {
			foreach ($command->getIssueSelection()->getIssueList() as &$issue){
				array_push($t_result, array('command' => $command->getId(), 'id'=>$issue->getId(), 'name'=> $issue->getSummary()));
			}
		}
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle getting an issue by its name via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_issue_get_by_name( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$issue_name = isset( $p_args['issueName'] ) ? $p_args['issueName'] : $p_request->getParam( 'issueName' );

	$team=new Team($_SESSION['teamid']);

	if(!is_null($issue_name)){
		$issue=new Issue(null, null, $issue_name);
		if (!empty($issue->getCommandList())){
			$t_result=array();
			foreach($issue->getCommandList() as $item){
				array_push($t_result, array('command' => $item, 'id'=>$issue->getId(), 'name'=> $issue->getSummary()));
			}				
		}
		else{
			return $p_response->withStatus( 400 )->withJson("The issue ".$issue_id." isn't in any command");
		}
	}
	else{
		$commands=$team->getCommands();

		$arr = array(1, 2, 3, 4);
		$t_result=array();
		foreach ($commands as &$command) {
			foreach ($command->getIssueSelection()->getIssueList() as &$issue){
				array_push($t_result, array('command' => $command->getId(), 'id'=>$issue->getId(), 'name'=> $issue->getSummary()));
			}
		}
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle updating an issue via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_issue_update( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$issue_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );
	$effortEst = isset( $p_args['effortEst'] ) ? $p_args['effortEst'] : $p_request->getParam( 'effortEst' );
	$mgrEffortEst = isset( $p_args['mgrEffortEst'] ) ? $p_args['mgrEffortEst'] : $p_request->getParam( 'mgrEffortEst' );
	$deadline = isset( $p_args['deadline'] ) ? $p_args['deadline'] : $p_request->getParam( 'deadline' );
	$extRef = isset( $p_args['extRef'] ) ? $p_args['extRef'] : $p_request->getParam( 'extRef' );
	$wbsTree = isset( $p_args['wbsTree'] ) ? $p_args['wbsTree'] : $p_request->getParam( 'wbsTree' );
	 
	$test=true;

	if(!is_null($issue_id)) {
		if(Issue::exists($issue_id)){
			$issue=new Issue($issue_id);
			if(!is_null($effortEst)){
				$issue->setEffortEstim($effortEst);
				$test=false;
			}
			if(!is_null($mgrEffortEst)){
				$issue->setMgrEffortEstim($mgrEffortEst);
				$test=false;
			}
			if(!is_null($deadline)){
				$issue->setDeadline($deadline);
				$test=false;
			}
			if(!is_null($extRef)){
				$issue->setExternalRef($extRef);
				$test=false;
			}
			/*if(!is_null($wbsTree)){
				$issue->setDeadline($wbsTree);
			}*/
			if($test){
				$t_result = "aucun changement";
			}
			else{
				$t_result="changement fait";
			}
		}
		else{
			return $p_response->withStatus( 400 )->withJson( "Issue with id ". $issue_id." doesn't exists" );
		}
	}
	else{
		return $p_response->withStatus( 400, 'No issue id found' );
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

?>
