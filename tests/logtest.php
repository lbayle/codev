<?php
include_once '../path.inc.php';
require_once('lib/log4php/Logger.php');

#Logger::configure(dirname(__FILE__).'/../lib/log4php___/src/examples/resources/appender_echo.properties');
Logger::configure(dirname(__FILE__).'/../log4php.xml');

$logger = Logger::getLogger("logtest");

$logger->debug("Hello World!");
$logger->info("We have liftoff.");

echo "config file " . $logger->getConfigurationFile();

?>
