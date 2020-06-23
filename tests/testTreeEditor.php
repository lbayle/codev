<?php

require('../include/session.inc.php');

require('../path.inc.php');

class TestTreeEditor extends Controller {

   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         $data = array(
           'test' => 1
         );
         $smartyPrefix = 'fancy_';
         $this->smartyHelper->assign($smartyPrefix.'data', $data);

         $this->smartyHelper->assign('wbsRootId', "574870"); // 574870, 2037441, 1963286

      }
   }

}

// ========== MAIN ===========
TestTreeEditor::staticInit();
$controller = new TestTreeEditor('../', 'TEST Fancytree Editor', 'Tests');
$controller->execute();
