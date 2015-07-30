<?php
use CLIFramework\Component\Table\Table;

function dumpQuery($dbh, $query)
{
    $stm = $dbh->query($query);
    if ($stm == false) {
        var_dump($dbh->errorInfo());
        return;
    }

    echo "Result for: $query\n";
    $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
    $table = new Table;
    $table->setHeaders(array_keys($rows[0]));
    foreach($rows as $row) {
        $values = array_map(function($val) { return var_export($val, true); }, array_values($row));
        $table->addRow($values);
    }

    echo $table->render();
}
