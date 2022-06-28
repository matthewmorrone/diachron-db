<?
error_reporting(0);
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
function get_phone_id($mysqli, $phone) {
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
    $result = mysqli_query($mysqli, $query);
    return $result;
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
function get_language_id($mysqli, $language) {
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
    $result = mysqli_query($mysqli, $query);
    return $result;
}
function add_pair($mysqli, $data) {
    $source_ipa = $data["source_ipa"];
    $target_ipa = $data["target_ipa"];
    $source_lang = $data["source_lang"];
    $target_lang = $data["target_lang"];

    if (count(check_phone($mysqli, $source_ipa))) { 
        echo "$source_ipa found.\n"; 
        printr(get_phone_id($mysqli, $source_ipa));
    }
    else { 
        echo "$source_ipa not found.\n"; 
        echo add_phone($mysqli, $source_ipa)."\n";
    }

    if (count(check_phone($mysqli, $target_ipa))) { 
        echo "$target_ipa found.\n"; 
        printr(get_phone_id($mysqli, $target_ipa))."\n";
    }
    else { 
        echo "$target_ipa not found.\n"; 
        echo add_phone($mysqli, $target_ipa);
    }

    if (count(check_language($mysqli, $source_lang))) { 
        echo "$source_lang found.\n"; 
        printr(get_language_id($mysqli, $source_lang));
    }
    else { 
        echo "$source_lang not found.\n"; 
        echo add_language($mysqli, $source_lang)."\n";
    }

    if (count(check_language($mysqli, $target_lang))) { 
        echo "$target_lang found.\n"; 
        printr(get_language_id($mysqli, $target_lang));
    }
    else { 
        echo "$target_lang not found.\n"; 
        echo add_language($mysqli, $target_lang)."\n";
    }

    //TODO: check if transition exists, if not, add it
    
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