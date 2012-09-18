<?php
require('../include/session.inc.php');

###############################################################
# File Download 1.31
###############################################################
# Visit http://www.zubrag.com/scripts/ for updates
###############################################################
# Sample call:
#    download.php?f=phptutorial.zip
#
# Sample call (browser will try to save with new file name):
#    download.php?f=phptutorial.zip&fc=php123tutorial.zip
###############################################################

require('../path.inc.php');

$logger = Logger::getLogger("download");

if (isset($_SESSION['userid'])) {
   // get user info
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   // CodevTT specific
   if (isset($_GET['importTemplates'])) {
      $codevReportsDir = '..'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;
   } else {
      $codevReportsDir = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR;
   }

   // Allow direct file download (hotlinking)?
   // Empty - allow hotlinking
   // If set to nonempty value (Example: example.com) will only allow downloads when referrer contains this text
   define('ALLOWED_REFERRER', '');

   // Download folder, i.e. folder where you keep all files for download.
   // MUST end with slash (i.e. "/" )
   define('BASE_DIR',$codevReportsDir);

   // Allowed extensions list in format 'extension' => 'mime type'
   // If myme type is set to empty string then script will try to detect mime type
   // itself, which would only work if you have Mimetype or Fileinfo extensions
   // installed on server.
   $allowed_ext = array (

      // archives
      'zip' => 'application/zip',

      // documents
      'pdf' => 'application/pdf',
      'doc' => 'application/msword',
      'xls' => 'application/vnd.ms-excel',
      'ppt' => 'application/vnd.ms-powerpoint',
      'csv' => '',

      // executables
      'exe' => 'application/octet-stream',

      // images
      'gif' => 'image/gif',
      'png' => 'image/png',
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',

      // audio
      'mp3' => 'audio/mpeg',
      'wav' => 'audio/x-wav',

      // video
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'mpe' => 'video/mpeg',
      'mov' => 'video/quicktime',
      'avi' => 'video/x-msvideo'
   );

   ####################################################################
   ###  DO NOT CHANGE BELOW
   ####################################################################

   // If hotlinking not allowed then make hackers think there are some server problems
   if (ALLOWED_REFERRER !== '' &&
      (!isset($_SERVER['HTTP_REFERER']) || strpos(strtoupper($_SERVER['HTTP_REFERER']),strtoupper(ALLOWED_REFERRER)) === false)) {
      $logger->error("Internal server error. Please contact system administrator.");
      die("Internal server error. Please contact system administrator.");
   }

   // Make sure program execution doesn't time out
   // Set maximum script execution time in seconds (0 means no limit)
   set_time_limit(0);

   if (!isset($_GET['f']) || empty($_GET['f'])) {
      $logger->error("Please specify file name for download.");
      die("Please specify file name for download.");
   }

   // Nullbyte hack fix
   if (strpos($_GET['f'], "\0") !== FALSE) die('');

   /**
    * Check if the file exists
    * Check in subfolders too
    * @param $dirname
    * @param $fname
    * @param $file_path
    * @return mixed
    */
   function find_file ($dirname, $fname, &$file_path) {
      $dir = opendir($dirname);

      while ($file = readdir($dir)) {
         if (empty($file_path) && $file != '.' && $file != '..') {
            if (is_dir($dirname.'/'.$file)) {
               find_file($dirname.'/'.$file, $fname, $file_path);
            }
            else {
               if (file_exists($dirname.'/'.$fname)) {
                  $file_path = $dirname.'/'.$fname;
                  return;
               }
            }
         }
      }
   }

   // Get real file name.
   // Remove any path info to avoid hacking by adding relative path, etc.
   $fname = basename($_GET['f']);

   // get full file path (including subfolders)
   $file_path = '';
   find_file(BASE_DIR, $fname, $file_path);
   if(self::$logger->isDebugEnabled()) {
      $logger->debug("BASE_DIR <".BASE_DIR."> file ".$fname);
   }

   if (!is_file($file_path)) {
      $logger->error("File <$file_path> does not exist.");
      die("File does not exist. Make sure you specified correct file name.");
   }

   // file size in bytes
   $fsize = filesize($file_path);

   // file extension
   $fext = strtolower(substr(strrchr($fname,"."),1));

   // check if allowed extension
   if (!array_key_exists($fext, $allowed_ext)) {
      $logger->error("Not allowed file type.");
      die("Not allowed file type.");
   }

   // get mime type
   if ($allowed_ext[$fext] == '') {
      $mtype = '';
      // mime type is not set, get from server settings
      if (function_exists('mime_content_type')) {
         $mtype = mime_content_type($file_path);
      }
      else if (function_exists('finfo_file')) {
         $finfo = finfo_open(FILEINFO_MIME); // return mime type
         $mtype = finfo_file($finfo, $file_path);
         finfo_close($finfo);
      }
      if ($mtype == '') {
         $mtype = "application/force-download";
      }
   }
   else {
      // get mime type defined by admin
      $mtype = $allowed_ext[$fext];
   }

   // Browser will try to save file with this filename, regardless original filename.
   // You can override it if needed.

   if (!isset($_GET['fc']) || empty($_GET['fc'])) {
      $asfname = $fname;
   }
   else {
      // remove some bad chars
      $asfname = str_replace(array('"',"'",'\\','/'), '', $_GET['fc']);
      if ($asfname === '') $asfname = 'NoName';
   }

   // set headers
   header("Pragma: public");
   header("Expires: 0");
   header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
   header("Cache-Control: public");
   header("Content-Description: File Transfer");
   header("Content-Type: $mtype");
   header("Content-Disposition: attachment; filename=\"$asfname\"");
   header("Content-Transfer-Encoding: binary");
   header("Content-Length: " . $fsize);

   // download
   // @readfile($file_path);
   ob_end_clean();
   $file = @fopen($file_path,"rb");
   if ($file) {
      while(!feof($file)) {
         print(fread($file, 1024*8));
         flush();
         if (connection_status()!=0) {
            @fclose($file);
            die();
         }
      }
      @fclose($file);
   }

   // log downloads
   $logger->info("user ".$session_user->getId()." (".$session_user->getName().") downloaded file: ".$fname);
} else {
   Tools::sendForbiddenAccess();
}

?>
