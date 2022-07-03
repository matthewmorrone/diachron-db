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
        case "tables":
            $query = "SHOW TABLES FROM $database";
            printjson(get_tables());
        break;
        case "test":
            $source_phone = "q";
            $target_phone = "k";
            $source_lang = "Old Turkic";
            $target_lang = "Turkish";

            echo "<pre>";
            printr("count_tables: ", count_tables());
            printr("get_tables: ", json_encode(get_tables()));
            printr("get_phones: ", count(get_phones()));
            printr("get_languages: ", count(get_languages()));
            printr("get_transitions: ", count(get_transitions()));

            printr("check_phone $source_phone: ", check_phone($source_phone));
            printr("get_phone $source_phone: ", get_phone($source_phone)[0]["id"]);

            printr("check_phone $target_phone: ", check_phone($target_phone));
            printr("get_phone $target_phone: ", get_phone($target_phone)[0]["id"]);

            printr("check_language $source_lang: ", check_language($source_lang));
            $source_id = get_language($source_lang)[0]["id"];
            printr("get_language $source_lang: ", $source_id);

            printr("check_language $target_lang: ", check_language($target_lang));
            $target_id = get_language($target_lang)[0]["id"];
            printr("get_language $target_lang: ", $target_id);

            printr("check_transition $source_lang → $target_lang: ", check_transition($source_id, $target_id));
            $transition = get_transition($source_id, $target_id)[0];
            printr("get_transition $source_lang → $target_lang: ", $transition["id"]);

            echo "</pre>";
        break;
        case "view_data":
            json_encode(get_query("select * from phones"));
            json_encode(get_query("select * from environments"));
            json_encode(get_query("select * from languages"));
        break;
        case "pairs":
            echo json_encode(get_pairs());
        break;
        case "phones":
            echo json_encode(get_phones());
        break;
        case "languages":
            echo json_encode(get_languages());
        break;
        case "insert":
            if (strcmp($table, "pair") === 0)     echo add_pair($data);
            if (strcmp($table, "phone") === 0)    echo add_phone($phone);
            if (strcmp($table, "language") === 0) echo add_language($language);
        break;
        case "remove":
            if (strcmp($table, "pair") === 0)     echo remove_pair($id);
        break;
        default: break;
    }
}
function get_query($query) {
    GLOBAL $mysqli;
    $result = $mysqli->query($query);
    while($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
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
    GLOBAL $mysqli;
    $query = "
    select id, source_lang, source_phone, target_lang, target_phone from
    (
    select pairs.id as id
    , source_phone.ipa as source_phone
    , target_phone.ipa as target_phone
    , source_lang.name as source_lang
    , target_lang.name as target_lang
    from pairs
    inner join phones as source_phone
    inner join phones as target_phone
    inner join transitions
    inner join languages as source_lang
    inner join languages as target_lang
    on pairs.source_phone_id = source_phone.id
    and pairs.target_phone_id = target_phone.id
    and pairs.transition_id = transitions.id
    and transitions.source_language_id = source_lang.id
    and transitions.target_language_id = target_lang.id
    ) as pairs
    order by id DESC
    ;";
    return get_query($query);
}
function get_phones() {
    $query = "SELECT id, ipa FROM phones";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = $row["ipa"];
    }
    return $results;
}
function check_phone($phone) {
    $query = "SELECT id, ipa FROM phones WHERE ipa = '$phone'";
    $results = get_query($query);
    return !empty($results);
}
function get_phone($phone) {
    GLOBAL $mysqli;
    $query = "SELECT id, ipa FROM phones WHERE ipa = '$phone'";
    return get_query($query);
}
function add_phone($phone) {
    GLOBAL $mysqli;
    $query = "INSERT INTO phones (ipa) VALUES ('$phone')";
    $mysqli->query($query);
    return $mysqli->insert_id;
}
function get_languages() {
    GLOBAL $mysqli;
    $query = "SELECT id, name FROM languages";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = $row["name"];
    }
    return $results;
}
function check_language($language) {
    $query = "SELECT id, name FROM languages WHERE name = '$language'";
    return count(get_query($query));
}
function get_language($language) {
    GLOBAL $mysqli;
    $query = "SELECT id, name FROM languages WHERE name = '$language'";
    return get_query($query);
}
function add_language($name) {
    GLOBAL $mysqli;
    $query = "INSERT INTO languages (name) VALUES ('$name')";
    $mysqli->query($query);
    return $mysqli->insert_id;
}
function get_transitions() {
    GLOBAL $mysqli;
    $query = "SELECT id, source_language_id, target_language_id FROM transitions";
    foreach(get_query($query) as $row) {
        $results[$row["id"]] = [
            "source_language_id" => $row["source_language_id"],
            "target_language_id" => $row["target_language_id"]
        ];
    }
    return $results;
}
function check_transition($source, $target) {
    GLOBAL $mysqli;
    $query = "SELECT * FROM transitions
    where source_language_id = '$source'
    and   target_language_id = '$target'
    ";
    return !empty(get_query($query));
}
function get_transition($source, $target) {
    GLOBAL $mysqli;
    $query = "SELECT * FROM transitions
    where source_language_id = '$source'
    and   target_language_id = '$target'
    ";
    foreach(get_query($query) as $row) {
        $results[] = $row;
    }
    return $results;
}
function add_transition($source, $target) {
    GLOBAL $mysqli;
    $query = "INSERT INTO transitions (source_language_id, target_language_id) VALUES ('$source', '$target')";
    mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
    return $mysqli->insert_id;
}
function get_or_add_phone($phone) {
    if (check_phone($phone)) {
        return get_phone($phone)[0]["id"];
    }
    else {
        return add_phone($phone);
    }
}
function get_or_add_language($language) {
    if (check_language($language)) {
        return get_language($language)[0]["id"];
    }
    else {
        return add_language($language);
    }
}
function get_or_add_transition($source, $target) {
    if (check_transition($source, $target)) {
        return get_transition($source, $target)[0]["id"];
    }
    else {
        return add_transition($source, $target);
    }
}
function remove_pair($id) {
    GLOBAL $mysqli;
    $query = "DELETE FROM pairs WHERE id='$id'";
    $mysqli->query($query);
}
function add_pair($data, $debug=false) {
    GLOBAL $mysqli;
    $source_phone = $data["source_phone"];
    $target_phone = $data["target_phone"];
    $source_lang = $data["source_lang"];
    $target_lang = $data["target_lang"];

    $source_phone_id = get_or_add_phone($source_phone);
    $target_phone_id = get_or_add_phone($target_phone);
    if ($debug) {
        echo "source_phone_id: $source_phone ($source_phone_id)\n";
        echo "target_phone_id: $target_phone ($target_phone_id)\n";
    }
    $source_lang_id = get_or_add_language($source_lang);
    $target_lang_id = get_or_add_language($target_lang);
    if ($debug) {
        echo "source_lang_id: $source_lang ($source_lang_id)\n";
        echo "target_lang_id: $target_lang ($target_lang_id)\n";
    }

    $transition_id = get_or_add_transition($source_lang_id, $target_lang_id);
    if ($debug) {
        echo "transition_id: $transition_id\n";
    }

    $query = "select * from pairs
    where source_phone_id = '$source_phone_id'
    and target_phone_id = '$target_phone_id'
    and transition_id = '$transition_id'
    ";
    $pair = get_query($query);
    if (!empty($pair)) {
        printr($pair);
    }
    else {
        $query = "insert into pairs (source_phone_id, target_phone_id, transition_id) values ('$source_phone_id', '$target_phone_id', '$transition_id');";
        $mysqli->query($query);
        echo $mysqli->insert_id;
    }
}
function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {echo "<pre>"; print_r($i); echo "</pre>\n"; }
        else {print_r($i); echo "\n";}
    }
}
?>