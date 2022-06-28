<?

if ($_POST) {
    extract($_POST);

    $credentials = array_map(function($a) {
        return trim($a);
    }, file('credentials.txt'));

    $address  = (isset($address)  ? $address  : $credentials[0]);
    $username = (isset($username) ? $username : $credentials[1]);
    $password = (isset($password) ? $password : $credentials[2]);
    // $connect = connect($server, $username, $password);
    // $link = connect($account, $username, $password, $database) or die(mysqli_error($link));

    $database = (isset($database) ? $database : $credentials[3]);
    // $database = database($database, $connect);
    // $database = (isset($database) ? $database : "");

    switch($mode) {
        case "all":
            $mysqli = connect($address, $username, $password, $database);
            $query = 'SELECT id, created, title, blurb, image FROM posts ORDER BY created';
            echo json_query($mysqli, $query);
        break;
        case "get":
            $mysqli = connect($address, $username, $password, $database);
            $id = (int)$id;
            if (is_int($id)) {
                $query = "SELECT id, created, modified, title, blurb, content, image FROM posts WHERE id = $id";
                echo json_encode(get_query($mysqli, $query)[0][0]);
            }
        break;
        case "auth":
            if (strcmp($password, 'will') === 0) {
                echo 'win';
            }
            else {
                echo 'fail';
            }
        break;
        case "new":
            $mysqli = connect($address, $username, $password, $database);
            $title = mysqli_real_escape_string($mysqli, $title);
            $blurb = mysqli_real_escape_string($mysqli, $blurb);
            $content = mysqli_real_escape_string($mysqli, $content);
            $query = "INSERT INTO posts (title, blurb, content) VALUES ('$title', '$blurb', '$content')";
            $result = $mysqli->query($query);
            // echo $mysqli->error."\n";
        break;
        case "edit":
            $mysqli = connect($address, $username, $password, $database);
            $title = mysqli_real_escape_string($mysqli, $title);
            $blurb = mysqli_real_escape_string($mysqli, $blurb);
            $content = mysqli_real_escape_string($mysqli, $content);
            $query = "UPDATE posts SET title = '$title', blurb = '$blurb', content = '$content'";
            $result = $mysqli->query($query);
            // echo $mysqli->error."\n";
        break;
        case "delete":
            $mysqli = connect($address, $username, $password, $database);
            $query = "DELETE FROM posts WHERE id = $id";
            $result = $mysqli->query($query);
            // echo $mysqli->error."\n";
        break;
        default:
            echo "fail\n";
        break;
    }
    if (isset($mysqli)) {
        mysqli_close($mysqli);
    }
}

if ($_POST) {
    extract($_POST);

    $server = (isset($server) ? $server : "localhost");
    $username = (isset($username) ? $username : "root");
    $password = (isset($password) ? $password : "root");

    $database = (isset($database) ? $database : "default");

    $connect = connect($server, $username, $password, $database);

    switch($mode) {
        case "connect": echo "$connect $username@$server"; break;

        case "show databases": show_databases(); break;
        case "show tables": show_tables(); break;
        case "show columns": show_columns(); break;
        case "show table": show_table(); break;

        case "show files": show_files(); break;
        case "show file": show_file($file); break;

        case "add database": add_database(); break;
        case "rename database": rename_database(); break;
        case "remove database": remove_database(); break;

        case "add table": add_table(); break;
        case "rename table": rename_table(); break;
        case "truncate table": truncate_table(); break;
        case "remove table": remove_table(); break;

        case "add column": add_column(); break;
        case "rename column": rename_column(); break;
        case "remove column": remove_column(); break;

        case "ip_address":
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else 										$ip = $_SERVER['REMOTE_ADDR'];
            echo trim($ip);
        break;
        case "current_directory":
            echo getcwd();
        break;
        case "new_directory":
            mkdir($newfoldername) or die($newfoldername." not created.");
            chmod($newfoldername, 0777);
            echo "$newfoldername created";
            break;
        case "list_directory":
            //$dir = $directory;
            $dir = getcwd();
            $counter = 0;
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file == "." || $file == ".." || substr($file, 0, 1) == ".") continue;
                        $directory[0][] = $counter;
                        $directory[1][] = $file;
                        if (is_dir($file)) $directory[2][] = "directory";
                        else $directory[2][] = "file";
                        $directory[3][] = "$dir/$file";
                        $counter++;
                    }
                }
            }
            echo json_encode($directory);
        break;
        case "clear_directory": break;
        case "remove_directory":
            rmdir($directory) or die("$directory not removed.");
            echo "$directory removed.";
        break;
        case "new_file":
            $newfilename = $newfilename;
            $f = fopen($newfilename, "w+");
            chmod($newfilename, 0777);
            fclose($f);
            echo "$newfilename created.";
        break;
        case "get_file": break;
        case "clear_file": break;
        case "delete_file": break;
        case "file_get_contents": echo file_get_contents($address); break;
        case "": break;
        default: break;
    }
}




function santeria($query) {
    $query = strtolower($query);
    $query = str_replace(".", " ", $query);
    $query = str_replace(",", " ", $query);
    $query = str_replace("/", " ", $query);
    $query = str_replace("!", " ", $query);
    $query = str_replace(";", " ", $query);
    $query = str_replace("(", " ", $query);
    $query = str_replace(")", " ", $query);
    $query = str_replace("?", " ", $query);
    $query = str_replace("--", " ", $query);
    $query = str_replace("[", " ", $query);
    $query = str_replace("]", " ", $query);
    $query = str_replace("<", " ", $query);
    $query = str_replace("]", " ", $query);
    $query = str_replace(">", " ", $query);
    $query = str_replace("\"", " ", $query);
    $query = str_replace("'", " ", $query);
    $query = str_replace(":", " ", $query);
    $query = str_replace("+", " ", $query);
    $query = preg_replace('!\s+!', ' ', $query);
    return trim($query);
}
function sanitize($query) {
    trim($query);
    $query = str_replace("\"", "", $query);
    $query = str_replace("\'", "", $query);
    $query = str_replace(" ", "_", $query);
    $query = str_replace("(", "", $query);
    $query = str_replace(")", "", $query);
    $query = str_replace("?", "", $query);
    $query = str_replace("%", "", $query);
    $query = htmlspecialchars($query);
    return $query;
}
function desanitize($query) {
    $query = trim($query);
    $query = stripslashes($query);
    $query = htmlspecialchars($query);
    return $query;
}
function addspaces($query) {
    return str_replace("_", " ", $query);
}


function connect($address, $username, $password, $database) {
    $mysqli = new mysqli($address, $username, $password, $database);
    return $mysqli or die(mysqli_error($mysqli));
}

function pluck($array, $i = 0) {
    $array2 = [];
    foreach($array as $key=>$val) {
        $array2[] = $val[$i];
    }
    return $array2;
}
function json_query($link, $query) {
    return json_encode(get_query($link, $query));
}


function database($d, $c) {
    if (mysqli_select_db($d, $c)) {return $d;}
}





function show_tables() {
    $query = "SHOW TABLES FROM ".$database;
    $results = get_query($query);
    $result = '<select><option></option>';
    foreach ($results as $k=>$v) {
        foreach($v as $x=>$y) {
            $result .= '<option>'.$y.'</option>';
        }
    }
    $result .= '</select>';
    echo $result;
}
function show_columns() {
    $query = "SHOW COLUMNS FROM ".$table;
    $results = get_query($query);
    $result = '<select><option></option>';
    foreach ($results as $k=>$v) {
        $result .= '<option datatype="'.$v["Type"].'">'.$v["Field"].'</option>';
    }
    $result .= '</select>';
    echo $result;
}
function show_table() {
    $query = "SELECT * FROM ".$_POST['table'];
    $results = get_query($query);
    $result[] = array_keys($results[0]);
    foreach($results as $r) {
        $result[] = array_values($r);
    }
    echo json_encode($result);
}


function add_database() {
    $query = "CREATE DATABASE IF NOT EXISTS ".$database;
    $result = get_query($query);
    print_r($result);
}
function rename_database() {
    $query = "RENAME DATABASE ".$database." TO ".$newname;
    echo $query;
    $result = get_query($query);
    print_r($result);
}
function remove_database() {
    $query = "DROP DATABASE IF EXISTS ".$database;
    $result = get_query($query);
    print_r($result);
}
function add_table() {
    $query = "CREATE TABLE IF NOT EXISTS ".$table."(id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id))";
    $result = get_query($query);
    print_r($result);
}
function rename_table() {
    $query = "RENAME TABLE ".$table." TO ".$newname;
    $result = get_query($query);
    print_r($result);
}
function truncate_table() {
    $query = "TRUNCATE TABLE IF EXISTS ".$table;
    $result = get_query($query);
    print_r($result);
}
function remove_table() {
    $query = "DROP TABLE IF EXISTS ".$table;
    $result = get_query($query);
    print_r($result);
}
function add_column() {
    $query = "ALTER TABLE ".$table." ADD ".$column." ".$datatype; //AFTER name; //FIRST
    $result = get_query($query);
}
function rename_column() {
    $query = "ALTER TABLE ".$table." CHANGE ".$column." ".$newname." ".$datatype;
    $result = get_query($query);
    print_r($result);
}
function remove_column() {
    $query = "ALTER TABLE ".$table." DROP ".$column;
    $result = get_query($query);
    print_r($result);
}

function generate_query($array) {
//print "<ul>"; foreach ($_POST as $key => $value) {print "<li>".$key.": ".$value."</li>"; if (is_array($value)) {$subarray = $value; print "<ul>"; foreach ($subarray as $key => $value) {print "<li>".$key.": ".$value."</li>";} print "</ul>";}} print "</ul>";
    $query = "";
    if ($array["command"] == "CREATE") {
        $query .= $array["command"];
        $query .= " TABLE ";
        $query .= $array["newtablename"];
        $query .= " (number int PRIMARY KEY)";
    }
    if ($array["command"] == "DROP") {
        if ($array["tablename"] != "null") {
            $query .= "DROP TABLE IF EXISTS ".$array["tablename"][0];
        }
    }
    if ($array["command"] == "TRUNCATE") {
        if ($array["tablename"] != "null") {
            $query .= "TRUNCATE TABLE IF EXISTS ".$array["tablename"][0];
        }
    }
    if ($array["command"] == "DELETE") {
        $query .= $array["command"];
        $query .= " FROM ";
        $query .= $array["tablename"][0];
        $query .= " WHERE number = ".$array["number"];
    }
    if ($array["command"] == "ALTER") {
        $query .= $array["command"];
        $query .= " TABLE ";
        $query .= $array["tablename"];
        $query .= " CHANGE ";
        $query .= $array["oldname"];
        $query .= " ";
        $query .= $array["newname"];
    }
    if ($array["command"] == "SELECT") {
        if ($array["b_cols"]) {
            $query .= $array["command"];
            if ($array["distinct"]) {$query .= " DISTINCT";}
            $columns = $array["b_cols"];
            foreach ($columns as $column) {$query .= " ".$column.",";}
            $query = chomp($query, 1);
            $query .= " FROM ".$array["tables"];

            if ($array["paramed"]) {
                for ($i = 0; $i < count($array["param"]); $i++) {
                    if ($array["values"][$i]) {
                        if ($array["bool"][$i]) {$query .= " ".$array["bool"][$i];}
                        if ($array["param"][$i]) {$query .= " ".$array["param"][$i];}
                        if ($array["not"][$i]) {$query .= " ".$array["not"][$i];}
                        if ($array["operator"][$i]) {$query .= " ".$array["operator"][$i];}
                        if ($array["operator"][$i] == "IN") {
                            $query .= " (";
                            $words = explode(", ", $array["values"][$i]);
                            foreach ($words as $word) {$query .= "'".$word."', ";}
                            $query = substr($query, 0, strlen($query)-2);
                            $query .= ")";
                        }
                        else if ($array["operator"][$i] == "BETWEEN") {$query .= " ".$array["value1"][$i]." AND ".$array["value2"][$i];}
                        else {$query .= " ".$array["values"][$i];}
                    }
                }
            }
            if ($array["grouped"] == "on") 	{$query .= " GROUP BY ".$array["group"];}
            if ($array["ordered"] == "on") 	{$query .= " ORDER BY ".$array["order"];
            if ($array["direction"])		{$query .= " ".$array["direction"];}}
            if ($array["limited"] == "on") 	{$query .= " LIMIT ".$array["limit"]." ";}
        }
    }
    return $query;
}

function get_options($query) {
    trim($query);
    $query = stripslashes($query);
    $result = mysqli_query($query) or die($query." ".mysqli_error());

    $row = mysqli_fetch_array($result);
    $num_rows = mysqli_num_rows($result);
    $num_fields = mysqli_num_fields($result);

    print '<select size="10">';

    $values = array_values($row);
    if ($buffer == "yes") {
        print "<option value='' selected=true></option>";
    }
    for ($index = 0; $index < 1; $index++) {
        $value = htmlspecialchars($values[2 * $index + 1]);
        print "<option value=".$value." ";
        if ($buffer == "no") {
            print "selected=true";
        }
        print ">".$value."</option>";
    }
    while ($row = mysqli_fetch_array($result)) {
        $values = array_values($row);
        for ($index = 0; $index < 1; $index++) {
            $value = htmlspecialchars($values[2 * $index + 1]);
            print "<option value=".$value." ";
            if ($buffer == "no") {
                print "selected=true";
            }
            print ">".$value."</option>";
        }
    }
    print "</select>";
}
function check_table($table) {
    if (count($table[0] != count($table[1]))) {
        print("Make sure the first line and the data entries have an equal number of entries!<br />"); return -1;
    }
    for ($i = 1; $i < count($table); $i++) {
        if (count($table[$i]) != count($table[0])) {
            print("Row $i does not have the correct number of data entries!<br />"); {
                return $i;
            }
        }
    }
    return true;
}



function start() {
    date_default_timezone_set("America/New_York");
    // require("phpquery.php");
    return new DateTime();
}

function finish($start) {
    $timediff = $start->diff(new DateTime());
    $minutes = str_pad($timediff->i, 2, '0', STR_PAD_LEFT);
    $seconds = str_pad($timediff->s, 2, '0', STR_PAD_LEFT);

    echo "\n";
    echo $minutes.':'.$seconds;
    echo "\n\n";
}

function get_urls($url) {
    $data = file_get_contents($url);
    $data = strip_tags($data, "<a>");
    $data = preg_replace("/\s+/", " ", $data);
    $d = preg_split("/<\/a>/", $data);
    foreach ($d as $k => $u) {
        if(strpos($u, "<a") !== FALSE) {
            $u = preg_replace("/.*<a.+href=\"/sm", "", $u);
            $u = preg_replace("/\".*/", "", $u);
            $u = preg_replace('/\?.*/', '', $u);
            $d[trim($k)] = trim($u);
        }
    }
    return array_values(array_unique(array_filter($d, "urlfilter")));
}

function reset_dir($dirPath) {
    if (!is_dir($dirPath)) {return;}
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {$dirPath .= '/';}
    $files = glob($dirPath.'*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {self::deleteDir($file);}
        else {unlink($file);}
    }
    @rmdir($dirPath);
    @mkdir($dirPath);
    return $dirPath;
}

function chomp($i, $c) {return substr($i, 0, strlen($i)-$c); }
function is_url($i) {return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $i);}
function xerror() {print("<br /><br />Error At:<br />___".__LINE__."___<br /><br />"); }
function xecho() {
    foreach (func_get_args() as $i) {
        if (is_array($i)) {echo "<pre>"; print_r($i); echo "</pre>"; }
        else {echo $i;}
        echo "<br />";
    }
}
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// get_defined_vars();
// get_defined_functions();
// define("MY_CONSTANT", 1);
// print_r(get_defined_constants(true));

function geoip() {
    // geoip_continent_code_by_name(); //Get the two letter continent code
    // geoip_country_code_by_name(); //Get the two letter country code
    // geoip_country_code3_by_name(); //Get the three letter country code
    // geoip_country_name_by_name(); //Get the full country name
    // geoip_database_info(); //Get GeoIP Database information
    // geoip_db_avail(); //Determine if GeoIP Database is available
    // geoip_db_filename(); //Returns the filename of the corresponding GeoIP Database
    // geoip_db_get_all_info(); //Returns detailed information about all GeoIP database types
    // geoip_id_by_name(); //Get the Internet connection type
    // geoip_isp_by_name(); //Get the Internet Service Provider (ISP) name
    // geoip_org_by_name(); //Get the organization name
    // geoip_record_by_name(); //Returns the detailed City information found in the GeoIP Database
    // geoip_region_by_name(); //Get the country code and region
    // geoip_region_name_by_code(); //Returns the region name for some country and region code combo
    // geoip_time_zone_by_country_and_region(); //Returns the time zone for some country and region code combo

    $infos = geoip_db_get_all_info();
    if (is_array($infos)) {
        return $infos;
    }

}

function proxy($url, $header = 1, $timeout = 60) {
    $proxy = '50.22.206.179:80';
    $referer = 'http://www.google.com/';
    $agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $result['EXE'] = curl_exec($ch);
    $result['INF'] = curl_getinfo($ch);
    $result['ERR'] = curl_error($ch);
    curl_close($ch);
    return $result['EXE'];
}

function show_files() {
    $dir = "files";
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == "." || $file == ".." || substr($file, 0, 1) == ".") continue;
                $directory[$file] = "$dir/$file";
            }
        }
    }
    echo json_encode($directory);
}

function show_file($file) {
    $lines = file("$file");
    $c = 0;
    foreach($lines as $k=>$v) {
        $result[$c++] = explode(",", trim($v));
    }
    echo json_encode($result);
}
function import_from_csv($name) {
    print "<ul>"; 
    foreach ($_POST as $key => $value) {
        print "<li>".$key.": ".$value."</li>"; 
        if (is_array($value)) {
            $subarray = $value; 
            print "<ul>"; 
            foreach ($subarray as $key => $value) {
                print "<li>".$key.": ".$value."</li>";
            } 
            print "</ul>";
        } 
        print "</li>";
    } 
    print "</ul>";
    print "<ul>"; 
    foreach ($_FILES as $k => $v) {
        print "<li>$k: $v</li>"; 
        print "<ul>"; 
        foreach ($v as $key => $value) {
            print "<li>$key: $value</li>";
        } 
        print "</ul>";
    } 
    print "</ul>";

    $file = file("uploads/" . $name);
    $x = 0; $y = 0;
    foreach ($file as $line) {
        $line = explode(",", $line);
        foreach ($line as $i) {
            $table[$x][$y] = $i;
            $y++;
        }
        $x++; $y = 0;
    }
    unlink("uploads/".$name);
    return $table;
}


function export_to_csv($table, $format) {
    $filename = 'result'.$format;
    $file = fopen("downloads/".$filename, 'w');
    for ($x = 0; $x < count($table); $x++) {
        $result = "";
        for ($y = 0; $y < count($table[$x]); $y++) {
            $result .= $table[$x][$y].', ';
        }
        $result = substr($result, 0, strlen($result)-2);
        if ($x == 0) {
            $result .= "\r\n";
        }
        fwrite($file, $result);
    }
    return $filename;
}


function download($file_name) {
    // make sure it's a file before doing anything!
    if(is_file($file_name)) {
        /*
            Do any processing you'd like here:
            1. Increment a counter
            2. Do something with the DB
            3. Check user permissions
            4. Anything you want!
        */
        // required for IE
        if(ini_get('zlib.output_compression')) { ini_set('zlib.output_compression', 'Off'); }
        // get the file mime type using the file extension
        switch(strtolower(substr(strrchr($file_name, '.'), 1))) {
            case 'pdf': $mime = 'application/pdf'; break;
            case 'zip': $mime = 'application/zip'; break;
            case 'jpeg':
            case 'jpg': $mime = 'image/jpg'; break;
            default: $mime = 'application/force-download';
        }
        header('Pragma: public');   // required
        header('Expires: 0');       // no cache
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($file_name)).' GMT');
        header('Cache-Control: private', false);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($file_name));    // provide file size
        header('Connection: close');
        readfile($file_name);       // push it out
        exit();
    }
}

function compress($type) {
    if ($type == "bz") {
        $filename = "/tmp/testfile.bz2";
        $str = "This is a test string.\n";
        // open file for writing
        $bz = bzopen($filename, "w");
        // write string to file
        bzwrite($bz, $str);
        // close file
        bzclose($bz);
        // open file for reading
        $bz = bzopen($filename, "r");
        // read 10 characters
        echo bzread($bz, 10);
        // output until end of the file (or the next 1024 char) and close it.
        echo bzread($bz);
        bzclose($bz);
    }
    if ($type == "lzf") {
        $lzfc = lzf_compress($str);
        $lzfu = lzf_uncompress($lzfc);
    }
}

function archivebackup($archiveFile, $file, &$errMsg) {
    $ziph = new ZipArchive();
    if(file_exists($archiveFile)) {
        if($ziph->open($archiveFile, ZIPARCHIVE::CHECKCONS) !== TRUE) {
            $errMsg = "Unable to Open $archiveFile";
            return 1;
        }
    }
    else {
        if($ziph->open($archiveFile, ZIPARCHIVE::CM_PKWARE_IMPLODE) !== TRUE) {
            $errMsg = "Could not Create $archiveFile";
            return 1;
        }
    }
    if(!$ziph->addFile($file)) {
        $errMsg = "error archiving $file in $archiveFile";
        return 2;
    }
    $ziph->close();
    return 0;
}

function zipper() {
    $zip = new ZipArchive();
    $filename = "./test112.zip";
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    $zip->addFromString("testfilephp.txt" . time(), "#1 This is a test string added as testfilephp.txt.\n");
    $zip->addFromString("testfilephp2.txt" . time(), "#2 This is a test string added as testfilephp2.txt.\n");
    $zip->addFile($thisdir . "/too.php","/testfromfile.php");
    echo "numfiles: " . $zip->numFiles . "\n";
    echo "status:" . $zip->status . "\n";
    $zip->close();
    $za = new ZipArchive();
    $za->open('test_with_comment.zip');
    print_r($za);
    var_dump($za);
    echo "numFiles: " . $za->numFiles . "\n";
    echo "status: " . $za->status  . "\n";
    echo "statusSys: " . $za->statusSys . "\n";
    echo "filename: " . $za->filename . "\n";
    echo "comment: " . $za->comment . "\n";
    for ($i = 0; $i < $za->numFiles;$i++) {
        echo "index: $i\n";
        print_r($za->statIndex($i));
    }
    echo "numFile:" . $za->numFiles . "\n";
}

function xml_reader() {
    $reader = new XMLReader();
    $reader->open('zip://' . dirname(__FILE__) . '/test.odt#meta.xml');
    $odt_meta = array();
    while ($reader->read()) {
        if ($reader->nodeType == XMLREADER::ELEMENT) {
            $elm = $reader->name;
        } 
        else {
            if ($reader->nodeType == XMLREADER::END_ELEMENT && $reader->name == 'office:meta') {
                break;
            }
            if (!trim($reader->value)) {
                continue;
            }
            $odt_meta[$elm] = $reader->value;
        }
    }
    print_r($odt_meta);
}




function fastexp($b,$m,$n) {
    $t = 1;
    while ($n > 0) {
        if ($m % 2) {$t = ($t * $b) % $n;} // Do this if $m is odd.
        $n = floor($m / 2);
        $b = ($b * $b) % $n; // Increase the power of $b by a factor of 2.
    }
    return $t;
}
function is_prime($number) {
    if ($number < 2) /* We don't want zero or one showing up as prime */ {
        return FALSE;
    }
    for ($i = 2; $i <= ($number / 2); $i++) {
        if($number % $i == 0) /* Modulus operator, very useful */ {
            return FALSE;
        }
    }
    return TRUE;
}
// function generate_prime() {
//     $number = 0;
//     while (!$this->IsPrime($number)) /* Keep going till we get a prime number */ {
//         $number = rand();
//     }
//     echo $number."\n";
//     return $number;
// }
function alpha_iterate() {
    $a = str_split("abcdefghijklmnopqrstuvwxyz");
    $len = count($a);
    $list = array();

    for($i = 1; $i < (1 << $len); $i++) {
        $c = '';
        for($j = 0; $j < $len; $j++) {
            if($i & (1 << $j)) {
                $c .= $a[$j];
            }
            echo $c.".it\n";
        }
        sort($list);
        return $list;
    }
}
function alpha_generator($input, &$output, $current = "") {
    static $lookup = array(
        1 => "1",	2 => "abc", 3 => "def",
        4 => "ghi", 5 => "jkl", 6 => "mno",
        7 => "pqrs", 8 => "tuv", 9 => "wxyz",
        0 => "0"
        );
    $digit = substr($input, 0, 1);			// e.g. "4"
    $other = substr($input, 1);				// e.g. "3556"
    $chars = str_split($lookup[$digit], 1); // e.g. "ghi"
    foreach ($chars as $char) {				// e.g. g, h, i
        if ($other === false) {				// base case
            $output[] = $current . $char;
        } 
        else {									// recursive case
            alphaGenerator($other, $output, $current . $char);
        }
    }
    // $output = array();
    // alphaGenerator("43556", $output);
    // var_dump($output);
}
function generatePasswd($numAlpha=6,$numNonAlpha=2) {
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';
    return str_shuffle(substr(str_shuffle($listAlpha), 0,$numAlpha) . substr(str_shuffle($listNonAlpha), 0,$numNonAlpha));
}
function hex2bin($h) {
    if (!is_string($h)) {
        $h = (string)$h;
    }
    $r = '';
    for ($a = 0; $a < strlen($h); $a += 2) {
        $r .= chr(hexdec($h{$a} . $h{($a + 1)}));
    }
    return $r;
}
function convert_number_to_words($number) {
    $hyphen		= '-';
    $conjunction = ' and ';
    $separator	= ', ';
    $negative	= 'negative ';
    $decimal	= ' point ';
    $dictionary = array(
        0					=> 'zero',
        1					=> 'one',
        2					=> 'two',
        3					=> 'three',
        4					=> 'four',
        5					=> 'five',
        6					=> 'six',
        7					=> 'seven',
        8					=> 'eight',
        9					=> 'nine',
        10					=> 'ten',
        11					=> 'eleven',
        12					=> 'twelve',
        13					=> 'thirteen',
        14					=> 'fourteen',
        15					=> 'fifteen',
        16					=> 'sixteen',
        17					=> 'seventeen',
        18					=> 'eighteen',
        19					=> 'nineteen',
        20					=> 'twenty',
        30					=> 'thirty',
        40					=> 'fourty',
        50					=> 'fifty',
        60					=> 'sixty',
        70					=> 'seventy',
        80					=> 'eighty',
        90					=> 'ninety',
        100					=> 'hundred',
        1000				=> 'thousand',
        1000000				=> 'million',
        1000000000			=> 'billion',
        1000000000000		=> 'trillion',
        1000000000000000 	=> 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error('convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING);
        return false;
    }

    if ($number < 0) {
        return $negative . convert_number_to_words(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
        break;
        case $number < 100:
            $tens	= ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
        break;
        case $number < 1000:
            $hundreds = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . convert_number_to_words($remainder);
            }
        break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
        break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}
function convertNumber($num) {
    list($num, $dec) = explode(".", $num);

    $output = "";

    if($num{0} == "-") {
        $output = "negative ";
        $num = ltrim($num, "-");
    }
    else if($num{0} == "+") {
        $output = "positive ";
        $num = ltrim($num, "+");
    }

    if($num{0} == "0") {
        $output .= "zero";
    }
    else {
        $num = str_pad($num, 36, "0", STR_PAD_LEFT);
        $group = rtrim(chunk_split($num, 3, " "), " ");
        $groups = explode(" ", $group);

        $groups2 = array();
        foreach($groups as $g) $groups2[] = convertThreeDigit($g{0}, $g{1}, $g{2});

        for($z = 0; $z < count($groups2); $z++) {
            if($groups2[$z] != "") {
                $output .= $groups2[$z].convertGroup(11 - $z).($z < 11 && !array_search('', array_slice($groups2, $z + 1, -1)) && $groups2[11] != '' && $groups[11]{0} == '0' ? " and " : ", ");
            }
        }

        $output = rtrim($output, ", ");
    }

    if($dec > 0) {
        $output .= " point";
        for($i = 0; $i < strlen($dec); $i++) $output .= " ".convertDigit($dec{$i});
    }

    return $output;
}
function convertGroup($index) {
    switch($index) {
        case 11: return " decillion";
        case 10: return " nonillion";
        case 9: return " octillion";
        case 8: return " septillion";
        case 7: return " sextillion";
        case 6: return " quintrillion";
        case 5: return " quadrillion";
        case 4: return " trillion";
        case 3: return " billion";
        case 2: return " million";
        case 1: return " thousand";
        case 0: return "";
    }
}
function convertThreeDigit($dig1, $dig2, $dig3) {
    $output = "";

    if($dig1 == "0" && $dig2 == "0" && $dig3 == "0") return "";

    if($dig1 != "0") {
        $output .= convertDigit($dig1)." hundred";
        if($dig2 != "0" || $dig3 != "0") $output .= " and ";
    }

    if($dig2 != "0") $output .= convertTwoDigit($dig2, $dig3);
    else if($dig3 != "0") $output .= convertDigit($dig3);

    return $output;
}
function convertTwoDigit($dig1, $dig2) {
    if($dig2 == "0") {
        switch($dig1) {
            case "1": return "ten";
            case "2": return "twenty";
            case "3": return "thirty";
            case "4": return "forty";
            case "5": return "fifty";
            case "6": return "sixty";
            case "7": return "seventy";
            case "8": return "eighty";
            case "9": return "ninety";
        }
    }
    else if($dig1 == "1") {
        switch($dig2) {
            case "1": return "eleven";
            case "2": return "twelve";
            case "3": return "thirteen";
            case "4": return "fourteen";
            case "5": return "fifteen";
            case "6": return "sixteen";
            case "7": return "seventeen";
            case "8": return "eighteen";
            case "9": return "nineteen";
        }
    }
    else {
        $temp = convertDigit($dig2);
        switch($dig1) {
            case "2": return "twenty-$temp";
            case "3": return "thirty-$temp";
            case "4": return "forty-$temp";
            case "5": return "fifty-$temp";
            case "6": return "sixty-$temp";
            case "7": return "seventy-$temp";
            case "8": return "eighty-$temp";
            case "9": return "ninety-$temp";
        }
    }
}
function convertDigit($digit) {
    switch($digit) {
        case "0": return "zero";
        case "1": return "one";
        case "2": return "two";
        case "3": return "three";
        case "4": return "four";
        case "5": return "five";
        case "6": return "six";
        case "7": return "seven";
        case "8": return "eight";
        case "9": return "nine";
    }
}


function pop3_login($host,$port,$user,$pass,$folder="INBOX",$ssl=false) {
    $ssl=($ssl==false)?"/novalidate-cert":"";
    return (imap_open("{"."$host:$port/pop3$ssl"."}$folder",$user,$pass));
}
function pop3_stat($connection) {
    $check = imap_mailboxmsginfo($connection);
    return ((array)$check);
}
function pop3_list($connection,$message="") {
    if ($message) {
        $range=$message;
    } 
    else {
        $MC = imap_check($connection);
        $range = "1:".$MC->Nmsgs;
    }
    $response = imap_fetch_overview($connection,$range);
    foreach ($response as $msg) $result[$msg->msgno]=(array)$msg;
        return $result;
}
function pop3_retr($connection,$message) {
    return(imap_fetchheader($connection,$message, FT_PREFETCHTEXT));
}
function pop3_dele($connection,$message) {
    return(imap_delete($connection,$message));
}
function mail_parse_headers($headers) {
    $headers=preg_replace('/\r\n\s+/m', '',$headers);
    $headers=trim($headers)."\r\n"; /* a hack for the preg_match_all in the next line */
    preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
    foreach ($matches[1] as $key =>$value) $result[$value] = $matches[2][$key];
    return($result);
}
function mail_mime_to_array($imap,$mid,$parse_headers=false) {
    $mail = imap_fetchstructure($imap,$mid);
    $mail = mail_get_parts($imap,$mid,$mail, 0);
    if ($parse_headers) $mail[0]["parsed"] = mail_parse_headers($mail[0]["data"]);
    return($mail);
}
function mail_get_parts($imap, $mid, $part, $prefix) {
    $attachments = array();
    $attachments[$prefix] = mail_decode_part($imap, $mid, $part, $prefix);
    if (isset($part -> parts)) {
        $prefix = ($prefix == "0") ? "" : "$prefix.";
        foreach ($part -> parts as $number => $subpart) {
            $attachments = array_merge($attachments, mail_get_parts($imap, $mid, $subpart, $prefix.($number + 1)));
        }
    }
    return $attachments;
}
function mail_decode_part($connection,$message_number,$part,$prefix) {
    $attachment = array();

    if($part->ifdparameters) {
        foreach($part->dparameters as $object) {
            $attachment[strtolower($object->attribute)]=$object->value;
            if(strtolower($object->attribute) == 'filename') {
                $attachment['is_attachment'] = true;
                $attachment['filename'] = $object->value;
            }
        }
    }

    if($part->ifparameters) {
        foreach($part->parameters as $object) {
            $attachment[strtolower($object->attribute)]=$object->value;
            if(strtolower($object->attribute) == 'name') {
                $attachment['is_attachment'] = true;
                $attachment['name'] = $object->value;
            }
        }
    }

    $attachment['data'] = imap_fetchbody($connection, $message_number, $prefix);
    if($part->encoding == 3) { 
        // 3 = BASE64
        $attachment['data'] = base64_decode($attachment['data']);
    }
    elseif($part->encoding == 4) { 
        // 4 = QUOTED-PRINTABLE
        $attachment['data'] = quoted_printable_decode($attachment['data']);
    }
    return($attachment);
}