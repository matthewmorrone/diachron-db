<?php
error_reporting(E_ERROR | E_PARSE);
echo "<pre>";
$html = file_get_contents("diachronica.html");

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
    $result = array_filter($matches, "is_string", ARRAY_FILTER_USE_KEY);
    if ($keepOriginal) $result[0] = $matches[0];
    if (count($result) === 0) return $matches;
    return $result;
}

function processRules($rules) {
    foreach ($rules as $ruleKey=>$rule) {
        if (!$rule["source"] || !$rule["target"]) continue;
        if ($rule["source"]) $rule["source"] = explode(" ", $rule["source"]);
        if ($rule["target"]) $rule["target"] = explode(" ", $rule["target"]);
        if (count($rule["source"]) === 1 and count($rule["target"]) === 1) {
            $rule["source"] = $rule["source"][0];
            $rule["target"] = $rule["target"][0];
            continue;
        }
        else {
            $rule["subrules"] = array_map(null, $rule["source"], $rule["target"]);
            foreach($rule["subrules"] as $subruleKey=>$subrule) {
                $source = $subrule[0];
                $target = $subrule[1];
                $subrule = [];
                $subrule["original"] = $rule["original"];
                $subrule["source"] = $source;
                $subrule["target"] = $target;
                if ($rule["environment"]) {
                    $subrule["environment"] = $rule["environment"];
                }
                $rule["subrules"][$subruleKey] = $subrule;
            }
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
        }
        $rules[$ruleKey] = $rule;
    }
    foreach ($rules as $ruleKey=>$rule) {
        $source = preg_match_named('/\{(?<s1>.+?),(?<s2>.+?)(,(?<s3>.+))?\}(?<opt>\(.+\))?/', $rule['source']);
        $target = preg_match_named('/\{(?<t1>.+?),(?<t2>.+?)(,(?<t3>.+))?\}(?<opt>\(.+\))?/', $rule['target']);
        if (count($source)) {
            $opt = $source["opt"];
            if (!$source["s3"]) unset($source["s3"]);
            unset($source["opt"]);
            foreach($source as $sKey=>$s) {
                if (strcmp($sKey, "opt") === 0) continue;
                $resultRule = [];
                $resultRule["original"] = $rule["original"];
                $resultRule["source"] = $s;
                if ($opt) {
                    $resultRule["source"] .= $opt;
                }
                $resultRule["target"] = $rule["target"];
                if ($rule["environment"]) {
                    $resultRule["environment"] = $rule["environment"];
                }
                $rule["subrules"][] = $resultRule;
            }
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
        }
        else if (count($target)) {
            $opt = $target["opt"];
            if (!$target["t3"]) unset($target["t3"]);
            unset($target["opt"]);
            foreach($target as $tKey=>$t) {
                if (strcmp($tKey, "opt") === 0) continue;
                $resultRule = [];
                $resultRule["original"] = $rule["original"];
                $resultRule["source"] = $rule["source"];
                $resultRule["target"] = $t;
                if ($opt) {
                    $resultRule["target"] .= $opt;
                }
                if ($rule["environment"]) {
                    $resultRule["environment"] = $rule["environment"];
                }
                $rule["subrules"][] = $resultRule;
            }
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
        }
        $rules[$ruleKey] = $rule;
    }
    foreach ($rules as $ruleKey=>$rule) {
        $sourceMatches = preg_match_named('/(?<both>.+)\((?<once>.+)\)/', $rule["source"]);
        $targetMatches = preg_match_named('/(?<both>.+)\((?<once>.+)\)/', $rule["target"]);
        if (count($sourceMatches) && count($targetMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $rule["original"];
            $subrule2["original"] = $rule["original"];

            $subrule1["source"] = $sourceMatches["both"];
            $subrule1["target"] = $targetMatches["both"];
            
            $subrule2["source"] = $sourceMatches["both"].$sourceMatches["once"];
            $subrule2["target"] = $targetMatches["both"].$targetMatches["once"];

            if ($rule["environment"]) {
                $subrule1["environment"] = $rule["environment"];
                $subrule2["environment"] = $rule["environment"];
            }
            $rule["subrules"][] = $subrule1;
            $rule["subrules"][] = $subrule2;
            unset($rule["original"]);
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
            $rules[$ruleKey] = $rule;
        }
        else if (count($sourceMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $rule["original"];
            $subrule2["original"] = $rule["original"];

            $subrule1["source"] = $sourceMatches["both"];
            $subrule1["target"] = $rule["target"];

            $subrule2["source"] = $sourceMatches["both"].$sourceMatches["once"];
            $subrule2["target"] = $rule["target"];

            if ($rule["environment"]) {
                $subrule1["environment"] = $rule["environment"];
                $subrule2["environment"] = $rule["environment"];
            }
            $rule["subrules"][] = $subrule1;
            $rule["subrules"][] = $subrule2;
            unset($rule["original"]);
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
            $rules[$ruleKey] = $rule;
        }
        else if (count($targetMatches)) {
            $subrule1 = [];
            $subrule2 = [];

            $subrule1["original"] = $rule["original"];
            $subrule2["original"] = $rule["original"];

            $subrule1["source"] = $rule["source"];
            $subrule1["target"] = $targetMatches["both"];
            $subrule2["source"] = $rule["source"];
            $subrule2["target"] = $targetMatches["both"].$targetMatches["once"];
            if ($rule["environment"]) {
                $subrule1["environment"] = $rule["environment"];
                $subrule2["environment"] = $rule["environment"];
            }
            $rule["subrules"][] = $subrule1;
            $rule["subrules"][] = $subrule2;
            unset($rule["original"]);
            unset($rule["source"]);
            unset($rule["target"]);
            unset($rule["environment"]);
            $rules[$ruleKey] = $rule;
        }
    }
    $newRules = [];
    foreach ($rules as $ruleKey=>$rule) {
        if ($rule["subrules"]) {
            foreach($rule["subrules"] as $subrule) {
                $newRules[] = $subrule;
            }
        }
        else {
            $newRules[] = $rule;
        }
    }
    $rules = $newRules;
    return $rules;
}

foreach ($sections as $key=>$section) {
    $transition = $section->getElementsByTagName('h2')->item(0)->textContent;

    preg_match('/(?<index>[\d\.]+)\s*(?<source>[\p{L} -]+?) to (?<target>[\p{L} -]+)/u', $transition, $matches);
    if (!count($matches)) continue;
    $result = [
        "key" => $key,
        "source_language" => $matches["source"],
        "target_language" => $matches["target"],
    ];
    if (!count($result)) continue;
    $citations = $xpath->query("descendant::*[contains(@class, 'citation')]", $section);
    foreach ($citations as $citation) {
        $result["citation"] = $citation->nodeValue;
    }

    $rules = $xpath->query("descendant::*[contains(@class, 'rule')]", $section);
    $resultRules = [];
    foreach ($rules as $ruleKey=>$rule) {
        $resultRule = [];
        $resultRule["original"] = $rule->nodeValue;
        $attempt3 = preg_match_named('/(?<source>.+) → (?<target>.+) → (?<target2>.+) \/ (?<environment>.+)/', $rule->nodeValue);
        if (count($attempt3)) {
            $resultRule = [
                "subrules" => [[
                    "original" => $rule->nodeValue,
                    "source" => $attempt3["source"],
                    "target" => $attempt3["target"],
                    "environment" => $attempt3["environment"],
                ], [
                    "original" => $rule->nodeValue,
                    "source" => $attempt3["target"],
                    "target" => $attempt3["target2"],
                    "environment" => $attempt3["environment"],
                ]]
            ];
            $resultRules[] = $resultRule;
            continue;
        }
        
        $attempt7 = preg_match_named('/(?<source>.+) → (?<target>.+) → (?<target2>.+) → (?<target3>.+)/', $rule->nodeValue);
        if (count($attempt7)) {
            $resultRule = [
                "subrules" => [[
                    "original" => $rule->nodeValue,
                    "source" => $attempt2["source"],
                    "target" => $attempt2["target"],
                ], [
                    "original" => $rule->nodeValue,
                    "source" => $attempt2["target"],
                    "target" => $attempt2["target2"],
                ], [
                    "original" => $rule->nodeValue,
                    "source" => $attempt2["target2"],
                    "target" => $attempt2["target3"],
                ]]
            ];
            $resultRules[] = $resultRule;
            continue;
        }
        $attempt2 = preg_match_named('/(?<source>.+) → (?<target>.+) → (?<target2>.+)/', $rule->nodeValue);
        if (count($attempt2)) {
            $resultRule = [
                "subrules" => [[
                    "original" => $rule->nodeValue,
                    "source" => $attempt2["source"],
                    "target" => $attempt2["target"],
                ], [
                    "original" => $rule->nodeValue,
                    "source" => $attempt2["target"],
                    "target" => $attempt2["target2"],
                ]]
            ];
            $resultRules[] = $resultRule;
            continue;
        }
        
        $attempt6 = preg_match_named('/(?<source>.+) → (?<target>.+) \/ (?<environment>.+) (?<note>[\("].+[\)"])?$/', $rule->nodeValue);
        if (count($attempt6)) {
            $resultRule["source"] = $attempt6["source"];
            $resultRule["target"] = $attempt6["target"];
            $resultRule["environment"] = $attempt6["environment"];
            if ($attempt6["note"]) {
                $resultRule["note"] = $attempt6["note"];
            }
            $resultRules[] = $resultRule;
            continue;
        }
        $attempt1 = preg_match_named('/(?<source>.+) → (?<target>.+) \/ (?<environment>.+)/', $rule->nodeValue);
        if (count($attempt1)) {
            $resultRule["source"] = $attempt1["source"];
            $resultRule["target"] = $attempt1["target"];
            $resultRule["environment"] = $attempt1["environment"];
            $resultRules[] = $resultRule;
            continue;
        }
        
        $attempt4 = preg_match_named('/(?<source>.+) → (?<target>.+) (?<note>[\("].+[\)"])?$/', $rule->nodeValue);
        if (count($attempt4)) {
            $resultRule["source"] = $attempt4["source"];
            $resultRule["target"] = $attempt4["target"];
            if ($attempt4["note"]) {
                $resultRule["note"] = $attempt4["note"];
            }
            $resultRules[] = $resultRule;
            continue;
        }
        $attempt5 = preg_match_named('/(?<source>.+) → (?<target>.+)/', $rule->nodeValue);
        if (count($attempt5)) {
            $resultRule["source"] = $attempt5["source"];
            $resultRule["target"] = $attempt5["target"];
            $resultRules[] = $resultRule;
            continue;
        }
    }
    $result["rules"] = $resultRules;

    
    $result["rules"] = processRules($result["rules"]);
    $result["rules"] = processRules($result["rules"]);
    $result["rules"] = processRules($result["rules"]);

    // foreach($result["rules"] as &$rule) { unset($rule["original"]); }
    
    if ($key < 100) continue;
    // if ($key > 100) break;
    $results[] = $result;
    /*
    // r → {r,lː} / V_
    // does this mean r became l: only sometimes? I feel like there should be environment explanations here
    */
}
/* 
$chunks = array_chunk($results, 50);
foreach($chunks as $chunkey=>$chunk) {
    file_put_contents("$chunkey.html", json_encode($chunk));
} 
*/

/* 
$issues = [];
foreach($results as $result) {
    $rules = $result["rules"];
    foreach($rules as $rule) {
        if (!$rule['source'] or !$rule['target']) {
            $issues[] = $rule["original"];
        }
    }
}
$issues = array_unique($issues);
printr(count($issues));
foreach($issues as $issue) {
    printr($issue);
}
*/

function printr() {
    foreach (func_get_args() as $i) {
        if (is_array($i) || is_object($i)) {var_export($i);}
        else {print_r($i);}
        echo "\n";
    }
}
// printr($results);
echo json_encode($results);
?>