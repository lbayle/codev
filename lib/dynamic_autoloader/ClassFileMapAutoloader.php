<?php

require_once('ClassFileMap.php');

/**
 * Autoloads classes using class file maps
 *
 * @author A.J. Brown
 * @package com.hypermuttlabs
 * @subpackage packaging
 */
class ClassFileMapAutoloader {

   /**
    * @var ClassFileMap[]
    */
   private $_aClassFileMaps = array();

   /**
    * Adds a class file map for use by this autoloader.  ClassFileMaps are grouped
    * by their name if the second parameter is true, resulting in a second
    * class file with the same name overwriting the first.
    *
    * @param ClassFileMap $oClassFileMap
    * @param bool $bUseName use the value of {@link ClassFileMap::getName()}
    *  as the key
    *
    * @return void
    */
   public function addClassFileMap(ClassFileMap $oClassFileMap, $bUseName = true) {
      if($bUseName) {
         $this->_aClassFileMaps[$oClassFileMap->getName()] = $oClassFileMap;
      }
      else {
         $this->_aClassFileMaps[] = $oClassFileMap;
      }
   }

   /**
    * Registers this class with the spl autloader stack.
    *
    * @return bool
    */
   public function registerAutoload() {
      return spl_autoload_register(array(&$this, 'autoload'));
   }

   /**
    * Autloads classes, if they can be found in the class file maps associated
    * with this autoloader.
    *
    * @param string $sClass
    * @return string the class name if found, otherwise false
    */
   public function autoload($sClass) {
      if(class_exists($sClass, false) || interface_exists($sClass)) {
         return false;
      }

      $sPath = $this->_doLookup($sClass);
      if ($sPath !== null) {
         require_once $sPath;
      }

      return true;
   }

   /**
    * Loop through class files maps untill a match is found
    *
    * @param string $sClassName
    * @return string the path of the class, or null if not found
    */
   private function _doLookup($sClassName) {
      foreach($this->_aClassFileMaps as $oClassFileMap) {
         $sPath = $oClassFileMap->lookup($sClassName);
         if (!is_null($sPath)) {
            return $sPath;
         }
      }

      return null;
   }

}

?>