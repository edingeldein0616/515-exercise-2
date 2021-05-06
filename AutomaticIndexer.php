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
    private static $sourcefile = "source.txt";
    private static $threshold;

    private $stopwords;
    private $invertedIndex;
    private $pageData;

    /**
     * Object constructor
     * 
     *  @param string $stopwordFilename
     *      The name of the file containing stopwords to filter out of text.
     * 
     *  @param int $threshold
     *      The minimum frequency of a term in a document. Terms under the threshold will be ignored on returns.
     */
    function __construct($stopwordFilename, $threshold = 1) {
        $file = fopen($stopwordFilename, "r") or exit("unable to open $stopwordFilename");
        while(!feof($file)) {
            $stopword = trim(fgets($file));
            $this->stopwords[$stopword] = 1;
        }

        $this->invertedIndex = new InvertedIndex();
        $this->pageData = [];
        self::$threshold = $threshold;
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
        system("touch " . self::$dumpfile . " " . self::$sourcefile);

        $docId = hash('md5', $url);

        $file = $this->webScrape($docId, $url);
        $tokenized = $this->tokenize($file);

        foreach($tokenized as $token) {
            $this->invertedIndex->index($token, $docId);
        }

        system("rm " . self::$dumpfile . " " . self::$sourcefile);
    }

    /**
     * Takes an file pointer and tokenizes the raw text data of the webpage.
     * Filters out non-alphanumeric characters, converts to lowercase, and
     * removes stopwords from the text.
     * 
     * @param string $file
     *      File pointer to webpage text dump file.
     * 
     * @return array $tokenized
     *      An array of words (tokens) from the webpage text.
     */
    private function tokenize($file) {        
        // Get dumpfile of webpage contents
        
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
    private function webScrape($docId, $url) {
    
        if(system("lynx -source '$url' > " . self::$sourcefile) === false) {
            exit("Unable to dump contents of $url to " . self::$sourcefile);
        }

        $source = fopen(self::$sourcefile, "r");
        while(!feof($source)) {
            $sourceContent = fgets($source);
        }
        fclose($source);

        // Get title
        $title = "*undefined*";
        $titlePattern = '/<title>.*?<\/title>/';
        preg_match($titlePattern, $sourceContent, $titleMatches);
        var_dump($titleMatches);
        if(count($titleMatches) > 0) $title = strip_tags($titleMatches[0]);

        // Get Description
        $description = "N/A";
        $tags = get_meta_tags($url);
        if($tags !== false) {
            $meta = array("\n","\r",";",">",">>","<","*");
            $description = str_replace($meta, '', $tags['description']);
        }

        $this->pageData[$docId] = [
            "url" => $url,
            "title" => $title,
            "description" => $description
        ];

        if(system("lynx -dump -nolist '$url' > " . self::$dumpfile) === false) {
            exit("Unable to dump contents of $url to " . self::$dumpfile);
        }

        return fopen(self::$dumpfile, "r");
    }

    /**
     * Filters out non-alphanumeric characters and bullet points common in lynx dump files.
     * Replaces all carriage returns, newlines and tabs with empty character.
     * 
     * @param string $str
     *      String to filter.
     * 
     * @return string $str
     *      Filtered string.
     */
    private function filterCharacters($str) {
        // Filter out all non alphanumeric characters except for whitespace
        $str = preg_replace('/[^a-zA-Z\d\s]/', '', $str);
        // Filter out 'o' character that is commonly used as a bullet point
        // in lynx dump files.
        $str = preg_replace('/\s*o\s/', '', $str);
        // Filter multiple whitespace into single space character
        $str = str_ireplace(array("\r","\n","\t"), '', $str);
        return $str;
    }

    public function getIndex() {
        return $this->invertedIndex->getIndexThreshold(AutomaticIndexer::$threshold);
    }

    public function getPageData() {
        return $this->pageData;
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
            $this->index[$id]->addDocument($documentId);
        } else {
            $this->index[$id] = new Index($term);
            $this->index[$id]->addDocument($documentId);
        }
    }

    /**
     * Gets the index object for the implicit term.
     * 
     * @return Index $index
     *      The index object for the implicit term.
     */
    public function getFullIndex() {
        return $this->index;
    }

    /** 
     * Gets a copy of the index with no term document frequencies below the threshold.
     * 
     * @param int $threshold
     *      The threshold used to filter out documents.
     * 
     * @return InvertedIndex
     *      A copy of the inverted index filtered by the threshold.
     */
    public function getIndexThreshold($threshold) {
        $cpy = [];

        foreach($this->index as $termId => $index) {
            $indexCpy = Index::thresholdCopy($index, $threshold);
            if($indexCpy)
                $cpy[$termId] = $indexCpy;
        }

        return $cpy;
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
     * Gets the document frequency dictionary.
     * 
     * @return array $docFreq
     *      Dicionary of document Id to term frequency.
     */
    public function getDocFreq() {
        return $this->docFreq;
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

    /**
     * Adds a document id with it's term frequency to the docFreq dictionary.
     * 
     * @param string $documentId
     *      The id of the document.
     * 
     * @param int $frequency
     *      The frequency of the term in the document.
     */
    public function addDocumentFrequency($documentId, $frequency) {
        $this->docFreq[$documentId] = $frequency;
    }

    /**
     * Copies an index and filters out docuemnts where the term frequency is below the threshold.
     * 
     * @param Index $index
     *      The index to copy.
     * 
     * @param int $threshold
     *      The threshold used to filter out docuemnt term freqencies.
     * 
     * @return Index|null $cpy
     *      A new index object the the document below threshold freqencies filtered out.
     *      Returns null if total frequency is lower than the threshold.
     */
    public static function thresholdCopy($index, $threshold) {
        $cpy = new Index($index->getTerm());

        foreach ($index->getDocFreq() as $documentId => $frequency) {
            if($frequency > $threshold) {
                $cpy->addDocumentFrequency($documentId, $frequency);
            }            
        }

        if($cpy->getTotalFrequency() < $threshold) {
            return null;
        }
        return $cpy;
    }
}

?>