<?php

$g_app->group('/commands', function() use ( $g_app ) {
	$g_app->get( '', 'rest_commands_get' );
	$g_app->get( '/', 'rest_commands_get' );
	$g_app->get( '/{id}', 'rest_commands_get' );
	$g_app->get( '/{id}/', 'rest_commands_get' );
	$g_app->post( '', 'rest_commands_add' );
	$g_app->post( '/', 'rest_commands_add' );

	#issues
	$g_app->post( '/{id}/issues/', 'rest_commands_issues_add' );
	$g_app->post( '/{id}/issues', 'rest_commands_issues_add' );
	$g_app->post( '/{id}/issues/{issue_id}', 'rest_commands_issues_add' );
	$g_app->post( '/{id}/issues/{issue_id}/', 'rest_commands_issues_add' );
});

/**
 * A method that does the work to handle getting a command via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commands_get( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$command_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );
	
	$team=new Team($_SESSION['teamid']);
	
	if(!is_null($command_id)) {
		if(array_key_exists($command_id,$team->getcommands())){
			$command=new Command($command_id);
			$t_result = array( 'command' => $command->getName(), 'id'=>$command->getId());
		}
		else{
			return $p_response->withStatus( 400 )->withJson( "Command with id ". $command_id." doesn't exists" );
		}
	}
	else{
		$t_result=array();
		foreach($team->getcommands() as $t){
			$command=new Command($t->getId());
			array_push($t_result, array('command' => $command->getName(), 'id'=>$command->getId()));
		}
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle creating a command via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commands_add( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$team_id = $_SESSION['teamid'];
	$name = isset( $p_args['name'] ) ? $p_args['name'] : $p_request->getParam( 'name' );
	
	try{
		Command::create($name, $team_id);
		$t_result = array( 'Command created');
	}
	catch (Exception $e) {
		$t_result = array( 'Command already exists');
		return $p_response->withStatus( 400 )->withJson( $t_result );
	}
	
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle creating a link between command and issue via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commands_issues_add( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$command_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );
	$issue_id = isset( $p_args['issue_id'] ) ? $p_args['issue_id'] : $p_request->getParam( 'issue_id' );
	$team=new Team($_SESSION['teamid']);

	$status=400;

	if(array_key_exists($command_id,$team->getcommands())){
		if(!is_null($command_id)) {
			$command=new Command($command_id);
			if(Issue::exists($issue_id)){
				$issue_selection=$command->getIssueSelection();
				if(array_key_exists($issue_id,$issue_selection->getIssueList())){
					$t_result = "This link already exists";
				}
				else{
					#CodevTTPlugin::assignCommand($issue_id, $command_id);
					$command->addIssue($issue_id);
					$t_result = "Link between command ".$command_id." and issue ".$issue_id." added";
					$status=200;
				}
			}
			else{
				$t_result = "This issue doesn't exist";
			}
		}
		else{
			$t_result = "You have to enter the issue_id";
		}
	}
	else{
		$t_result = "Command with id ". $command_id." doesn't exists";
	}
	
	return $p_response->withStatus( $status )->withJson( $t_result );
}

?>
