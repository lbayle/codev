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
 * Description of LoadPerJobIndicator
 *
 * @author lob
 */
abstract class IndicatorPluginAbstract implements IndicatorPluginInterface  {

   protected static $domains;
   protected static $categories;

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception if initialization failed
    */
   public function __construct(PluginDataProviderInterface $pluginDataProv) {

      $this->initialize($pluginDataProv);
   }
   
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }

   public static function getSmartyFilename() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class().".html";
   }

   public static function getSmartySubFilename() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax.html";
   }

   public static function getAjaxPhpURL() {
      $sepChar = '/';
      return Constants::$codevURL.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax.php";
   }
   
}
