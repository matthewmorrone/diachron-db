<?
error_reporting(E_ERROR);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_POST) {
    if ($_POST["debug"]) {
        printr($_POST);
    }
    extract($_POST);

    include 'credentials.txt';

    $mysqli = new mysqli($hostname, $username, $password);
    mysqli_select_db($mysqli, $database);

    switch($mode) {
        case "connect": 
            printr($mysqli, "$username@$hostname"); 
        break;
        case "tables": 
            $query = "SHOW TABLES FROM $database";
            print_query($mysqli, $query);
        break;
        case "data":
            print_query($mysqli, "select * from phones");
            print_query($mysqli, "select * from environments");
            print_query($mysqli, "select * from languages");
        break;
        case "query":
            // $query = "
            // SELECT * FROM environments_pairs 
            // inner join environments
            // inner join pairs
            // on environments_pairs.pair_id = pairs.id
            // and environments_pairs.environment_id = environments.id
            // ";
            // print_query($mysqli, $query);
            $query = "
select source_phone, target_phone, source_language, target_language from
(
select pairs.id as pair_id
, source_phone.ipa as source_phone
, target_phone.ipa as target_phone
, source_language.name as source_language
, target_language.name as target_language
from pairs 
inner join phones as source_phone
inner join phones as target_phone
inner join transitions
inner join languages as source_language
inner join languages as target_language
on pairs.source_phone_id = source_phone.id
and pairs.target_phone_id = target_phone.id
and pairs.transition_id = transitions.id
and transitions.source_language_id = source_language.id
and transitions.target_language_id = target_language.id
) as pairs
;
            ";
            print_query($mysqli, $query);
        break;
        case "phones":
            echo json_encode(get_phones($mysqli));
        break;
        case "languages":
            echo json_encode(get_languages($mysqli));
        break;
        case "insert":
            if (strcmp($table, "pairs") === 0) {
                echo add_pair($mysqli, $data);
            }
            if (strcmp($table, "phones") === 0) {
                echo add_phone($mysqli, $phone);
            }
            if (strcmp($table, "languages") === 0) {
                echo add_language($mysqli, $language);
            }
        break;
        default: break;
    }
}

function show_tables($mysqli) {
    $query = "SHOW TABLES FROM $database";
    return get_query($mysqli, $query);
}
function get_query($mysqli, $query) {
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
    return $results;
}
function get_phones($mysqli) {
    $result = mysqli_query($mysqli, "SELECT id, ipa FROM phones");
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = $row["ipa"];
    }
    return $results;
}
function check_phone($mysqli, $phone) {
    $query = "SELECT id, ipa FROM phones WHERE ipa = '$phone'";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = $row["ipa"];
    }
    return $results;
}
function get_phone($mysqli, $phone) {
    $query = "SELECT id, ipa FROM phones WHERE ipa = '$phone'";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[] = $row["id"];
    }
    return $results[0];
}
function add_phone($mysqli, $phone) {
    $query = "INSERT INTO phones (ipa) VALUES ('$phone')";
    mysqli_query($mysqli, $query);
    return get_phone($mysqli, $phone);
}
function get_languages($mysqli) {
    $result = mysqli_query($mysqli, "SELECT id, name FROM languages");
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = $row["name"];
    }
    return $results;
}
function check_language($mysqli, $language) {
    $query = "SELECT id, name FROM languages WHERE name = '$language'";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = $row["ipa"];
    }
    return $results;
}
function get_language($mysqli, $language) {
    $query = "SELECT id, name FROM languages WHERE name = '$language'";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[] = $row["id"];
    }
    return $results[0];
}
function add_language($mysqli, $name) {
    $query = "INSERT INTO languages (name) VALUES ('$name')";
    mysqli_query($mysqli, $query);
    return get_language($mysqli, $name);
}
function get_transitions($mysqli) {
    $result = mysqli_query($mysqli, "SELECT id, source_language_id, target_language_id FROM transitions");
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = [
            "source_language_id" => $row["source_language_id"],
            "target_language_id" => $row["target_language_id"]
        ];
    }
    return $results;
}
function check_transition($mysqli, $source, $target) {
    $query = "SELECT * FROM transitions
    where source_language_id = '$source'
    and   target_language_id = '$target'
    ";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        $results[$row["id"]] = $row;
    }
    return $results;
}
function get_transition($mysqli, $source, $target) {
    $query = "SELECT * FROM transitions
    where source_language_id = '$source'
    and   target_language_id = '$target'
    ";
    $result = mysqli_query($mysqli, $query);
    $results = array();
    while($row = mysqli_fetch_assoc($result)) {
        // $results[$row["id"]] = [
        //     "source_language_id" => $row["source_language_id"],
        //     "target_language_id" => $row["target_language_id"]
        // ];
        $results[] = $row;
    }
    return $results[0]["id"];
}
function add_transition($mysqli, $source, $target) {
    $query = "INSERT INTO transitions (source_language_id, target_language_id) VALUES ('$source', '$target')";
    mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
    return get_transition($mysqli, $source, $target);
}
function get_or_add_phone($mysqli, $phone) {
    if (count(check_phone($mysqli, $phone))) { 
        // echo "$phone found.\n"; 
        return get_phone($mysqli, $phone);
    }
    else { 
        // echo "$phone not found.\n";
        return add_phone($mysqli, $phone);
    }
}
function get_or_add_language($mysqli, $language) {
    if (count(check_language($mysqli, $language))) { 
        // echo "$language found.\n"; 
        return get_language($mysqli, $language);
    }
    else { 
        // echo "$language not found.\n";
        return add_language($mysqli, $language);
    }
}
function get_or_add_transition($mysqli, $source, $target) {
    if (count(check_transition($mysqli, $source, $target))) { 
        // echo "$source -> $target found.\n"; 
        return get_transition($mysqli, $source, $target);
    }
    else { 
        // echo "$source -> $target not found.\n";
        return add_transition($mysqli, $source, $target);
    }
}
function add_pair($mysqli, $data) {
    $source_phone = $data["source_phone"];
    $target_phone = $data["target_phone"];
    $source_lang = $data["source_lang"];
    $target_lang = $data["target_lang"];

    $source_phone_id = get_or_add_phone($mysqli, $source_phone);
    $target_phone_id = get_or_add_phone($mysqli, $target_phone);
    $source_lang_id = get_or_add_language($mysqli, $source_lang);
    $target_lang_id = get_or_add_language($mysqli, $target_lang);

    echo "source_phone_id: $source_phone ($source_phone_id)\n";
    echo "target_phone_id: $target_phone ($target_phone_id)\n";
    echo "source_lang_id: $source_lang ($source_lang_id)\n";
    echo "target_lang_id: $target_lang ($target_lang_id)\n";

    $transition_id = get_or_add_transition($mysqli, $source_lang_id, $target_lang_id);
    echo "transition_id: $transition_id\n";


    $query = "select * from pairs
    where source_phone_id = '$source_phone_id'
    and target_phone_id = '$target_phone_id'
    and transition_id = '$transition_id'
    ";
    $pair = get_query($mysqli, $query);
    if (count($pair) > 0) {
        echo -1;
        // echo json_encode($pair);
    }
    else {
        $query = "insert into pairs (source_phone_id, target_phone_id, transition_id)
        values ('$source_phone_id', '$target_phone_id', '$transition_id');";
        echo $query;
        mysqli_query($mysqli, $query);
        echo 1;
    }
    //TODO: check if a pair exists with specific source, target and transition. if not, add it
    // $query = "select * from pairs where ";

}
function print_query($mysqli, $query) {
    trim($query);
    $result = $mysqli->query($query);
    $num_rows = $result->num_rows;
    $fields = $result->fetch_fields();
    echo "<table>";
    for($i = 0; $i < $num_rows; $i++):
        $row = $result->fetch_assoc();
        if ($i == 0):
            echo "<thead>";
            echo "<tr>";
            foreach($row as $field=>$cell):
                echo "<th>$field</th>";
            endforeach;
            echo "</tr>";
            echo "</thead>";
        endif;
        echo "<tr>";
        foreach($row as $field=>$cell):
            echo "<td>$cell</td>";
        endforeach;
        echo "</tr>";
    endfor;
    echo "</table>";
}
function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {echo "<pre>"; print_r($i); echo "</pre>\n"; }
        else {print_r($i); echo "\n";}
    }
}
?>