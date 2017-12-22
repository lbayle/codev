<?php

$g_app->group('/commandSets', function() use ( $g_app ) {
	$g_app->get( '', 'rest_commandSets_get' );
	$g_app->get( '/', 'rest_commandSets_get' );
	$g_app->get( '/{id}', 'rest_commandSets_get' );
	$g_app->get( '/{id}/', 'rest_commandSets_get' );
	$g_app->post( '', 'rest_commandSets_add' );
	$g_app->post( '/', 'rest_commandSets_add' );

	#commands
	$g_app->post( '/{id}/commands/', 'rest_commandSets_issues_add' );
	$g_app->post( '/{id}/commands', 'rest_commandSets_issues_add' );
	$g_app->post( '/{id}/commands/{command_id}', 'rest_commandSets_commands_add' );
	$g_app->post( '/{id}/commands/{command_id}/', 'rest_commandSets_commands_add' );
});

/**
 * A method that does the work to handle getting a commandSet via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commandSets_get( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$commandSet_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );
	
	$team=new Team($_SESSION['teamid']);
	
	if(!is_null($commandSet_id)) {
		if(array_key_exists($commandSet_id,$team->getcommandSetList())){
			$commandSet=new CommandSet($commandSet_id);
			$t_result = array( 'commandSet' => $commandSet->getName());
		}
		else{
			return $p_response->withStatus( 200 )->withJson( "Command with id ". $commandSet_id." doesn't exists, or you don't have the right to access it" );
		}
	}
	else{
		$t_result = array( 'commandSets' => $team->getcommandSetList());
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle creating a commandSet via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commandSets_add( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$team_id = isset( $p_args['team_id'] ) ? $p_args['team_id'] : $p_request->getParam( 'team_id' );
	$name = isset( $p_args['name'] ) ? $p_args['name'] : $p_request->getParam( 'name' );
	
	try{
		CommandSet::create($name, $team_id);
		$t_result = array( 'CommandSet created');
	}
	catch (Exception $e) {
		$t_result = array( 'CommandSet already exists');
	}
	
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

/**
 * A method that does the work to handle creating a link between commandSet and command via Rest API
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_commandSets_commands_add( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	$commandSet_id = isset( $p_args['id'] ) ? $p_args['id'] : $p_request->getParam( 'id' );
	$command_id = isset( $p_args['command_id'] ) ? $p_args['command_id'] : $p_request->getParam( 'command_id' );
	$command_type = isset( $p_args['command_type'] ) ? $p_args['command_type'] : $p_request->getParam( 'command_type' );

	if(is_null($command_type)) {
		$command_type=1;
	}

	$team=new Team($_SESSION['teamid']);

	if(array_key_exists($commandSet_id,$team->getcommandSetList())){
		if(!is_null($commandSet_id)) {
			$commandSet=new CommandSet($commandSet_id);
			if(array_key_exists($command_id,$team->getcommands())){
				$command_selection=$commandSet->getCommands($command_type);
				if(array_key_exists($command_id,$command_selection)){
					$t_result = "This link already exists";
				}
				else{
					$commandSet->addCommand($command_id, $command_type);
					$t_result = "Link between commandSet ".$commandSet_id." and command ".$command_id." added with the type ".$command_type;
				}
			}
			else{
				$t_result = "Command with id ".$command_id." doesn't exist";
			}
		}
		else{
			$t_result = "You have to enter the command_id";
		}
	}
	else{
		$t_result = "CommandSet with id ". $commandSet_id." doesn't exists";
	}

	return $p_response->withStatus( 200 )->withJson( $t_result );
}

?>
