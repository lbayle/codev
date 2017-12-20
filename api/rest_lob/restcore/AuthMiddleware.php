<?php

require('include/session.inc.php');
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

#require_api( 'authentication_api.php' );
#require_api( 'user_api.php' );


# C:\xampp\htdocs\prjmngt\mantisbt-2.9.0\core\constant_inc.php
# HTTP Status Codes
define( 'HTTP_STATUS_SUCCESS', 200 );
define( 'HTTP_STATUS_CREATED', 201 );
define( 'HTTP_STATUS_NO_CONTENT', 204 );
define( 'HTTP_STATUS_NOT_MODIFIED', 304 );
define( 'HTTP_STATUS_BAD_REQUEST', 400 );
define( 'HTTP_STATUS_UNAUTHORIZED', 401 );
define( 'HTTP_STATUS_FORBIDDEN', 403 );
define( 'HTTP_STATUS_NOT_FOUND', 404 );
define( 'HTTP_STATUS_CONFLICT', 409 );
define( 'HTTP_STATUS_PRECONDITION_FAILED', 412 );
define( 'HTTP_STATUS_INTERNAL_SERVER_ERROR', 500 );
define( 'HTTP_STATUS_UNAVAILABLE', 503 );

# HTTP HEADERS
define( 'HEADER_AUTHORIZATION', 'Authorization' );
define( 'HEADER_LOGIN_METHOD', 'X-Mantis-LoginMethod' );
define( 'HEADER_USERNAME', 'X-Mantis-Username' );
define( 'HEADER_VERSION', 'X-Mantis-Version' );
define( 'HEADER_IF_MATCH', 'If-Match' );
define( 'HEADER_IF_NONE_MATCH', 'If-None-Match' );
define( 'HEADER_ETAG', 'ETag' );

# LOGIN METHODS
define( 'LOGIN_METHOD_COOKIE', 'cookie' );
define( 'LOGIN_METHOD_API_TOKEN', 'api-token' );


/**
 * A middleware class that handles authentication and authorization to access APIs.
 */
class AuthMiddleware {
    
   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }    
    
	public function __invoke( \Slim\Http\Request $request, \Slim\Http\Response $response, callable $next ) {
		$t_authorization_header = $request->getHeaderLine( 'Authorization' );

		if( empty( $t_authorization_header ) ) {
			# Since authorization header is empty, check if user is authenticated by checking the cookie
			# This mode is used when Web UI javascript calls into the API.
			if( Tools::isConnectedUser() ) {
                        # TODO check PHP session 
                        # return $response->withStatus( HTTP_STATUS_UNAUTHORIZED, 'API to     ken required' );
			} else {
	            self::$logger->error("RestAPI: API token required");
	            return $response->withStatus( HTTP_STATUS_UNAUTHORIZED, 'API token required' );
			}
		} else {

			$hashToken = hash( 'sha256', $t_authorization_header );
			$query = "SELECT user_id FROM `mantis_api_token_table` WHERE hash = '$hashToken'";
		    $result = SqlWrapper::getInstance()->sql_query($query);
		    if ($result) {
		    	$row = mysql_fetch_row($result);

		        $_SESSION['userid'] = $row[0];
		        $query = "SELECT id, username, realname, last_visit FROM `mantis_user_table` WHERE id = '$_SESSION[userid]'";
		    	$result = SqlWrapper::getInstance()->sql_query($query);
		        if($result){
		    		$row = mysql_fetch_row($result);
			        $_SESSION['username'] = $row[1];
			        $_SESSION['realname'] = $row[2];
			        $lastVisitTimestamp = $row[3];

			        try {
			            $user =  UserCache::getInstance()->getUser($row[0]);

			            $locale = $user->getDefaultLanguage();
			            if (NULL != $locale) { $_SESSION['locale'] = $locale; }

			            $teamid = $user->getDefaultTeam();
			            if (0 != $teamid) {
			               $_SESSION['teamid'] = $teamid;
			               
			            } else {
			               // no default team (user's first connection): 
			               // find out if user is already affected to a team and set as default team
			               $query = "SELECT team_id FROM `codev_team_user_table` WHERE user_id = '".$user->getId().
			                       "' ORDER BY arrival_date DESC LIMIT 1;";
			               $result = SqlWrapper::getInstance()->sql_query($query);
			               if ($result && 1 == SqlWrapper::getInstance()->sql_num_rows($result)) {
			                  $row = SqlWrapper::getInstance()->sql_fetch_object($result);
			                  $teamid = $row->team_id;
			                  $user->setDefaultTeam($teamid);
			                  $_SESSION['teamid'] = $teamid;
			               }
			            }
			            
			            $projid = $user->getDefaultProject();
			            if (0 != $projid) { $_SESSION['projectid'] = $projid; }

			            $query2 = "UPDATE `mantis_user_table` SET last_visit = ".$now." WHERE username = '".$formattedUser."';";
			            SqlWrapper::getInstance()->sql_query($query2);

			         } catch (Exception $e) {
			            if ($isLog && self::$logger->isDebugEnabled()) {
			               $logger->debug("could not load preferences for user $row_login->id");
			            }
			         }
					if (($isLog) && ($now > ($lastVisitTimestamp + 2))) {
			            $ua = Tools::getBrowser();
			            $browserStr = $ua['name'] . ' ' . $ua['version'] . ' (' .$ua['platform'].')'; 
			            $logger->info('user '.$row_login->id.' '.$row_login->username.' ('.$row_login->realname.'), Team '.$user->getDefaultTeam().', '.$browserStr);
					}
				}
		    }
		    else{
		    	return $response->withStatus( 400, 'Token detected but problem during authentication' );
		    }
		}

		$t_force_enable = $t_login_method == LOGIN_METHOD_COOKIE;
		return $next( $request->withAttribute( ATTRIBUTE_FORCE_API_ENABLED, $t_force_enable ), $response )->
			withHeader( HEADER_USERNAME, "test")->
			withHeader( HEADER_LOGIN_METHOD, $t_login_method );
	}
}
AuthMiddleware::staticInit();