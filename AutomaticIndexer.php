<?php
include "porter2.php";

/**
 * Implementation of an automatic indexer for scraping keyword data from webpages.
 * 
 */
class AutomaticIndexer {

    /**
     * Static field for temporary page dump file name
     */
    private static $dumpfile = "pageDump.txt";
    
    private $stopwords;
    private $invertedIndex;

    /**
     * Object constructor
     * 
     *  @param string $stopwordFilename
     *      The name of the file containing stopwords to filter out of text.
     */
    function __construct($stopwordFilename) {
        $file = fopen($stopwordFilename, "r") or exit("unable to open $stopwordFilename");
        while(!feof($file)) {
            $stopword = trim(fgets($file));
            $this->stopwords[$stopword] = 1;
        }

        $this->invertedIndex = new InvertedIndex();
    }

    /**
     * Index the keywords of the webpage at the given url.
     * 
     * @param string $url
     *      The url of the webpage to scrape.
     * 
     * @return InvertedIndex $invertedIndex
     *      The updated inverted index containing scraped index data from the url.
     */
    public function index($url) {
        $urlId = hash('md5', $url);
        $tokenized = $this->tokenize($url);

        foreach($tokenized as $token) {
            $this->invertedIndex->index($token, $urlId);
        }

        return $this->invertedIndex;
    }

    /**
     * Takes an input url and tokenizes the raw text data of the webpage.
     * Filters out non-alphanumeric characters, converts to lowercase, and
     * removes stopwords from the text.
     * 
     * @param string $url
     *      The url of the webpage to tokenize.
     * 
     * @return array $tokenized
     *      An array of words (tokens) from the webpage text.
     */
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

    /**
     * Calls a system command to the lynx web browser that scrapes text data from a
     * webpage to a dumpfile.
     * 
     * @param string $url
     *      The url of the webpage to scrape.
     * 
     * @return resource|false
     *      Pointer to a file, or false if the file cannot be opened.
     */
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

/**
 * Implementation of an inverted index file that conatins a list of indexes that record
 * terms and their frequency throught multiple documentIds.
 */
class InvertedIndex {

    /**
     * Key => value pair
     * $termId => Index
     * $termId is the hashed value of the input term.
     */
    private $index;

    function __construct() {
        $this->index = [];
    }

    /**
     * Adds an index for a term, documentId pair, or increments the fequency of the term if
     * the term or documentId already exists in the index.
     * 
     * @param string $term
     *      The term to store or update.
     * 
     * @param string $documentId
     *      The id of the document being stored or updated.
     * 
     * @return void
     */
    public function index($term, $documentId) {
        $id = $this->getId($term);
        if(array_key_exists($id, $this->index)) {
            $this->index[$id]->adddocumentId($documentId);
        } else {
            $this->index[$id] = new Index($term);
            $this->index[$id]->adddocumentId($documentId);
        }
    }

    /**
     * Gets the index object for the implicit term.
     * 
     * @return Index $index
     *      The index object for the implicit term.
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * Hashes an id using 'md5' for the input term.
     * 
     * @param string $term
     *      Term to get id for.
     * 
     * @return string $termId
     *      Hashed id value of the term.
     */
    private function getId($term) {
        return hash("md5", $term);
    }
}

/**
 * Implementation of a single index (row) for a term in an inverted index.
 * Contains the term and a dictionary of documentID => frequency values.
 */
class Index {

    /**
     * Implicit term of the index.
     */
    private $term;
    /**
     * Dictionary of document and the term's frequency in the given document.
     * key => value
     * $documentId => $frequency
     */
    private $docFreq;

    /**
     * Constructor
     * 
     * @param string $term
     *      The term of the current index
     */
    function __construct($term) {
        $this->term = $term;
        $this->docFreq = [];
    }

    /**
     * Gets the term value.
     * 
     * @return string $term
     *      The term.
     */
    public function getTerm() {
        return $this->term;
    }

    /**
     * Gets the frequency of the term in a given document.
     * 
     * @param string $documentId
     *      Id of the document.
     * 
     * @return int $frequency
     *      Frequency of the term in the document.
     */
    public function getdocumentIdFrequency($documentIdId) {
        return $this->docFreq[$documentIdId];
    }

    /**
     * Sums the frequency of the term accross all documents.
     * 
     * @return int $totalFrequency
     *      Frequency of term across indexed documents.
     */
    public function getTotalFrequency() {
        $total = 0;
        foreach($this->docFreq as $key => $value) {
            $total += $value;
        }

        return $total;
    }

    /**
     * Adds a document to the term. If the document already exists, the frequency is incremented.
     * 
     * @param string $documentId
     *      Id value of the document to store or update.
     * 
     * @return void
     */
    public function addDocument($documentId) {
        if(array_key_exists($documentId, $this->docFreq)) {
            $this->docFreq[$documentId] += 1;
        } else {
            $this->docFreq[$documentId] = 1;
        }
    }
}

?>