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
        case "graph":
            switch($type) {
                case "language":
                    $language_id = get_language($language)[0]["id"];
                    switch($query) {
                        case "parent": echo json_encode(get_data(get_structure(get_parent($language)))); break;
                        case "structure": echo json_encode(get_data(get_structure($language))); break;
                        case "ancestors": echo json_encode(get_data(get_structure(get_ancestors($language)))); break;
                        case "children": echo json_encode(get_data(get_structure(get_children($language)))); break;
                        case "descendants": echo json_encode(get_data(get_structure(get_descendants($language)))); break;
                        case "family": echo json_encode(get_data(get_structure(get_family($language)))); break;
                        case "structure_all": echo json_encode(get_data(get_structure_all($language))); break;
                    }
                break;
                case 'segment':
                    $data = [
                        "source_segment" => $segment,
                        "target_segment" => $segment,
                    ];
                    $pairs = query_pairs($data);
                    echo json_encode(generate_segment_data($pairs));
                break;
            }
        break;
        case "list":
            switch($table) {
                case "pairs":
                    echo json_encode(query_pairs($data));
                    // $transition = $data["transition"];
                    // if ($transition) echo json_encode(get_pairs_by_transition($transition, $limit));
                    // else echo json_encode(get_pairs($limit));
                break;
                case "segments":    echo json_encode(get_segments($limit));    break;
                case "languages":   echo json_encode(get_languages($limit));   break;
                case "transitions": echo json_encode(get_transitions($limit)); break;
            }
        break;
        case "insert":
            switch($table) {
                case "pair":        echo add_pair($data);      break;
                case "segment":     echo add_segment($value);  break;
                case "language":    echo add_language($value); break;
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
function preg_replace_all($find, $repl, $text) {
    while (preg_match($find, $text)) {
        $text = preg_replace($find, $repl, $text);
    }
    return $text;
}
function history($output) {
    $timestamp = date('Y/m/d H:i:s');
    $output = preg_replace("/[\s]+/", " ", $output);
    file_put_contents("history.log", "$timestamp\t$output\n", FILE_APPEND);
}
function do_query($query) {
    GLOBAL $mysqli, $debug;
    if ($debug) echo "$query<br>";
    history($query);
    $mysqli->query($query) or die($mysqli->error);
    return $mysqli;
}
function get_query($query) {
    GLOBAL $mysqli, $debug;
    history($query);
    $result = $mysqli->query($query) or die($mysqli->error);
    while($row = $result->fetch_assoc()) $results[] = $row;
    return $results;
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
    $query = "TRUNCATE languages_segments";
    do_query($query);
    $query = "SET FOREIGN_KEY_CHECKS = 1";
    do_query($query);
}
function generate_segment_data($pairs) {
    $result = [];
    foreach($pairs as $pair) {
        $result[] = $pair["source_segment"];
        $result[] = $pair["target_segment"];
    }
    $result = array_unique($result);
    $result = array_values($result);
    $result = array_map(function($a) {
        return ["data" => ["id" => $a, "label" => $a]];
    }, $result);
    foreach($pairs as $pair) {
        if (strcmp($pair["source_segment"], $pair["target_segment"]) === 0) continue;
        $transition = explode(" → ", $pair["transition"]);
        $source_label = $transition[0];
        $target_label = $transition[1];
        $result[] = ["data" => [
            "source" => $pair["source_segment"],
            "target" => $pair["target_segment"],
            "transition" => $pair["transition"],
            "source_language" => $source_label,
            "target_language" => $target_label,
        ]]; 
    }
    return $result;
}
function get_parent($language) {
    $query = "SELECT
    source_language.value AS source
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE target_language.value = '".$language."'";
    $language = get_query($query);
    if (!$language) return -1;
    return $language[0]["source"];
}
function get_ancestors($language, $inclusive=false) {
    $ancestors = [];
    $parent = $language;
    if ($inclusive) $ancestors[] = $parent;
    while ($parent !== -1) {
        $parent = get_parent($parent);
        if ($parent !== -1) $ancestors[] = $parent;
    }
    return array_reverse($ancestors);
}
function get_ancestor_tree($language, $lookup=false) {
    $ancestors = [];
    $parent = $language;
    while ($parent !== -1) {
        $grandparent = get_parent($parent);
        if ($grandparent !== -1) {
            $transition = [$grandparent, $parent];
            if ($lookup) {
                $source = get_language($grandparent)[0]["id"];
                $target = get_language($parent)[0]["id"];
                $transition["transition"] = get_transition($source, $target)[0]["id"];
            }
            $ancestors[] = $transition;
        }
        $parent = $grandparent;
    }
    return array_reverse($ancestors);
}
function get_children($languages) {
    if (!is_array($languages)) $languages = [$languages];
    $languages = implode("','", $languages);
    $query = "SELECT
    target_language.value AS target
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE source_language.value IN ('".$languages."')";
    $languages = get_query($query);
    if (!$languages) return [];
    $languages = array_map(function($language) {
        return $language["target"];
    }, $languages);
    return $languages;
}
function get_descendants($language) {
    $count = [];
    $languages = [$language];
    while (empty($count[count($count)-2]) or $count[count($count)-2] !== $count[count($count)-1]) {
        $languages = implode("','", $languages);
        $query = "SELECT
    target_language.value AS target
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE source_language.value IN ('".$languages."')
    OR    target_language.value IN ('".$languages."')";
        $family = get_query($query);
        foreach($family as $branch) {
            $result[] = $branch["target"];
        }
        $languages = array_unique($result);
        $count[] = count($languages);
    }
    if (($key = array_search($language, $languages)) !== false) {
        unset($languages[$key]);
    }
    return $languages;
}
function get_family($language) {
    $count = [];
    $languages = [$language];
    while (empty($count[count($count)-2]) or $count[count($count)-2] !== $count[count($count)-1]) {
        $languages = implode("','", $languages);
        $query = "SELECT
    source_language.value AS source,
    target_language.value AS target
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE source_language.value IN ('".$languages."')
    OR    target_language.value IN ('".$languages."')";
        $family = get_query($query);
        foreach($family as $branch) {
            $result[] = $branch["source"];
            $result[] = $branch["target"];
        }
        $languages = array_unique($result);
        $count[] = count($languages);
    }
    return $languages;
}
function get_structure($languages) {
    if (!is_array($languages)) $languages = [$languages];
    $languages = implode("','", $languages);
    $query = "SELECT
    source_language.value AS source,
    target_language.value AS target
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE source_language.value IN ('".$languages."')
    OR    target_language.value IN ('".$languages."')";
    return get_query($query);
}
function get_structure_all($language) {
    $family = get_family($language);
    $languages = implode("','", $family);
    $query = "SELECT
    source_language.value AS source,
    target_language.value AS target
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE source_language.value IN ('".$languages."')
    OR    target_language.value IN ('".$languages."')";
    $family = get_query($query);
    return $family;
}
function get_data($pairs) {
    $result = [];
    foreach($pairs as $pair) {
        $result[] = $pair["source"];
        $result[] = $pair["target"];
    }
    $result = array_unique($result);
    $result = array_values($result);
    $result = array_map(function($a) {
        return ["data" => ["id" => $a]];
    }, $result);
    foreach($pairs as $pair) {
        $result[] = ["data" => $pair];
    }
    return $result;
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
function query_pairs($data) {
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $transition = $data["transition"];
    $query = "SELECT pairs.id AS id,
source_segment.value AS source_segment,
target_segment.value AS target_segment,
CONCAT(source_language.value, ' → ', target_language.value) AS transition,
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
AND transitions.target_language_id = target_language.id";
if ($source_segment and $target_segment) {
    $query .= " AND (source_segment.value = '$source_segment' OR target_segment.value = '$target_segment')";
}
else if ($source_segment) $query .= " AND source_segment.value = '$source_segment'";
else if ($target_segment) $query .= " AND target_segment.value = '$target_segment'";
if ($transition) $query .= " HAVING transition = '$transition'";
$query .= " ORDER BY transition";
    history($query);
    return get_query($query);
}
function get_pairs_by_transition_id($transition_id, $limit=0) {
    $query = "SELECT pairs.id AS id,
source_segment.value AS source_segment,
target_segment.value AS target_segment,
CONCAT(source_language.value, ' → ', target_language.value) AS transition,
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
ORDER BY transition
";
    if ($limit) $query .= " LIMIT $limit";
    return get_query($query);
}
function get_pairs_by_transition($transition, $limit=0) {
    $query = "SELECT pairs.id AS id,
source_segment.value AS source_segment,
target_segment.value AS target_segment,
CONCAT(source_language.value, ' → ', target_language.value) AS transition,
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
HAVING transition = '$transition'
ORDER BY transition
";
    if ($limit) $query .= " LIMIT $limit";
    return get_query($query);
}
function get_pairs($limit=0) {
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
ORDER BY transition";
    if ($limit) $query .= " LIMIT $limit";
    return get_query($query);
}
function add_inventory($family, $segments) {
    $family_id = get_or_add_language($family);
    $segments = array_unique($segments);
    foreach($segments as $segment) {
        $segment_id = get_or_add_segment($segment);
        $query = "INSERT INTO languages_segments (language_id, segment_id) VALUES ('$family_id', '$segment_id')";
        do_query($query);
    }
}
function get_inventory($language_id) {
    $query = "SELECT * FROM languages_segments
    INNER JOIN segments
    ON languages_segments.segment_id = segments.id
    WHERE language_id = '$language_id'";
    return array_map(function($segment) {
        return $segment["value"];
    }, get_query($query));
}
function get_segments($limit=0) {
    $query = "SELECT id, value FROM segments ORDER BY value";
    if ($limit) $query .= " LIMIT $limit";
    foreach(get_query($query) as $row) {
        $results[] = ["id" => $row["id"], "value" => $row["value"]];
    }
    // usort($results, 'strnatcasecmp');
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
    $value = trim($value);
    if (!$value) return;
    $query = "INSERT INTO segments (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_languages($limit=0) {
    $query = "SELECT id, value FROM languages";
    if ($limit) $query .= " LIMIT $limit";
    foreach(get_query($query) as $row) {
        $results[] = ["id" => $row["id"], "value" => $row["value"]];
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
    $value = trim($value);
    if (!$value) return;
    $query = "INSERT INTO languages (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_transitions($limit=0) {
    $query = "SELECT
transitions.id AS id,
CONCAT(source_language.value, ' → ', target_language.value) as transition,
citation
FROM transitions
INNER JOIN languages AS source_language
INNER JOIN languages AS target_language
ON  transitions.source_language_id = source_language.id
AND transitions.target_language_id = target_language.id
";
    if ($limit) $query .= "LIMIT $limit";
    $result = get_query($query);
    foreach($result as $row) {
        $results[] = ["id" => $row["id"], "transition" => $row["transition"], "citation" => $row["citation"]];
    }
    usort($results, function($a, $b) {
        return $a['transition'] <=> $b['transition'];
    });
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
function update_transition($data) {
    $id = $data["id"];

    $source_language = $data["source_language"];
    $target_language = $data["target_language"];
    $citation = $data["citation"];

    $source_language_id = get_or_add_language($source_language);
    $target_language_id = get_or_add_language($target_language);

    $query = "UPDATE transitions SET source_language_id = '$source_language_id' , target_language_id = '$target_language_id' , citation = '$citation' WHERE id='$id'";
    do_query($query);
}
function add_transition($source_language_id, $target_language_id, $citation='') {
    $query = "INSERT INTO transitions (source_language_id, target_language_id, citation) VALUES ('$source_language_id', '$target_language_id', '$citation')";
    $result = do_query($query)->insert_id;
    return $result;
}
function get_or_add_segment($segment) {
    return check_segment($segment) ? get_segment($segment)[0]["id"] : add_segment($segment);
}
function get_or_add_language($language) {
    return check_language($language) ? get_language($language)[0]["id"] : add_language($language);
}
function get_or_add_transition($source, $target, $citation='') {
    if (!$source or !$target) return;
    $checkTransition = check_transition($source, $target);
    if ($checkTransition) {
        return get_transition($source, $target)[0]["id"];
    }
    else {
        return add_transition($source, $target, $citation);
    }
}
function update_pair($data) {
    $id = $data["id"];
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $source_language = $data["source_language"];
    $target_language = $data["target_language"];
    $environment = $data["environment"];
    $notes = $data["notes"];

    $source_segment_id = get_or_add_segment($source_segment);
    $target_segment_id = get_or_add_segment($target_segment);
    $source_language_id = get_or_add_language($source_language);
    $target_language_id = get_or_add_language($target_language);

    $transition_id = get_or_add_transition($source_language_id, $target_language_id);

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
function add_pair($data) {
    $source_segment = $data["source_segment"];
    $target_segment = $data["target_segment"];
    $source_language = $data["source_language"];
    $target_language = $data["target_language"];
    $environment = $data["environment"];
    $notes = $data["notes"];

    $source_segment_id = get_or_add_segment($source_segment);
    $target_segment_id = get_or_add_segment($target_segment);

    $transition_id = $data["transition"];
    if (!$transition_id) {
        $source_language_id = get_or_add_language($source_language);
        $target_language_id = get_or_add_language($target_language);
        $transition_id = get_or_add_transition($source_language_id, $target_language_id);
    }

    $query = "SELECT * from pairs WHERE source_segment_id = '$source_segment_id' AND target_segment_id = '$target_segment_id' AND transition_id = '$transition_id'";
    $pair = get_query($query);
    if (!empty($pair) and $environment) {
        $id = $pair[0]["id"];
        $query = "UPDATE pairs SET environment = concat(environment, ' $environment') WHERE id='$id'";
        do_query($query);
    }
    else {
        $query = "INSERT INTO pairs (source_segment_id, target_segment_id, transition_id, environment, notes) VALUES ('$source_segment_id', '$target_segment_id', '$transition_id', '$environment', '$notes');";
        return do_query($query)->insert_id;
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
        else {print_r($i); echo "\t";}
    }
    echo "\n<br>";
}
?>