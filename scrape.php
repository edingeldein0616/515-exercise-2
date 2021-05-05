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
            require 'AutomaticIndexer.php';

            $url = trim($_POST["url"]);

            $indexer = new AutomaticIndexer("stopwords.txt", 2);

            $indexer->index($url);

            $invertedIndex = $indexer->getIndex();

            foreach ($invertedIndex as $id => $value) {
                echo "<tr><td>$id</td>";
                echo "<td>" . $value->getTerm() . "</td>";
                echo "<td>" . $value->getTotalFrequency() . "</td></tr>";
            }

            ?>
        <tbody>
    </table>
</body>

</html>