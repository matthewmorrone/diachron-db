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
                    $language_id = get_language(["value" => $language]);
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
                    echo json_encode(generate_segment_data($data));
                break;
                case "transition":
                    echo json_encode(generate_transition_data($data));
                break;
            }
        break;
        case "list":
            switch($table) {
                case "pairs":       echo json_encode(query_pairs($data));      break;
                case "segments":    echo json_encode(get_segments($data));    break;
                case "languages":   echo json_encode(get_languages($data));   break;
                case "transitions": echo json_encode(get_transitions($data)); break;
            }
        break;
        case "insert":
            switch($table) {
                case "pair":        echo add_pair($data);              break;
                case "segment":     echo add_segment($data);           break;
                case "language":    echo add_language($data);          break;
                case "transition":  echo get_or_add_transition($data); break;
            }
        break;
        case "update":
            switch($table) {
                case "pair":        echo update_pair($data);       break;
                case "segment":     echo update_segment($data);    break;
                case "language":    echo update_language($data);   break;
                case "transition":  echo update_transition($data); break;
            }
        break;
        case "remove":
            switch($table) {
                case "pair":       echo remove_pair($data);       break;
                case "segment":    echo remove_segment($data);    break;
                case "language":   echo remove_language($data);   break;
                case "transition": echo remove_transition($data); break;
            }
        break;
        case "test":
            $source_segment = "e";
            $target_segment = "i";
            $source_language = "Proto-Indo-European";
            $target_language = "Latin";

            printr("count_tables: ", count_tables());
            printr("get_tables: ", json_encode(get_tables()));
            printr("get_segments: ", count(get_segments()));
            printr("get_languages: ", count(get_languages()));
            printr("get_transitions: ", count(get_transitions()));

            printr("check_segment $source_segment: ", check_segment(["value" => $source_segment]));
            printr("get_segment $source_segment: ", get_segment(["value" => $source_segment])[0]["id"]);

            printr("check_segment $target_segment: ", check_segment(["value" => $target_segment]));
            printr("get_segment $target_segment: ", get_segment(["value" => $target_segment])[0]["id"]);

            printr("check_language $source_language: ", check_language(["value" => $source_language]));
            $source_language_id = get_language(["value" => $source_language])[0]["id"];
            printr("get_language $source_language: ", $source_language_id);

            printr("check_language $target_language: ", check_language(["value" => $target_language]));
            $target_language_id = get_language(["value" => $target_language])[0]["id"];
            printr("get_language $target_language: ", $target_language_id);

            printr("check_transition $source_language → $target_language: ", check_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id]));
            $transition = get_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id])[0];
            printr("get_transition $source_language → $target_language: ", $transition["id"]);

        break;
        case "reset":
            reset_database();
        break;
        case "dump":
            switch($format) {
                case "json": echo exportJson(); break;
                case "sql":  echo exportSql(); break;
                case "csv":  echo exportCsv(); break;
            }
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
function log_reads($output) {
    $timestamp = date('Y/m/d H:i:s');
    $output = preg_replace("/[\s]+/", " ", $output);
    file_put_contents("log/history-reads.log", "$timestamp\t$output\n", FILE_APPEND);
}
function log_writes($output) {
    $timestamp = date('Y/m/d H:i:s');
    $output = preg_replace("/[\s]+/", " ", $output);
    file_put_contents("log/history-writes.log", "$timestamp\t$output\n", FILE_APPEND);
}
function exportJson() {
    $export = [];
    $export[] = [
        "type" => "database",
        "name" => 'diachron'
    ];
    $tables = get_tables();
    foreach($tables as $table) {
        $data = get_query("select * from $table");
        $export[] = [
            "type" => "table",
            "database" => 'diachron',
            "table" => $table,
            "data" => $data
        ];
    }
    header('Content-type: Application/JSON');
    return json_encode($export, JSON_PRETTY_PRINT);
}
function exportSql($tables=false, $backup_name=false) {
    GLOBAL $mysqli;
    $mysqli->query("SET NAMES 'utf8'");

    $queryTables = $mysqli->query('SHOW TABLES');
    while($row = $queryTables->fetch_row()) $target_tables[] = $row[0];

    if ($tables !== false) $target_tables = array_intersect($target_tables, $tables);
    foreach ($target_tables as $table) {
        $result        = $mysqli->query('SELECT * FROM '.$table);
        $fields_amount = $result->field_count;
        $rows_num      = $mysqli->affected_rows;
        $res           = $mysqli->query('SHOW CREATE TABLE '.$table);
        $TableMLine    = $res->fetch_row();
        $content       = (!isset($content) ? '' : $content) . "\n\n".$TableMLine[1].";\n\n";

        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter=0) {
            while ($row = $result->fetch_row()) {
                if ($st_counter % 100 == 0 || $st_counter == 0) {
                    $content .= "\nINSERT INTO ".$table." VALUES";
                }
                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) $content .= '"'.$row[$j].'"';
                    else $content .= '""';
                    if ($j < ($fields_amount - 1)) $content.= ',';
                }
                $content .= ")";
                if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) $content .= ";";
                else $content .= ",";
                $st_counter = $st_counter + 1;
            }
        }
        $content .= "\n\n";
    }
    $backup_name = $backup_name ? $backup_name : "diachron.sql";
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"".$backup_name."\"");
    return $content;
}
function exportCsv($tables=false, $backup_name=false) {
    GLOBAL $mysqli;
    $mysqli->query("SET NAMES 'utf8'");

    $queryTables = $mysqli->query('SHOW TABLES');
    while($row = $queryTables->fetch_row()) $target_tables[] = $row[0];

    if ($tables !== false) $target_tables = array_intersect($target_tables, $tables);
    $content = "";

    foreach ($target_tables as $table) {
        $result = $mysqli->query('SELECT * FROM '.$table);

        $content .= "$table\n";
        for ($i = 0; $i < $result->field_count; $i++) {
            while ($row = $result->fetch_row()) {
                for ($j = 0; $j < $result->field_count; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) $content .= '"'.$row[$j].'"';
                    else $content .= '""';
                    if ($j < ($result->field_count - 1)) $content.= ',';
                }
                $content .= "\n";
            }
        }
        $content .= "~~~~~~~\n";
    }
    $backup_name = $backup_name ? $backup_name : "diachron.csv";
    header("Content-Description: File Transfer");
    header("Content-disposition: attachment; filename=\"".$backup_name."\"");
    header("Content-Type: application/csv;");
    return $content;
}
function get_query($query) {
    GLOBAL $mysqli, $debug;
    if ($debug) echo "$query<br>";
    log_reads($query);
    $result = $mysqli->query($query) or die($mysqli->error);
    while($row = $result->fetch_assoc()) $results[] = $row;
    return $results;
}
function do_query($query) {
    GLOBAL $mysqli, $debug;
    if ($debug) echo "$query<br>";
    log_writes($query);
    $mysqli->query($query) or die($mysqli->error);
    return $mysqli;
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
function generate_segment_data($data) {
    if (!$data["value"]) return "segment not provided to generate_segment_data";
    $data = ["source_segment" => $data["value"], "target_segment" => $data["value"]];
    $pairs = query_pairs($data);

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
function generate_transition_data($data) {
    if ($data["value"]) $transition = $data["value"];
    $pairs = query_pairs(["transition" => $transition]);

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
        $result[] = ["data" => [
            "id" => $pair["source_segment"]."→".$pair["target_segment"],
            "label" => $pair["source_segment"]."→".$pair["target_segment"],
            "transition" => $pair["source_segment"]."→".$pair["target_segment"],
            "source" => $pair["source_segment"],
            "target" => $pair["target_segment"],
            "environment" => $pair["environment"],
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
function add_inventory($language, $segments) {
    $language_id = get_or_add_language(["value" => $language]);
    $segments = array_unique($segments);
    foreach($segments as $segment) {
        $segment_id = get_or_add_segment(["value" => $segment]);
        $query = "INSERT INTO languages_segments (language_id, segment_id) VALUES ('$language_id', '$segment_id')";
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
    if      ($data["source_segment_id"]) $source_segment = get_or_add_segment(["value" => $data["source_segment"]]);
    else if ($data["source_segment"])    $source_segment = $data["source_segment"];

    if      ($data["target_segment_id"]) $target_segment = get_or_add_segment(["value" => $data["target_segment"]]);
    else if ($data["target_segment"])    $target_segment = $data["target_segment"];

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
    return get_query($query);
}
function get_pairs_by_transition($data) {
    if      ($data["transition_id"]) $transition = get_transition(["value" => $data["transition_id"]]);
    else if ($data["transition"])    $transition = $data["transition"];
    if (!$transition) return -1;
    $limit = isset($data["limit"]) ? $data["limit"] : 0;
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
function get_pairs($data) {
    $limit = isset($data["limit"]) ? $data["limit"] : 0;
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
function get_segments($data=null) {
    $limit = isset($data["limit"]) ? $data["limit"] : 0;
    $query = "SELECT id, value FROM segments ORDER BY value";
    if ($limit) $query .= " LIMIT $limit";
    foreach(get_query($query) as $row) {
        $results[] = ["id" => $row["id"], "value" => $row["value"]];
    }
    return $results;
}
function check_segment($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "SELECT id, value FROM segments WHERE id = '$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "SELECT id, value FROM segments WHERE value = '$value'";
    }
    if (!$query) return "no id or value supplied to check_segment";
    $results = get_query($query);
    return !empty($results);
}
function get_segment($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "SELECT id, value FROM segments WHERE id = '$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "SELECT id, value FROM segments WHERE value = '$value'";
    }
    if (!$query) return "no id or value supplied to get_segment";
    return get_query($query);
}
function add_segment($data) {
    $value = $data["value"];
    if (!$value) return "no value supplied to get_segment";
    $query = "INSERT INTO segments (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_languages($data=null) {
    $limit = isset($data["limit"]) ? $data["limit"] : 0;
    $query = "SELECT id, value FROM languages";
    if ($limit) $query .= " LIMIT $limit";
    foreach(get_query($query) as $row) {
        $results[] = ["id" => $row["id"], "value" => $row["value"]];
    }
    return $results;
}
function check_language($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "SELECT id, value FROM languages WHERE id = '$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "SELECT id, value FROM languages WHERE value = '$value'";
    }
    if (!$query) return "no id or value supplied to check_language";
    $results = get_query($query);
    return !empty($results);
}
function get_language($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "SELECT id, value FROM languages WHERE id = '$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "SELECT id, value FROM languages WHERE value = '$value'";
    }
    if (!$query) return "no id or value supplied to get_language";
    return get_query($query);
}
function add_language($data) {
    $value = $data["value"];
    if (!$value) return -1;
    $query = "INSERT INTO languages (value) VALUES ('$value')";
    return do_query($query)->insert_id;
}
function get_transitions($data=null) {
    $limit = isset($data["limit"]) ? $data["limit"] : 0;
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
function check_transition($data) {
    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to check_transition";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to check_transition";

    $query = "SELECT * FROM transitions WHERE source_language_id = '$source_language_id' AND target_language_id = '$target_language_id'";
    return !empty(get_query($query));
}
function get_transition($data) {
    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to get_transition";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to get_transition";

    $query = "SELECT * FROM transitions WHERE source_language_id = '$source_language_id' AND target_language_id = '$target_language_id'";
    foreach(get_query($query) as $row) {
        $results[] = $row;
    }
    return $results;
}
function update_transition($data) {
    $id = $data["id"];

    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to update_transition";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to update_transition";

    $citation = $data["citation"];

    $query = "UPDATE transitions SET source_language_id = '$source_language_id' , target_language_id = '$target_language_id' , citation = '$citation' WHERE id='$id'";
    do_query($query);
}
function add_transition($data) {
    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to add_transition";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to add_transition";

    $citation = $data["citation"];

    $query = "INSERT INTO transitions (source_language_id, target_language_id, citation) VALUES ('$source_language_id', '$target_language_id', '$citation')";
    $result = do_query($query)->insert_id;
    return $result;
}
function get_or_add_segment($data) {
    $segment = $data["value"];
    return check_segment(["value" => $segment]) ? get_segment(["value" => $segment])[0]["id"] : add_segment(["value" => $segment]);
}
function get_or_add_language($data) {
    $language = $data["value"];
    return check_language(["value" => $language]) ? get_language(["value" => $language])[0]["id"] : add_language(["value" => $language]);
}
function get_or_add_transition($data) {
    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to get_or_add_transition";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to get_or_add_transition";

    $citation = $data["citation"];
    if (!$source_language_id or !$target_language_id) return;
    $checkTransition = check_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id]);
    if ($checkTransition) {
        return get_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id])[0]["id"];
    }
    else {
        return add_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id, "citation" => $citation]);
    }
}
function update_pair($data) {
    $id = $data["id"];
    if (!$id) return -1;

    if      ($data["source_segment"])    $source_segment_id = get_or_add_segment(["value" => $data["source_segment"]]);
    else if ($data["source_segment_id"]) $source_segment_id = $data["source_segment_id"];
    else return "no source_segment supplied to update_pair";

    if      ($data["target_segment"])    $target_segment_id = get_or_add_segment(["value" => $data["target_segment"]]);
    else if ($data["target_segment_id"]) $target_segment_id = $data["target_segment_id"];
    else return "no target_segment supplied to update_pair";

    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to update_pair";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to update_pair";

    $environment = $data["environment"];
    $notes = $data["notes"];

    $data = ["source_language_id" => $source_language_id, "target_language_id" => $target_language_id];
    $transition_id = get_or_add_transition($data);

    $query = "UPDATE pairs
    SET source_segment_id = '$source_segment_id'
      , target_segment_id = '$target_segment_id'
      , transition_id = '$transition_id'
      , environment = '$environment'
      , notes = '$notes'
    WHERE id='$id'";

    do_query($query);
}
function add_pair($data) {
    if      ($data["source_segment"])    $source_segment_id = get_or_add_segment(["value" => $data["source_segment"]]);
    else if ($data["source_segment_id"]) $source_segment_id = $data["source_segment_id"];
    else return "no source_segment supplied to add_pair";

    if      ($data["target_segment"])    $target_segment_id = get_or_add_segment(["value" => $data["target_segment"]]);
    else if ($data["target_segment_id"]) $target_segment_id = $data["target_segment_id"];
    else return "no target_segment supplied to add_pair";

    if      ($data["source_language"])    $source_language_id = get_or_add_language(["value" => $data["source_language"]]);
    else if ($data["source_language_id"]) $source_language_id = $data["source_language_id"];
    else return "no source_language supplied to add_pair";

    if      ($data["target_language"])    $target_language_id = get_or_add_language(["value" => $data["target_language"]]);
    else if ($data["target_language_id"]) $target_language_id = $data["target_language_id"];
    else return "no target_language supplied to add_pair";

    $environment = $data["environment"];
    $notes = $data["notes"];

    if ($data["transition_id"]) $transition_id = $data["transition_id"];
    else                        $transition_id = get_or_add_transition(["source_language_id" => $source_language_id, "target_language_id" => $target_language_id]);

    $query = "SELECT * from pairs WHERE source_segment_id = '$source_segment_id' AND target_segment_id = '$target_segment_id' AND transition_id = '$transition_id'";
    $pair = get_query($query);
    if (!empty($pair) and $environment) {
        $id = $pair[0]["id"];
        if (!$id) return "no id found for pair, but somehow a result was returned?";
        $query = "UPDATE pairs SET environment = concat(environment, ' $environment') WHERE id='$id'";
        do_query($query);
    }
    else {
        $query = "INSERT INTO pairs (source_segment_id, target_segment_id, transition_id, environment, notes) VALUES ('$source_segment_id', '$target_segment_id', '$transition_id', '$environment', '$notes');";
        return do_query($query)->insert_id;
    }
}
function update_segment($data) {
    $id = $data["id"];
    $value = $data["value"];
    if (!$id or !$value) return "id or value not supplied to update_segment";
    $query = "UPDATE segments SET value = '$value' WHERE id='$id'";
    do_query($query);
}
function update_language($data) {
    $id = $data["id"];
    $value = $data["value"];
    if (!$id or !$value) return "id or value not supplied to update_language";
    $query = "UPDATE languages SET value = '$value' WHERE id='$id'";
    do_query($query);
}
function remove_pair($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "DELETE FROM pairs WHERE id='$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "DELETE FROM pairs WHERE id='$value'";
    }
    if (!$query) return "no id or value supplied to remove_pair";
    $query = "DELETE FROM pairs WHERE id='$id'";
    do_query($query);
}
function remove_segment($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "DELETE FROM segments WHERE id='$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "DELETE FROM segments WHERE id='$value'";
    }
    if (!$query) return "no id or value supplied to remove_segment";
    $query = "DELETE FROM segments WHERE id='$id'";
    do_query($query);
}
function remove_language($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "DELETE FROM languages WHERE id='$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "DELETE FROM languages WHERE id='$value'";
    }
    if (!$query) return "no id or value supplied to remove_language";
    do_query($query);
}
function remove_transition($data) {
    if ($data["id"]) {
        $id = $data["id"];
        $query = "DELETE FROM transitions WHERE id='$id'";
    }
    else if ($data["value"]) {
        $value = $data["value"];
        $query = "DELETE FROM transitions WHERE id='$value'";
    }
    if (!$query) return "no id or value supplied to remove_transition";
    do_query($query);
}
function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {echo "<pre>";  print_r($i); echo "</pre>";}
        else {print_r($i); echo "\t";}
    }
    echo "\n<br>";
}
?>