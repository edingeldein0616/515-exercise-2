<?php
include "IndexDatabase.php";
include "AutomaticIndexer.php";

$seedUrl = trim($_POST['seedUrl']);
$maxPages = trim($_POST['maxPages']);
$maxTime = trim($_POST['maxTime']);

$indexer = new AutomaticIndexer("stopwords.txt", $maxPages, $maxTime);

echo "<h1>$seedUrl</h1>";
$indexer->crawl($seedUrl);

?>