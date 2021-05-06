<html>

<head>
    <title>Automatic Indexer</title>

    <style>
        table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #dddddd;
        }
    </style>
</head>

<body>
    <table>

        <thead>
            <tr>
                <th>id</th>
                <th>term</th>
                <th>reference count</th>
            </tr>
        </thead>
        <tbody>

            <?php
            ini_set('display_errors', 1);

            require 'AutomaticIndexer.php';
            require 'IndexDatabase.php';

            $url = trim($_POST["url"]);
            $username = trim($_POST["username"]);
            $password = trim($_POST["password"]);

            $indexer = new AutomaticIndexer("stopwords.txt", 2);
            $indexer->index($url);
            $pageData = $indexer->getPageData();
            $invertedIndex = $indexer->getIndex();

            $db = new IndexDatabase("localhost", $username, $password, "erich_dingeldein");

            foreach($pageData as $docId => $data) {
                $url = $data['url'];
                $title = $data['title'];
                $description = $data['description'];
                $db->insert("documents (docId, url, title, description)", "('$docId','$url','$title','$description')");
            }

            foreach($invertedIndex as $termId => $index) {
                $db->insert("terms (termId, term)", "('$termId','" . $index->getTerm() ."')");
                $docFreq = $index->getDocFreq();
                foreach($docFreq as $docId => $freq) {
                    $db->insert("documents (docId)", "('" . $docId . "')");
                    $db->insert("inverted_index (termId, docId, freq)", "('$termId','$docId',$freq)");
                }
            }

            foreach ($invertedIndex as $id => $value) {
                echo "<tr><td><a href=\"lookup.php?id=$id\">$id</a></td>";
                echo "<td>" . $value->getTerm() . "</td>";
                echo "<td>" . $value->getTotalFrequency() . "</td></tr>";
            }

            ?>
        <tbody>
    </table>
</body>

</html>