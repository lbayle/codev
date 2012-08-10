<?php

/**
 * Manages a map of class names to their paths
 *
 * @author A.J. Brown
 * @package com.hypermuttlabs
 * @subpackage packaging
 */
class ClassFileMap {

   /**
    * @var string[]
    */
   private $_aClassMap;

   /**
    * Stores a name for this class file map.  For user usage.
    * @var string
    */
   private $_sName;

   /**
    * Stores the timestamp of this class file map's creation.
    * @var int
    */
   private $_iCreated;

   /**
    * Constructor
    *
    * @param string $sName an optional name to give this class file map.
    */
   function __construct($sName = null) {
      $this->_iCreated = time();
      $this->_sName = $sName;
   }

   /**
    * Maps the specified class to the specified path. If the first argument
    * is an array, it is expected that the array contains name-value pairs
    * of class names and thier paths.
    *
    * @param string|array  $mClassName the class name to map
    * @param string   $sPath the path to map to
    *
    * @return void
    */
   public function setClassPath($mClassName, $sPath = null) {
      if(is_array($mClassName)) {
         foreach($mClassName as $sClassName => $sPath) {
            $this->setClassPath($sClassName, $sPath);
         }

         return;
      }

      $this->_aClassMap[$mClassName] = $sPath;
   }

   /**
    * Retreives the name of this class
    *
    * @return string
    */
   public function getName() {
      return $this->_sName;
   }

   /**
    * Determine if a class is mapped
    *
    * @param string $sClassName
    * @return boolean true if the class is mapped
    */
   public function isMapped($sClassName) {
      return !empty($this->_aClassMap[$sClassName]);
   }

   /**
    * Returns the path of the file if mapped, otherwise null
    *
    * @param  string  $sClassName the class to lookup
    * @return  string the full path to the file containing the class
    */
   public function lookup($sClassName) {
      if (!$this->isMapped($sClassName)) return null;

      return $this->_aClassMap[$sClassName];
   }

   /**
    * Returns an array of classes which exist within a given file path
    *
    * @param string $sFileName
    * @return string[]|array[]
    */
   public function getClassesInFile($sFileName) {
      return array_keys($this->_aClassMap, $sFileName);
   }

   /**
    * Retreives the entire class-file map as an array with class names as keys
    * and their paths as values
    *
    * @return string[]
    */
   public function getClassMap() {
      return $this->_aClassMap;
   }
}

?>