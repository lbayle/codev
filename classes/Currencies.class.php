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
 * Important: There must be only one referal currency, with coef = 1.0
 *
 * Valued stored with 6 decimals, converted to int
 *
 * EUR = 1        => 1.0
 * USD = 930709   => 0.930709
 * GBP = 1153988  => 1.153988
 * CNY =  134703  => 0.1347038
 * INR =   14125  => 0.0141254
 * 
 * referalCur to other:  val / coef
 * other to referalCur:  cal * coef
 *
 * http://www.calculator.net/currency-calculator.html
 */
class Currencies {
   private static $logger;
   private static $instance;

   private $currencies;
   private $referalCurrency;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   private function __construct() {

      $this->currencies = array();
      $query0 = "SELECT * FROM codev_currencies_table";
      $result0 = SqlWrapper::getInstance()->sql_query($query0);

      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result0)) {
         $coef = $row->coef / 1000000; // (6 decimals)
         $this->currencies[$row->currency] = $coef;
         if (1 == $coef) { $this->referalCurrency = $row->currency; }
      }
      $errMsg = $this->checkConsistency();
      if ('SUCCESS' !== $errMsg) {
         throw new Exception($errMsg);
      }
   }

   private static function createInstance() {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c();
      }
      return self::$instance;
   }

   public static function getInstance() {
      if (!isset(self::$instance)) {
         self::createInstance();
      }
      return self::$instance;
   }

   public function getCurrencies() {
      return $this->currencies;
   }

   /**
    * check DB values are valid
    * 
    * @return string 'SUCCESS' or error message
    */
   public function checkConsistency() {

      if (0 === count($this->currencies)) {
         return 'ERROR: no currency defined';
      }

      $referalExists = false;
      foreach ($this->currencies as $name => $coef) {

         if (is_null($coef) || (0 === $coef)) {
            // avoid divisions by zero
            return "ERROR: invalid coef (null or zero) for currency : ".$name;
         }
         if (1 === $coef) {
            if ($referalExists) {
               return 'ERROR: multiple referal curencies defined (coef=1)';
            } else {
               $referalExists = true;
            }
         }
      }
      if (!$referalExists) {
         return "ERROR: no referal currency defined";
      }
      return 'SUCCESS';
   }

   /**
    * check if a new currency is valid
    * 
    * @param string $currency
    * @param float $coef
    * @return string 'SUCCESS' or error message
    */
   public function isValidCandidate($currency, $coef) {
      if (is_null($coef) || is_nan($coef) || (0 == $coef) ) {
         // avoid divisions by zero
         return T_('ERROR: invalid currency: (coef is null, zero, or not a number)');
      }
      if (0 === count($this->currencies)) {
         // if first currency, it must be referal
         if (1 != $coef) {
            return T_('ERROR: First define the referal currency (with coef=1)');
         }
      } else {
         // check coef : there can be only one 'referal' with coef = 1
         if (1 == $coef) {
            return T_("ERROR: there can be only one 'referal' currency (with coef=1)");
         }
         // check name does not already exist
         if (array_key_exists($currency, $this->currencies)) {
            return T_("ERROR: currency already defined");
         }
      }
      return 'SUCCESS';
   }


   /**
    * the coef is related to the referal currency
    *
    * @param string $currency 'EUR', 'USD', ...
    * @param type $coef
    * @throws Exception
    */
   public function setCurrency($currency, $coef) {

      $errMsg = $this->isValidCandidate($currency, $coef);
      if ('SUCCESS' != $errMsg) {
         throw new Exception($errMsg);
      }

      // convert float to int (ex: 0.930709 => 930709 (6 decimals)
      $dbCoef = round(floatval($coef), 6) * 1000000;

      $query = "INSERT INTO `codev_currencies_table`  (`currency`, `coef`) VALUES ('$currency','$dbCoef');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $this->currencies[$currency] = $coef;
   }

   /**
    * convert value to a different currency
    * 
    * referalCur to other:  val / coef
    * other to referalCur:  cal * coef
    *
    * @param type $value
    * @param type $sourceCurrency
    * @param type $targetCurrency
    * @throws Exception
    */
   public function convertValue($value, $sourceCurrency, $targetCurrency) {

      if ((!array_key_exists($sourceCurrency, $this->currencies)) ||
          (!array_key_exists($sourceCurrency, $this->currencies))) {
         $e = new Exception("currency is not defined ($sourceCurrency)");
         self::$logger->error("EXCEPTION convertValue: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      // first convert to referal currency
      if ($sourceCurrency != $this->referalCurrency) {
         $refValue = $value * $this->currencies[$sourceCurrency];
      } else {
         $refValue = $value;
      }
      // then convert to target currency
      if ($targetCurrency != $this->referalCurrency) {
         $targetValue = $refValue / $this->currencies[$targetCurrency];
      } else {
         $targetValue = $refValue;
      }

      //self::$logger->error("$value $sourceCurrency => $refValue $this->referalCurrency => $targetValue $targetCurrency");

      return $targetValue;
   }
}
Currencies::staticInit();

