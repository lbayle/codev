<?php
/*
   This file is part of CodevTT

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

/**
 * EmailData Structure Definition
 */
class EmailData {
	public $email_id = 0;
	public $email = '';
	public $subject = '';
	public $body = '';
	public $submitted = '';

	public $metadata = array(
		'headers' => array(),
	);
}

/**
 * Singleton email class
 *
 * This class adds emails to the MantisBT email queue.
 * If email is enabled in mantis, then CodevTT mail will be sent.
 *
 */
class Email {
   private static $logger;

   /**
    * Singleton
    * @var Email
    */
   private static $instance;

   /**
    * The singleton pattern
    * @static
    * @return Email
    */
   public static function getInstance() {
      if (NULL == self::$instance) {
         self::$instance = new Email();
      }
      return self::$instance;
   }

   private function __construct() {

      self::$logger = Logger::getLogger(__CLASS__);
   }


   /**
    * Store email in MantisBT queue for sending
    *
    * @param string $p_recipient Email recipient address.
    * @param string $p_subject   Subject of email message.
    * @param string $p_message   Body text of email message.
    * @param array  $p_headers   Array of additional headers to send with the email.
    * @return boolean
    */
   public function sendEmail( $p_recipient, $p_subject, $p_message, $p_submitted = null, array $p_headers = null ) {

      $t_recipient = trim( $p_recipient );
      $t_subject = self::string_strip_hrefs( trim( $p_subject ) );
      $t_message = trim( $p_message );

      # short-circuit if no recipient is defined, or email disabled
      # note that this may cause signup messages not to be sent
      if( Tools::is_blank( $p_recipient ) || ( 0 == Constants::$emailSettings['enable_email_notification']) ) {
         self::$logger->error('email notification is disabled');
         return FALSE;
      }

      $t_email_data = new EmailData();

      $t_email_data->email = $t_recipient;
      $t_email_data->subject = $t_subject;
      $t_email_data->body = $t_message;
      $t_email_data->submitted = $p_submitted;
      $t_email_data->metadata = array();
      $t_email_data->metadata['headers'] = $p_headers === null ? array() : $p_headers;
      $t_email_data->metadata['priority'] = 5; # Urgent = 1, Not Urgent = 5, Disable = 0
      $t_email_data->metadata['charset'] = 'utf-8';

      $t_hostname = 'codevtt_server';
      if( isset( $_SERVER['SERVER_NAME'] ) ) {
         $t_hostname = $_SERVER['SERVER_NAME'];
      }
      $t_email_data->metadata['hostname'] = $t_hostname;

      $retCode = $this->email_queue_add( $t_email_data );

      return $retCode;
   }

   /**
    * Add to email queue
    * @param EmailData $p_email_data Email Data structure.
    */
   private function email_queue_add( EmailData $emailData ) {

      # email cannot be blank
      if( Tools::is_blank( $emailData->email ) ) {
         self::$logger->error('Recipient email is missing');
         return FALSE;
      }

      # subject cannot be blank
      if( Tools::is_blank( $emailData->subject ) ) {
         self::$logger->error('email subject is blank');
         return FALSE;
      }

      # body cannot be blank
      if( Tools::is_blank( $emailData->body ) ) {
         self::$logger->error('email body is blank');
         return FALSE;
      }

      if( Tools::is_blank( $emailData->submitted ) ) {
         $emailData->submitted = time();
      }

      $sqlWrapper = SqlWrapper::getInstance();
      $c_email    = SqlWrapper::sql_real_escape_string($emailData->email);
      $c_subject  = SqlWrapper::sql_real_escape_string($emailData->subject);
      $c_body     = SqlWrapper::sql_real_escape_string($emailData->body);
      $c_metadata = serialize( $emailData->metadata );

      $query = "INSERT  INTO `mantis_email_table` (`email`, `subject`, `body`, `submitted`, `metadata`) ".
         "VALUES ('$c_email', '$c_subject', '$c_body', ".$emailData->submitted.", '$c_metadata');";
      #echo "queue email: $query<br>";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      #self::$logger->error('email sent to '.$emailData->email);
      return TRUE;
   }

   /**
    * Detect href anchors in the string and replace them with URLs and email addresses
    * @param string $p_string String to be processed.
    * @return string
    */
   private static function string_strip_hrefs( $p_string ) {
      # First grab mailto: hrefs.  We don't care whether the URL is actually
      # correct - just that it's inside an href attribute.
      $p_string = preg_replace( '/<a\s[^\>]*href="mailto:([^\"]+)"[^\>]*>[^\<]*<\/a>/si', '\1', $p_string );

      # Then grab any other href
      $p_string = preg_replace( '/<a\s[^\>]*href="([^\"]+)"[^\>]*>[^\<]*<\/a>/si', '\1', $p_string );
      return $p_string;
   }


}


