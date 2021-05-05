<?php
include "porter2.php";

class AutomaticIndexer {

    private static $dumpfile = "pageDump.txt";
    
    private $stopwords;
    private $invertedIndex;

    function __construct($stopwordFilename) {
        $file = fopen($stopwordFilename, "r") or exit("unable to open $stopwordFilename");
        while(!feof($file)) {
            $stopword = trim(fgets($file));
            $this->stopwords[$stopword] = 1;
        }

        $this->invertedIndex = new InvertedIndex();
    }

    public function index($url) {
        $urlId = hash('md5', $url);
        $tokenized = $this->tokenize($url);

        foreach($tokenized as $token) {
            $this->invertedIndex->index($token, $urlId);
        }

        return $this->invertedIndex;
    }

    private function tokenize($url) {
        system("touch " . self::$dumpfile);
        // Get dumpfile of webpage contents
        $file = $this->webScrape($url);
        $tokenized = [];

        while(!feof($file)) {
        
            $line = fgets($file);
            $line = strtolower($line);
            $line = $this->filterCharacters($line);

            
            $tokens = preg_split('/\s/', $line);
            foreach($tokens as $token) {
        
                if(empty($token)) continue;
        
                if(!array_key_exists($token, $this->stopwords)) {
                    $token = Porter2::stem($token);
                    array_push($tokenized, $token);
                }
            }
        }

        fclose($file);
        system("rm " . self::$dumpfile);

        return $tokenized;
    }

    protected function webScrape($url) {
        if(system("lynx -dump -nolist '$url' > " . self::$dumpfile) === false) {
            exit("Unable to dump contents of $url to " . self::$dumpfile);
        }

        return fopen(self::$dumpfile, "r");
    }

    protected function filterCharacters($str) {
        // Filter out all non alphanumeric characters except for whitespace
        $str = preg_replace('/[^a-zA-Z\d\s]/', '', $str);
        // Filter out 'o' character that is commonly used as a bullet point
        // in lynx dump files.
        $str = preg_replace('/\s*o\s/', '', $str);
        // Filter multiple whitespace into single space character
        $str = str_ireplace(array("\r","\n","\t"), '', $str);
        return $str;
    }
}

class InvertedIndex {
    private $index;

    function __construct() {
        $this->index = [];
    }

    public function index($term, $document) {
        $id = $this->getId($term);
        if(array_key_exists($id, $this->index)) {
            $this->index[$id]->addDocument($document);
        } else {
            $this->index[$id] = new Index($term);
            $this->index[$id]->addDocument($document);
        }
    }

    public function getIndex() {
        return $this->index;
    }

    private function getId($term) {
        return hash("md5", $term);
    }
}

class Index {
    private $term;
    private $documents;

    function __construct($term) {
        $this->term = $term;
        $this->documents = [];
    }

    public function getTerm() {
        return $this->term;
    }

    public function getDocuments() {
        return $this->documents;
    }

    public function getReferenceCount() {
        return count($this->documents);
    }

    public function addDocument($document) {
        array_push($this->documents, $document);
    }
}

?>