<?php
require('../include/session.inc.php');
/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

if (Tools::isConnectedUser()) {

	// this will log CLIENT Javascript errors in CodevTT Server logs.
	// see js/jsErrorCatch.js (which will return the error via Ajax)

	$logger = Logger::getLogger("JsErrorCatch");

	if ($logger->isDebugEnabled()) {

		$desc = Tools::getSecureGETStringValue('desc');
		$line = Tools::getSecureGETStringValue('line');
		$useragent = Tools::getSecureGETStringValue('useragent');
		$os = Tools::getSecureGETStringValue('os');
		$url = Tools::getSecureGETStringValue('url');
		$file = Tools::getSecureGETStringValue('file');

		$userid = $_SESSION['userid'];

		$logger->debug("JS_CLIENT_ERROR: $desc --- user=$userid, URL=$url, file=$file, line=$line, OS=$os, USERAGENT=$useragent");
	}
}
