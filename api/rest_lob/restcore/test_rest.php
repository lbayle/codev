<?php

$g_app->group('/test', function() use ( $g_app ) {
	$g_app->get( '/version', 'rest_codevtt_version' );
	$g_app->get( '/version_date', 'rest_codevtt_version_date' );
});

/**
 * Get CodevTT Version
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_codevtt_version( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	return $p_response->withStatus( HTTP_STATUS_SUCCESS )->withJson( Config::codevVersion );
}

/**
 * Get CodevTT release date
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_codevtt_version_date( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) {
	return $p_response->withStatus( HTTP_STATUS_SUCCESS )->withJson( Config::codevVersionDate );
}

