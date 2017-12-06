<?php


$g_app->group('/commands', function() use ( $g_app ) {
	$g_app->get( '', 'rest_commands_get' );
	$g_app->get( '/', 'rest_commands_get' );
	$g_app->get( '/{id}', 'rest_commands_get' );
	$g_app->get( '/{id}/', 'rest_commands_get' );
	$g_app->post( '', 'rest_commands_add' );
	$g_app->post( '/', 'rest_commands_add' );
	$g_app->delete( '', 'rest_commands_delete' );
	$g_app->delete( '/', 'rest_commands_delete' );
	$g_app->delete( '/{id}', 'rest_commands_delete' );
	$g_app->delete( '/{id}/', 'rest_commands_delete' );
	$g_app->put( '', 'rest_commands_update' );
	$g_app->put( '/', 'rest_commands_update' );
	$g_app->put( '/{id}', 'rest_commands_update' );
	$g_app->put( '/{id}/', 'rest_commands_update' );
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
	
	if(!is_null($command_id)) {
	
		# Get commands By Id
		#$t_commands = mc_commands_get( /* username */ '', /* password */ '', $command_id );

		#if( ApiObjectFactory::isFault( $t_commands ) ) {
		#	return $p_response->withStatus( $t_result->status_code, $t_result->fault_string );
		#}
		$command=new Command($command_id);
		$t_result = array( 'command' => $command->getId() );
	}
	else{
		$t_result = "t";
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}
