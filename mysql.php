<?php
error_reporting(E_ERROR);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include 'credentials.txt';

$mysqli = new mysqli($hostname, $username, $password, $database);

if ($_GET) {
    if ($_GET["debug"]) {
        printr($_GET);
    }
    extract($_GET);

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
                case "pairs":
                    if ($transition) echo json_encode(get_pairs_by_transition($transition));
                    else echo json_encode("");
                break;
                case "segments":    echo json_encode(get_segments());    break;
                case "languages":   echo json_encode(get_languages());   break;
                case "transitions": echo json_encode(get_transitions()); break;
            }
        break;
        case "insert":
            switch($table) {
                case "pair":        echo add_pair($data, $debug); break;
                case "segment":     echo add_segment($value);     break;
                case "language":    echo add_language($value);    break;
                case "transition":
                    $source_language_id = get_or_add_language($data["source_language"]);
                    $target_language_id = get_or_add_language($data["target_language"]);
                    $citation = $data["citation"];
                    echo get_or_add_transition($source_language_id, $target_language_id, $citation);
                break;
            }
        break;
        case "update":
            switch($table) {
                case "pair":        echo update_pair($data);           break;
                case "segment":     echo update_segment($id, $value);  break;
                case "language":    echo update_language($id, $value); break;
                case "transition":  echo update_transition($data);     break;
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
        case "reset":
            reset_database();
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
function reset_database() {
    $query = "SET FOREIGN_KEY_CHECKS = 0";
    do_query($query);
    $query = "TRUNCATE pairs";
    do_query($query);
    $query = "TRUNCATE segments";
    do_query($query);
    $query = "TRUNCATE transitions";
    do_query($query);
    $query = "TRUNCATE languages";
    do_query($query);
    $query = "SET FOREIGN_KEY_CHECKS = 1";
    do_query($query);
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
function clean_tables() {
    $query = 'DELETE FROM pairs WHERE pairs.id IN (SELECT pairs.id AS id
FROM pairs
INNER JOIN segments AS source_segment
INNER JOIN segments AS target_segment
ON pairs.source_segment_id = source_segment.id
AND pairs.target_segment_id = target_segment.id
WHERE source_segment.value = ""
OR target_segment.value = "")';
    do_query($query);

    $query = 'DELETE FROM segments
WHERE segments.id NOT IN (
SELECT source_segment_id FROM pairs
UNION
SELECT target_segment_id FROM pairs
)';
    do_query($query);
    // probably need to do something similar for languages and transitions
}

function get_pairs_by_transition($transition_id) {
    $query = "SELECT pairs.id AS id,
source_segment.value AS source_segment,
target_segment.value AS target_segment,
CONCAT(source_language.value, ' → ', target_language.value) as transition,
pairs.environment,
pairs.notes
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
WHERE transitions.id = '$transition_id'
ORDER BY id DESC;";
    return get_query($query);
}
function get_pairs() {
    $query = "SELECT pairs.id AS id,
source_segment.value AS source_segment,
target_segment.value AS target_segment,
CONCAT(source_language.value, ' → ', target_language.value) as transition,
pairs.environment,
pairs.notes
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
ORDER BY id DESC;";
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
CONCAT(source_language.value, ' → ', target_language.value) as transition,
citation
FROM transitions
INNER JOIN languages AS source_language
INNER JOIN languages AS target_language
ON  transitions.source_language_id = source_language.id
AND transitions.target_language_id = target_language.id";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = [$row["transition"], $row["citation"]];
    }
    return $results;
}
function check_transition($source, $target) {
    $query = "SELECT * FROM transitions WHERE source_language_id = '$source' AND   target_language_id = '$target'";
    return !empty(get_query($query));
}
function get_transition($source, $target) {
    $query = "SELECT * FROM transitions WHERE source_language_id = '$source' AND   target_language_id = '$target'";
    foreach(get_query($query) as $row) {
        $results[] = $row;
    }
    return $results;
}
function update_transition($data, $debug=false) {
    $id = $data["id"];

    $source_language = $data["source_language"];
    $target_language = $data["target_language"];
    $citation = $data["citation"];

    $source_language_id = get_or_add_language($source_language);
    $target_language_id = get_or_add_language($target_language);

    $query = "UPDATE transitions SET source_language_id = '$source_language_id' , target_language_id = '$target_language_id' , citation = '$citation' WHERE id='$id'";
    if ($debug) echo $query."\n";
    do_query($query);
    if ($debug) echo "update_transition $id $source_language_id $target_language_id $citation\n";
}
function add_transition($source_language_id, $target_language_id, $citation='', $debug=false) {
    $query = "INSERT INTO transitions (source_language_id, target_language_id, citation) VALUES ('$source_language_id', '$target_language_id', '$citation')";
    $result = do_query($query)->insert_id;
    if ($debug) {
        echo "add_transition $source_language_id $target_language_id $citation $result\n";
    }
    return $result;
}
function get_or_add_segment($segment) {
    return check_segment($segment) ? get_segment($segment)[0]["id"] : add_segment($segment);
}
function get_or_add_language($language) {
    return check_language($language) ? get_language($language)[0]["id"] : add_language($language);
}
function get_or_add_transition($source, $target, $citation='') {
    $checkTransition = check_transition($source, $target);
    if ($checkTransition) {
        return get_transition($source, $target)[0]["id"];
    }
    else {
        return add_transition($source, $target, $citation);
    }
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
    $environment = $data["environment"];
    $notes = $data["notes"];

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
    , environment = '$environment'
    , notes = '$notes'
    WHERE id='$id'";

    do_query($query);
    echo "update_pair $id $source_segment_id $target_segment_id $transition_id";
}
function add_pair($data, $debug=false) {
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $source_language = $data["source_language"];
    $target_language = $data["target_language"];
    $environment = $data["environment"];
    $notes = $data["notes"];

    $source_segment_id = get_or_add_segment($source_segment);
    $target_segment_id = get_or_add_segment($target_segment);
    if ($debug) {
        echo "source_segment_id: $source_segment ($source_segment_id)\n";
        echo "target_segment_id: $target_segment ($target_segment_id)\n";
    }

    $transition_id = $data["transition"];
    if (!$transition_id) {
        $source_language_id = get_or_add_language($source_language);
        $target_language_id = get_or_add_language($target_language);
        if ($debug) {
            echo "source_language_id: $source_language ($source_language_id)\n";
            echo "target_language_id: $target_language ($target_language_id)\n";
        }
        $transition_id = get_or_add_transition($source_language_id, $target_language_id);
    }
    if ($debug) echo "transition_id: $transition_id\n";

    $query = "SELECT * from pairs WHERE source_segment_id = '$source_segment_id' AND target_segment_id = '$target_segment_id' AND transition_id = '$transition_id'";
    if ($debug) echo $query."\n";
    $pair = get_query($query);
    if (!empty($pair) and $debug) {
        echo "pair already exists: ".json_encode($pair)."\n";
    }
    else {
        $query = "INSERT INTO pairs (source_segment_id, target_segment_id, transition_id, environment, notes) VALUES ('$source_segment_id', '$target_segment_id', '$transition_id', '$environment', '$notes');";
        if ($debug) echo $query."\n";
        $result = do_query($query)->insert_id;
        if ($debug) echo "add_pair : $source_segment $target_segment $source_language $target_language $environment $notes : $result\n";
    }
}
function update_segment($id, $value) {
    $query = "UPDATE segments SET value = '$value' WHERE id='$id'";
    do_query($query);
    echo "update_segment $id $value";
}
function update_language($id, $value) {
    $query = "UPDATE languages SET value = '$value' WHERE id='$id'";
    do_query($query);
    echo "update_language $id";
}
function remove_pair($id) {
    $query = "DELETE FROM pairs WHERE id='$id'";
    echo $query;
    do_query($query);
    echo "remove_pair $id";
}
function remove_segment($id) {
    $query = "DELETE FROM segments WHERE id='$id'";
    do_query($query);
    echo "remove_segment $id";
}
function remove_language($id) {
    $query = "DELETE FROM languages WHERE id='$id'";
    do_query($query);
    echo "remove_language $id";
}
function remove_transition($id) {
    $query = "DELETE FROM transitions WHERE id='$id'";
    do_query($query);
    echo "remove_transition $id";
}
function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {echo "<pre>";  print_r($i); echo "</pre>";}
        else {print_r($i);}
        echo "\n<br>";
    }
}
?>