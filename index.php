<html>

<head>
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

            $indexer = new AutomaticIndexer("stopwords.txt");

            $invertedIndex = $indexer->index("https://www.w3schools.com");

            foreach ($invertedIndex->getIndex() as $id => $value) {
                echo "<tr><td>$id</td>";
                echo "<td>" . $value->getTerm() . "</td>";
                echo "<td>" . $value->getReferenceCount() . "</td></tr>";
            }

            ?>
        <tbody>
    </table>
</body>

</html>