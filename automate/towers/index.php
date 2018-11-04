<?php
    chdir($_SERVER["DOCUMENT_ROOT"]);
    require 'lib/global.php';


    /* initializers */
    if (!initialize()) {
        http_response_code(500);
        return;
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = file_get_contents('php://input');
        file_put_contents('/tmp/tests', $data);

        $j_data = json_decode($data, true);

        $db = $GLOBALS['db'];
        $sql = 'INSERT INTO location' .
               '            (uuid, lat, long, alt, bearing, speed, '.
               '             fix_accuracy, fix_provider, fix_timestamp, tower, dbm) '.
               '     VALUES (:uuid, :lat, :long, :alt, :bearing, :speed, '.
               '             :fix_accuracy, :fix_provider, :fix_timestamp, :tower, :dbm) ';
        try {
            $db->beginTransaction();
            $cnt = 0;
            foreach ($j_data['list_towers'] as $tow) {
                $dbm = $j_data['towers_db'][$cnt];
                $cnt = $cnt + 1;

                $sth = $db->prepare($sql);
                $sth->execute(array(
                    ':uuid'=>guidv4(),
                    ':lat'=>$j_data['lat'],
                    ':long'=>$j_data['long'],
                    ':alt'=>$j_data['alt'],
                    ':bearing'=>$j_data['bearing'],
                    ':speed'=>$j_data['speed'],
                    ':fix_accuracy'=>$j_data['fix_accuracy'],
                    ':fix_provider'=>$j_data['fix_provider'],
                    ':fix_timestamp'=>$j_data['fix_timestamp'],
                    ':tower'=>$tow,
                    ':dbm'=>$dbm));
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db->debug === True) {
                var_dump($e);
            }
            $db->handle->rollback(); 
            return False;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = 'SELECT uuid, lat, long, alt, bearing, speed, fix_accuracy, fix_provider, fix_timestamp, tower, dbm'.
               '  FROM location';
        $rs = $GLOBALS['db']->query($sql);

        echo '<html>';
        echo '<head>';
        echo '<style>';
        echo ' table {';
        echo '  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;';
        echo '  border-collapse: collapse;';
        echo '      width: 100%;';
        echo ' }';

        echo '  #towerlocations td, #towerlocations th {';
        echo '      border: 1px solid #ddd;';
        echo '      padding: 8px;';
        echo '  }';
        echo '  #towerlocations th {';
        echo '      padding-top: 12px;';
        echo '      padding-bottom: 12px;';
        echo '      text-align: left;';
        echo '      background-color: #4CAF50;';
        echo '      color: white;';
        echo '  }';
        echo '  #towerlocations tr:nth-child(even){background-color: #f2f2f2;}';
        echo '  #towerlocations tr:hover {background-color: #ddd;}';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        echo '<table id="towerlocations">';
        echo '<tr>';
        echo '<th>Lat</th>';
        echo '<th>Long</th>';
        echo '<th>Alt</th>';
        echo '<th>Bearing</th>';
        echo '<th>Speed</th>';
        echo '<th>Accuracy</th>';
        echo '<th>Provider</th>';
        echo '<th>Timestamp</th>';
        echo '<th>Tower</th>';
        echo '<th>dBm</th>';
        echo '</tr>';
        foreach($rs as $row) {
            echo '<tr>';
            echo '<td>' . $row['lat'] . '</td>';
            echo '<td>' . $row['long'] . '</td>';
            echo '<td>' . $row['alt'] . '</td>';
            echo '<td>' . $row['bearing'] . '</td>';
            echo '<td>' . $row['speed'] . '</td>';
            echo '<td>' . $row['fix_accuracy'] . '</td>';
            echo '<td>' . $row['fix_provider'] . '</td>';
            echo '<td>' . $row['fix_timestamp'] . '</td>';
            echo '<td>' . $row['tower'] . '</td>';
            echo '<td>' . $row['dbm'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
?>
