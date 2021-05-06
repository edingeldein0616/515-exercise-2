<?php
ini_set('display_errors', 1);

include "IndexDatabase.php";

$termId = trim($_GET["id"]);

$db = new IndexDatabase("localhost", "root", "root", "erich_dingeldein");

$result1 = $db->select("term", "terms", "termId='$termId'");
$term = $result1->fetch_assoc()['term'];

echo "<h1>$term</h1>";

$result2 = $db->select("*", "inverted_index", "termId='$termId'");

while($row = $result2->fetch_assoc()) {
    $docId = $row['docId'];
    $result3 = $db->select("*", "documents", "docId='$docId'");
    if($docData = $result3->fetch_assoc()) {
        $url = $docData['url'];
        $title = $docData['title'];
        $description = $docData['description'];
        echo "<p>$title, $url, $description</p>";
    }
    
}


?>