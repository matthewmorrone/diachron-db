<?
error_reporting(E_ERROR);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_GET) {
    if ($_GET["debug"]) {
        printr($_GET);
    }
    extract($_GET);

    include 'credentials.txt';

    $mysqli = new mysqli($hostname, $username, $password, $database);

    switch($mode) {
        case "connect":
            printr($mysqli, "$username@$hostname");
        break;
        case "tables":
            $query = "SHOW TABLES FROM $database";
            echo json_encode(get_tables());
        break;
        case "list":
            switch($table) {
                case "pairs":       echo json_encode(get_pairs());       break;
                case "segments":    echo json_encode(get_segments());    break;
                case "languages":   echo json_encode(get_languages());   break;
                case "transitions": echo json_encode(get_transitions()); break;
            }
        break;
        case "insert":
            switch($table) {
                case "pair":        echo add_pair($data);                  break;
                case "segment":     echo add_segment($value);              break;
                case "language":    echo add_language($value);             break;
                case "transition": 
                    $source_language_id = get_or_add_language($data["source_language"]);
                    $target_language_id = get_or_add_language($data["target_language"]);
                    echo get_or_add_transition($source_language_id, $target_language_id); 
                break;
            }
        break;
        case "update":
            switch($table) {
                case "pair":        echo update_pair($data);   break;
                case "segment":     echo update_segment($id, $value);  break;
                case "language":    echo update_language($id, $value); break;
                // case "transition": echo update_transition($id, $data); break;
            }
        break;
        case "remove":
            switch($table) {
                case "pair":       echo remove_pair($id);       break;
                case "segment":    echo remove_segment($id);    break;
                case "language":   echo remove_language($id);   break;
                case "transition": echo remove_transition($id); break;
            }
        break;
        case "test":
            $source_segment = "q";
            $target_segment = "k";
            $source_language = "Old Turkic";
            $target_language = "Turkish";

            echo "<pre>";

            printr("count_tables: ", count_tables());
            printr("get_tables: ", json_encode(get_tables()));
            printr("get_segments: ", count(get_segments()));
            printr("get_languages: ", count(get_languages()));
            printr("get_transitions: ", count(get_transitions()));

            printr("check_segment $source_segment: ", check_segment($source_segment));
            printr("get_segment $source_segment: ", get_segment($source_segment)[0]["id"]);

            printr("check_segment $target_segment: ", check_segment($target_segment));
            printr("get_segment $target_segment: ", get_segment($target_segment)[0]["id"]);

            printr("check_language $source_language: ", check_language($source_language));
            $source_language_id = get_language($source_language)[0]["id"];
            printr("get_language $source_language: ", $source_language_id);

            printr("check_language $target_language: ", check_language($target_language));
            $target_language_id = get_language($target_language)[0]["id"];
            printr("get_language $target_language: ", $target_language_id);

            printr("check_transition $source_language → $target_language: ", check_transition($source_language_id, $target_language_id));
            $transition = get_transition($source_language_id, $target_language_id)[0];
            printr("get_transition $source_language → $target_language: ", $transition["id"]);

            echo "</pre>";
        break;
        case "dump":
            $export = [];
            $export[] = [
                "type" => "database", 
                "name" => $database
            ];
            $tables = get_tables();
            foreach($tables as $table) {
                $data = get_query("select * from $table");
                $export[] = [
                    "type" => "table", 
                    "database" => $database, 
                    "table" => $table, 
                    "data" => $data
                ];
            }
            echo json_encode($export);
        break;
        default: break;
    }
}
function do_query($query) {
    GLOBAL $mysqli;
    $mysqli->query($query) or die($mysqli->error);
    return $mysqli;
}
function get_query($query) {
    GLOBAL $mysqli;
    $result = $mysqli->query($query) or die($mysqli->error);
    while($row = $result->fetch_assoc()) $results[] = $row;
    return $results;
}
function count_tables() {
    GLOBAL $database;
    $query = "SHOW TABLES FROM $database;";
    return count(get_query($query));
}
function get_tables() {
    GLOBAL $database;
    $query = "SHOW TABLES FROM $database;";
    foreach(get_query($query) as $row) {
        $results[] = $row["Tables_in_$database"];
    }
    return $results;
}
function get_pairs() {
    $query = "SELECT pairs.id AS id, 
    source_segment.value AS source_segment, 
    target_segment.value AS target_segment,
    source_language.value AS source_language, 
    target_language.value AS target_language
    FROM pairs
    INNER JOIN segments AS source_segment
    INNER JOIN segments AS target_segment
    INNER JOIN transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON pairs.source_segment_id = source_segment.id
    AND pairs.target_segment_id = target_segment.id
    AND pairs.transition_id = transitions.id
    AND transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    ORDER BY id DESC
    ;";
    return get_query($query);
}
function get_segments() {
    $query = "SELECT id, value FROM segments";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = $row["value"];
    }
    return $results;
}
function check_segment($value) {
    $query = "SELECT id, value FROM segments WHERE value = '$value'";
    $results = get_query($query);
    return !empty($results);
}
function get_segment($value) {
    $query = "SELECT id, value FROM segments WHERE value = '$value'";
    return get_query($query);
}
function add_segment($value) {
    $query = "INSERT INTO segments (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_languages() {
    $query = "SELECT id, value FROM languages";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = $row["value"];
    }
    return $results;
}
function check_language($value) {
    $query = "SELECT id, value FROM languages WHERE value = '$value'";
    $results = get_query($query);
    return !empty($results);
}
function get_language($value) {
    $query = "SELECT id, value FROM languages WHERE value = '$value'";
    return get_query($query);
}
function add_language($value) {
    $query = "INSERT INTO languages (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_transitions() {
    $query = "SELECT 
    transitions.id AS id, 
    source_language_id AS source_language_id,
    target_language_id AS target_language_id,
    source_language.value AS source_language_name, 
    target_language.value AS target_language_name,
    citation,
    notes
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    ";
    return get_query($query);
}
function check_transition($source, $target) {
    $query = "SELECT * FROM transitions
    WHERE source_language_id = '$source'
    AND   target_language_id = '$target'
    ";
    return !empty(get_query($query));
}
function get_transition($source, $target) {
    $query = "SELECT * FROM transitions
    WHERE source_language_id = '$source'
    AND   target_language_id = '$target'
    ";
    foreach(get_query($query) as $row) {
        $results[] = $row;
    }
    return $results;
}
function add_transition($source_language_id, $target_language_id) {
    $query = "INSERT INTO transitions (source_language_id, target_language_id) VALUES ('$source_language_id', '$target_language_id')";
    return do_query($query)->insert_id;
}
function get_or_add_segment($segment) {
    return check_segment($segment) ? get_segment($segment)[0]["id"] : add_segment($segment);
}
function get_or_add_language($language) {
    return check_language($language) ? get_language($language)[0]["id"] : add_language($language);
}
function get_or_add_transition($source, $target) {
    return check_transition($source, $target) ? get_transition($source, $target)[0]["id"] : add_transition($source, $target);
}
function update_pair($data, $debug=false) {
    $id = $data["id"];
    if ($debug) {
        echo "id: $id\n";
    }
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $source_language = $data["source_language"];
    $target_language = $data["target_language"];

    $source_segment_id = get_or_add_segment($source_segment);
    $target_segment_id = get_or_add_segment($target_segment);
    if ($debug) {
        echo "source_segment_id: $source_segment ($source_segment_id)\n";
        echo "target_segment_id: $target_segment ($target_segment_id)\n";
    }
    $source_language_id = get_or_add_language($source_language);
    $target_language_id = get_or_add_language($target_language);
    if ($debug) {
        echo "source_language_id: $source_language ($source_language_id)\n";
        echo "target_language_id: $target_language ($target_language_id)\n";
    }

    $transition_id = get_or_add_transition($source_language_id, $target_language_id);
    if ($debug) {
        echo "transition_id: $transition_id\n";
    }

    $query = "UPDATE pairs 
    SET source_segment_id = '$source_segment_id' 
    , target_segment_id = '$target_segment_id' 
    , transition_id = '$transition_id' 
    WHERE id='$id'";
    
    do_query($query);
}
function add_pair($data, $debug=false) {
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $source_language = $data["source_language"];
    $target_language = $data["target_language"];

    $source_segment_id = get_or_add_segment($source_segment);
    $target_segment_id = get_or_add_segment($target_segment);
    if ($debug) {
        echo "source_segment_id: $source_segment ($source_segment_id)\n";
        echo "target_segment_id: $target_segment ($target_segment_id)\n";
    }
    $source_language_id = get_or_add_language($source_language);
    $target_language_id = get_or_add_language($target_language);
    if ($debug) {
        echo "source_language_id: $source_language ($source_language_id)\n";
        echo "target_language_id: $target_language ($target_language_id)\n";
    }

    $transition_id = get_or_add_transition($source_language_id, $target_language_id);
    if ($debug) {
        echo "transition_id: $transition_id\n";
    }

    $query = "SELECT * from pairs
    WHERE source_segment_id = '$source_segment_id'
    AND target_segment_id = '$target_segment_id'
    AND transition_id = '$transition_id'
    ";
    $pair = get_query($query);
    if (!empty($pair)) {
        printr($pair);
    }
    else {
        $query = "INSERT INTO pairs (source_segment_id, target_segment_id, transition_id) 
        VALUES ('$source_segment_id', '$target_segment_id', '$transition_id');";
        echo do_query($query)->insert_id;
    }
}
function update_segment($id, $value) {
    $query = "UPDATE segments SET value = '$value' WHERE id='$id'";
    do_query($query);
}
function update_language($id, $value) {
    $query = "UPDATE languages SET value = '$value' WHERE id='$id'";
    do_query($query);
}
function remove_pair($id) {
    $query = "DELETE FROM pairs WHERE id='$id'";
    do_query($query);
}
function remove_segment($id) {
    $query = "DELETE FROM segments WHERE id='$id'";
    do_query($query);
}
function remove_language($id) {
    $query = "DELETE FROM languages WHERE id='$id'";
    do_query($query);
}
function remove_transition($id) {
    $query = "DELETE FROM transitions WHERE id='$id'";
    do_query($query);
}
function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {echo "<pre>"; print_r($i); echo "</pre>\n"; }
        else {print_r($i); echo "\n";}
    }
}
?>