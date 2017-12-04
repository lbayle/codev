<?php

/**
 * A middleware class to handle adding a Mantis version response header.
 */
class VersionMiddleware {
	public function __invoke( \Slim\Http\Request $request, \Slim\Http\Response $response, callable $next )
	{
		return $next( $request, $response )->withHeader( 'X-CodevTT-Version', Config::codevVersion );
	}
}