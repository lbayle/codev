<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
// ==============================================================
class IssueCache {
   
    // instance de la classe
    private static $instance;
    private static $issues;
    private static $callCount;

    // Un constructeur privé ; empêche la création directe d'objet
    private function __construct() 
    {
        self::$issues = array();
        self::$callCount = array();
        #echo "DEBUG: IssueCache ready<br/>";
    }

    // La méthode singleton
    public static function getInstance() 
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
   

    /**
     * get Issue class instance
     * @param $bugId
     */
    public function getIssue($bugId)
    {
        $issue = self::$issues[$bugId];
        
        if (NULL == $issue) {
            self::$issues[$bugId] = new Issue($bugId);
            $issue = self::$issues[$bugId];
            
            self::$callCount[$bugId] += 1;
            #echo "DEBUG: IssueCache add $bugId<br/>";
        } else {
            #echo "DEBUG: IssueCache called $bugId<br/>";
        }
        return $issue;
    }
} // class IssueCache


?>