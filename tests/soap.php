<?php
require('../include/session.inc.php');

/*
   This file is part of CoDevTT.

   CoDevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

/**
* Example usage (using jQuery):
* var url = "/path/mantisconnect_json.php?name=mc_project_get_issues&project_id=0&page_number=1&per_page=10";
* $.getJSON(url, function(data) {
* $.each(data, function() {
* console.log(data.id + ': ' data.summary);
* });
* });
*/

class MantisSoap {
   
   const getIssue = "mc_issue_get";

   /**
    * @var Logger The logger
    */
   private static $logger;
   
   /**
    * @var string[] The conf
    */
   private static $conf;
   
   /**
    * @var string[] The conf
    */
   private static $mantisConnectUrl;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
      
      // URL to your Mantis SOAP API (the mantisconnect.php file)
      self::$mantisConnectUrl = 'http://pc560/mantis/api/soap/mantisconnect.php';

      // the username/password of the user account to use for calls
      self::$conf = array(
          'username' => 'nvelin',
          'password' => 'secret'
      );
   }

   public function execute() {
      $args = parse_str($_SERVER['QUERY_STRING'], $args);
      self::soapRequest($args);
   }

   public static function soapRequest($args) {
      // get SOAP function name to call
      if (!isset($args['action'])) {
          die("No action specified.");
      }
      $function_name = $args['action'];

      // remove function name from arguments
      unset($args['action']);

      // prepend username/passwords to arguments
      $args = array_merge(self::$conf,$args);

      // connect and do the SOAP call
      try {
          $client = new SoapClient(self::$mantisConnectUrl.'?wsdl');
          $result = $client->__soapCall($function_name, $args);
          echo "URL : ".self::$mantisConnectUrl.'?wsdl'."<br>";
          echo "Method : ".$function_name."<br>";
          echo "Args : "."<br>";
          var_dump($args);
          echo "<br>";
      } catch (SoapFault $e) {
         self::$logger->error("Error with Mantis SOAP",$e);
         $result = array(
             'error' => $e->faultstring
         );
      }

      return $result;
   }

}

MantisSoap::staticInit();

// ========== MAIN ===========
if(isset($_SESSION['userid']) && isset($_GET['action'])) {
   $view = new MantisSoap();
   $view->execute();
}

// Test
$args = array(
   'action' => MantisSoap::getIssue,
   'issue_id' => 884
);
$issueSoap = MantisSoap::soapRequest($args);
var_dump($issueSoap);

?>
