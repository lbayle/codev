<?php

$g_app->group('/issues', function() use ( $g_app ) {
	$g_app->get( '', 'rest_issues_get' );
	$g_app->get( '/', 'rest_issues_get' );
	$g_app->get( '/{id}', 'rest_issues_get' );
	$g_app->get( '/{id}/', 'rest_issues_get' );
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
			$t_result = array( 'Issue' => $issue->getCommandList());
		}
		else{
			return $p_response->withStatus( 200 )->withJson( "Issue with id ". $issue_id." doesn't exists" );
		}
	}
	else{
		$commands=$team->getCommands();

		$arr = array(1, 2, 3, 4);
		foreach ($commands as &$command) {
			foreach ($command->getIssueSelection()->getIssueList() as &$issue){
			   	$t_result.='Issue id: '.$issue->getId().' is in category: '.$issue->getCategoryName().' - ';
			}
		}
	}
	return $p_response->withStatus( 200 )->withJson( $t_result );
}

?>
