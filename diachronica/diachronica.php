<?php
include "../mysql.php";
set_time_limit(180);

error_reporting(E_ERROR | E_PARSE);
echo "<pre>";

$html = mb_convert_encoding(
    file_get_contents('diachronica.html'),
    // file_get_contents('https://chridd.nfshost.com/diachronica/all'),
    "HTML-ENTITIES",
    "UTF-8"
);

function fix_encoding($text) {
    $repl = ["É¨", "Ç", "Ê", "Ç", "Ç", "Å", "É£", "Ã°", "É̣", "ı̄"];
    $with = ["ɨ",  "ǁ",  "ʼ",  "ǀ",  "ǂ",  "ŋ",  "γ",  "δ", "ɔ", "ī"];
    return str_replace($repl, $with, $text);
}
function preg_replace_all($find, $repl, $text) {
    while (preg_match($find, $text)) {
        $text = preg_replace($find, $repl, $text);
    }
    return $text;
}
function hot_fixes($text) {
    $text = preg_replace_all("/\[(\-) (.+)\]/", "[$1$2]", $text);
    $text = preg_replace_all("/\[(\+) (.+)\]/", "[$1$2]", $text);
    $text = preg_replace_all("/\((.+)→(.+)\)/", "($1->$2)", $text);
    $text = preg_replace_all("/“\(/", "(", $text);
    $text = preg_replace_all("/\)”/", ")", $text);
    return $text;
}

$doc = new DOMDocument();
$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
$doc->preserveWhiteSpace = false;

foreach ($doc->childNodes as $item)
    if ($item->nodeType == XML_PI_NODE)
        $doc->removeChild($item);
$doc->encoding = 'UTF-8';

$xpath = new DomXPath($doc);

$sections = $doc->getElementsByTagName('section');

function preg_match_named($pattern, $subject, $keepOriginal=false) {
    preg_match($pattern, $subject, $matches);
    if (count($matches) === 0) return $matches;
    $result = [];
    if ($keepOriginal) $result["original"] = $matches[0];
    $result += array_filter($matches, "is_string", ARRAY_FILTER_USE_KEY);
    if (count($result) === 0) return $matches;
    return $result;
}

function process_rules($resultRules) {
    foreach ($resultRules as $ruleKey=>$resultRule) {
        if (!$resultRule["source"] || !$resultRule["target"]) continue;
        if ($resultRule["source"]) $resultRule["source"] = explode(" ", $resultRule["source"]);
        if ($resultRule["target"]) $resultRule["target"] = explode(" ", $resultRule["target"]);
        if (count($resultRule["source"]) === 1 and count($resultRule["target"]) === 1) {
            $resultRule["source"] = $resultRule["source"][0];
            $resultRule["target"] = $resultRule["target"][0];
            continue;
        }
        else {
            $resultRule["subrules"] = array_map(null, $resultRule["source"], $resultRule["target"]);
            foreach($resultRule["subrules"] as $resultSubruleKey=>$resultSubrule) {
                $source = $resultSubrule[0];
                $target = $resultSubrule[1];
                $resultSubrule = [];
                $resultSubrule["original"] = $resultRule["original"];
                $resultSubrule["source"] = $source;
                $resultSubrule["target"] = $target;
                if ($resultRule["environment"]) {
                    $resultSubrule["environment"] = $resultRule["environment"];
                }
                if ($resultRule["notes"]) {
                    $resultSubrule["notes"] = $resultRule["notes"];
                }
                $resultRule["subrules"][$resultSubruleKey] = $resultSubrule;
            }
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
        }
        $resultRules[$ruleKey] = $resultRule;
    }
    foreach ($resultRules as $ruleKey=>$resultRule) {
        $source = preg_match_named('/\{(?<s1>.+?),(?<s2>.+?)(,(?<s3>.+))?(,(?<s4>.+))?(,(?<s5>.+))?(,(?<s6>.+))?\}(?<opt>\(.+\))?/', $resultRule['source']);
        $target = preg_match_named('/\{(?<t1>.+?),(?<t2>.+?)(,(?<t3>.+))?(,(?<t4>.+))?(,(?<t5>.+))?(,(?<t6>.+))?\}(?<opt>\(.+\))?/', $resultRule['target']);
        if (count($source)) {
            $opt = $source["opt"];
            if (!$source["s3"]) unset($source["s3"]);
            if (!$source["s4"]) unset($source["s4"]);
            if (!$source["s5"]) unset($source["s5"]);
            if (!$source["s6"]) unset($source["s6"]);
            unset($source["opt"]);
            foreach($source as $sKey=>$s) {
                if (strcmp($sKey, "opt") === 0) continue;
                $resultSubrule = [];
                $resultSubrule["original"] = $resultRule["original"];
                $resultSubrule["source"] = $s;
                if ($opt) {
                    $resultSubrule["source"] .= $opt;
                }
                $resultSubrule["target"] = $resultRule["target"];
                if ($resultRule["environment"]) {
                    $resultSubrule["environment"] = $resultRule["environment"];
                }
                if ($resultRule["notes"]) {
                    $resultSubrule["notes"] = $resultRule["notes"];
                }
                $resultRule["subrules"][] = $resultSubrule;
            }
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
        }
        else if (count($target)) {
            $opt = $target["opt"];
            if (!$target["t3"]) unset($target["t3"]);
            if (!$target["t4"]) unset($target["t4"]);
            if (!$target["t5"]) unset($target["t5"]);
            if (!$target["t6"]) unset($target["t6"]);
            unset($target["opt"]);
            foreach($target as $tKey=>$t) {
                if (strcmp($tKey, "opt") === 0) continue;
                $resultSubrule = [];
                $resultSubrule["original"] = $resultRule["original"];
                $resultSubrule["source"] = $resultRule["source"];
                $resultSubrule["target"] = $t;
                if ($opt) {
                    $resultSubrule["target"] .= $opt;
                }
                if ($resultRule["environment"]) {
                    $resultSubrule["environment"] = $resultRule["environment"];
                }
                if ($resultRule["notes"]) {
                    $resultSubrule["notes"] = $resultRule["notes"];
                }
                $resultRule["subrules"][] = $resultSubrule;
            }
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
        }
        $resultRules[$ruleKey] = $resultRule;
    }
    foreach ($resultRules as $resultRuleKey=>$resultRule) {
        $sourceMatches = preg_match_named('/(?<both>.+)\((?<once>.+)\)/', $resultRule["source"]);
        $targetMatches = preg_match_named('/(?<both>.+)\((?<once>.+)\)/', $resultRule["target"]);
        if (count($sourceMatches) && count($targetMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $resultRule["original"];
            $subrule2["original"] = $resultRule["original"];

            $subrule1["source"] = $sourceMatches["both"];
            $subrule1["target"] = $targetMatches["both"];
            
            $subrule2["source"] = $sourceMatches["both"].$sourceMatches["once"];
            $subrule2["target"] = $targetMatches["both"].$targetMatches["once"];

            if ($resultRule["environment"]) {
                $subrule1["environment"] = $resultRule["environment"];
                $subrule2["environment"] = $resultRule["environment"];
            }
            if ($resultRule["notes"]) {
                $subrule1["notes"] = $resultRule["notes"];
                $subrule2["notes"] = $resultRule["notes"];
            }
            $resultRule["subrules"][] = $subrule1;
            $resultRule["subrules"][] = $subrule2;
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
            $resultRules[$resultRuleKey] = $resultRule;
        }
        else if (count($sourceMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $resultRule["original"];
            $subrule2["original"] = $resultRule["original"];

            $subrule1["source"] = $sourceMatches["both"];
            $subrule1["target"] = $resultRule["target"];

            $subrule2["source"] = $sourceMatches["both"].$sourceMatches["once"];
            $subrule2["target"] = $resultRule["target"];

            if ($resultRule["environment"]) {
                $subrule1["environment"] = $resultRule["environment"];
                $subrule2["environment"] = $resultRule["environment"];
            }
            if ($resultRule["notes"]) {
                $subrule1["notes"] = $resultRule["notes"];
                $subrule2["notes"] = $resultRule["notes"];
            }
            $resultRule["subrules"][] = $subrule1;
            $resultRule["subrules"][] = $subrule2;
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
            $resultRules[$resultRuleKey] = $resultRule;
        }
        else if (count($targetMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $resultRule["original"];
            $subrule2["original"] = $resultRule["original"];

            $subrule1["source"] = $resultRule["source"];
            $subrule1["target"] = $targetMatches["both"];
            $subrule2["source"] = $resultRule["source"];
            $subrule2["target"] = $targetMatches["both"].$targetMatches["once"];
            if ($resultRule["environment"]) {
                $subrule1["environment"] = $resultRule["environment"];
                $subrule2["environment"] = $resultRule["environment"];
            }
            if ($resultRule["notes"]) {
                $subrule1["notes"] = $resultRule["notes"];
                $subrule2["notes"] = $resultRule["notes"];
            }
            $resultRule["subrules"][] = $subrule1;
            $resultRule["subrules"][] = $subrule2;
            unset($resultRule["original"]);
            unset($resultRule["source"]);
            unset($resultRule["target"]);
            unset($resultRule["environment"]);
            unset($resultRule["notes"]);
            $resultRules[$resultRuleKey] = $resultRule;
        }
    }
    $newRules = [];
    foreach ($resultRules as $resultRuleKey=>$resultRule) {
        if ($resultRule["subrules"]) {
            foreach($resultRule["subrules"] as $subrule) {
                $newRules[] = $subrule;
            }
        }
        else {
            $newRules[] = $resultRule;
        }
    }
    $resultRules = $newRules;

    return $resultRules;
}
foreach ($sections as $key=>$section) {
    if ($key < 6) continue;
    $transition = $section->getElementsByTagName('h2')->item(0)->textContent;
    $transition = fix_encoding($transition);

    $chars = "\p{L} -‘’“”";

    $sectionMatches = preg_match_named("/(?<index>[\d\.]+)\s*(?<family>[$chars]+)/u", $transition, true);
    if (!str_contains($sectionMatches["index"], ".")) {
        $family = $sectionMatches["family"];
    }

    $segments = $section->getElementsByTagName('div');
    if (count($segments)) {
        $segmentData = "";
        foreach ($segments as $table){
            $segmentData .= $table->nodeValue." ";
        }
        $segmentData = explode(" ", $segmentData);
        $segmentData = array_filter($segmentData);
        $result = [
            "key" => $key,
            "family" => $family,
            "citation" => $section->getElementsByTagName('p')->item(0)->nodeValue,
            "segments" => $segmentData
        ];
        $results[] = $result;
        continue;
    }
    continue;

    $transitionMatches = preg_match_named("/(?<index>[\d\.]+)\s*(?<source>[$chars]+?) to (?<target>[$chars]+)/u", $transition, true);
    if (!count($transitionMatches)) {
        continue;
    }
    $result = [
        "key" => $key,
        "family" => $family,
        "original" => $transition,
        "index" => $transitionMatches["index"],
        "source_language" => $transitionMatches["source"],
        "target_language" => $transitionMatches["target"],
    ];
    if (!count($result)) continue;
    $citations = $xpath->query("descendant::*[contains(@class, 'citation')]", $section);
    foreach ($citations as $citation) {
        $result["citation"] = str_replace(array("\r", "\n"), '', $citation->nodeValue);
    }

    $rules = $xpath->query("descendant::*[contains(@class, 'rule')]", $section);
    $resultRules = [];
    foreach ($rules as $ruleKey=>$rule) {
        $rule = $rule->nodeValue;
        $rule = hot_fixes($rule);

        $environment = "(?<environment> \/ .+)";
        $notesParens = "(?<notes> \(.{2,}\))";
        $notesQuotes = "(?<notes> “.+”)";
        $sourceTargets = [
            "(?<source>.+) → (?<target>.+) → (?<target2>.+) → (?<target3>.+) → (?<target4>.+)",
            "(?<source>.+) → (?<target>.+) → (?<target2>.+) → (?<target3>.+)",
            "(?<source>.+) → (?<target>.+) → (?<target2>.+)",
            "(?<source>.+) → (?<target>.+)",
        ];
        foreach($sourceTargets as $sourceTarget) {
            $ruleMatches = preg_match_named("/$sourceTarget$environment$notesQuotes/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}
            $ruleMatches = preg_match_named("/$sourceTarget$environment$notesParens/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}

            $ruleMatches = preg_match_named("/$sourceTarget$environment/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}

            $ruleMatches = preg_match_named("/$sourceTarget$notesQuotes/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}
            $ruleMatches = preg_match_named("/$sourceTarget$notesParens/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}

            $ruleMatches = preg_match_named("/$sourceTarget/", $rule, true);
            if ($ruleMatches) {$resultRules[] = array_map("trim", $ruleMatches); continue 2;}
        }
    }
    foreach ($resultRules as $ruleKey=>$resultRule) {
        if (!$resultRule["target4"] and !$resultRule["target3"] and !$resultRule["target2"]) continue;
        if ($resultRule["target2"]) {
            $newResultRule = [
                "subrules" => [[
                    "original" => $resultRule["original"],
                    "source" => $resultRule["source"],
                    "target" => $resultRule["target"],
                ], [
                    "original" => $resultRule["original"],
                    "source" => $resultRule["target"],
                    "target" => $resultRule["target2"],
                ]]
            ];
        }
        if ($resultRule["target3"]) {
            $newResultRule["subrules"][] = [
                "original" => $resultRule["original"],
                "source" => $resultRule["target2"],
                "target" => $resultRule["target3"],
            ];
        }
        if ($resultRule["target4"]) {
            $newResultRule["subrules"][] = [
                "original" => $resultRule["original"],
                "source" => $resultRule["target3"],
                "target" => $resultRule["target4"],
            ];
        }
        if ($resultRule["environment"]) {
            foreach($newResultRule["subrules"] as $subruleKey=>$subRule) {
                $newResultRule["subrules"][$subruleKey]["environment"] = $resultRule["environment"];
            }
        }
        if ($resultRule["notes"]) {
            foreach($newResultRule["subrules"] as $subruleKey=>$subRule) {
                $newResultRule["subrules"][$subruleKey]["notes"] = $resultRule["notes"];
            }
        }
        $resultRules[$ruleKey] = $newResultRule;
    }

    $resultRules = process_rules($resultRules);
    $resultRules = process_rules($resultRules);
    $resultRules = process_rules($resultRules);
    $resultRules = process_rules($resultRules);
    $result["rules"] = $resultRules;

    // foreach($result["rules"] as &$rule) { unset($rule["original"]); }

    $results[] = $result;
}
echo json_encode($results);


function database($results) {

    reset_database();
    foreach($results as $result) {
        $source_language_id = get_or_add_language($result["source_language"]);
        $target_language_id = get_or_add_language($result["target_language"]);
        $citation = $result["citation"];
        get_or_add_transition($source_language_id, $target_language_id, $citation);
        printr(json_encode($result));
    }
    foreach($results as $result) {
        foreach($result["rules"] as $rule) {
            $data = [
                "source_segment" => $rule["source"],
                "target_segment" => $rule["target"],
                "source_language" => $result["source_language"],
                "target_language" => $result["target_language"],
                "environment" => $rule["environment"],
                "notes" => $rule["notes"],
            ];
            try {
                add_pair($data);
                printr(json_encode($data));
            }
            catch(Exception $e) {
                printr($e->getMessage());
            }
        }
    }
}
function database_cleanup() {
    $query = "UPDATE pairs SET notes = REGEXP_REPLACE(REGEXP_REPLACE(notes, '\\)$', ''), '^\\(', '') WHERE notes LIKE '(%)'";
    do_query($query);
    $query = "UPDATE pairs SET notes = REGEXP_REPLACE(REGEXP_REPLACE(notes, '”$', ''), '^“', '') WHERE notes LIKE '“%”'";
    do_query($query);
    $query = "UPDATE pairs SET environment = REGEXP_REPLACE(environment, '(.+)”(.+)', '\\1ˈ\\2') WHERE environment LIKE '%”V%'";
    do_query($query);
}


// database($results);
// database_cleanup();

set_time_limit(120);
?>