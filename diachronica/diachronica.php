<?php
include "../mysql.php";

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

function replace_tags($text) {
    $repl = ["<sub>0</sub>", "<sub>1</sub>", "<sub>2</sub>", "<sub>3</sub>", "<sub>4</sub>", "<sub>n</sub>", "<sub>s</sub>", "<sub>u</sub>", "<sub>x</sub>", "<sup>1</sup>", "<sup>2</sup>", "<sup>3</sup>", "<sup>4</sup>", "<sup>5</sup>", "<sup>n</sup>"];
    $with = ["₀", "₁", "₂", "₃", "₄", "ₙ", "ₛ", "ᵤ", "ₓ", "¹", "²", "³", "⁴", "⁵", "ⁿ"];
    return str_replace($repl, $with, $text);
}
function replace_quotes($text) {
    $repl = ["“", "”"];
    $with = ["", ""];
    return str_replace($repl, $with, $text);
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

foreach ($sections as $key=>$section) {
    if ($key < 6) continue;
    $transition = $section->getElementsByTagName('h2')->item(0)->textContent;
    $transition = fix_encoding($transition);

    $chars = "\p{L} -‘’“”";

    $sectionMatches = preg_match_named("/(?<index>[\d\.]+)\s*(?<family>[$chars]+)/u", $transition, true);
    if (!str_contains($sectionMatches["index"], ".")) {
        $family = $sectionMatches["family"];
    }
    
    $transitionMatches = preg_match_named("/(?<index>[\d\.]+)\s*(?<source>[$chars]+?) to (?<target>[$chars]+)/u", $transition, true);
    if (!count($transitionMatches)) continue;
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
        // $resultRule = [];
        // $resultRule["original"] = $rule->nodeValue;
        $rule = $rule->nodeValue;
        $rule = replace_tags($rule);
        $rule = replace_quotes($rule);

        $ruleMatches = preg_match_named('/(?<source>.+) → (?<target>.+) (?<note>\(.+\))/', $rule); // (?<environment> \/ .+)?(?<note>\(.+\))?
        
        if ($ruleMatches["note"]) {
            printr($rule);
            printr($ruleMatches); 
        }

    }




    // $result["rules"] = processRules($result["rules"]);
    // $result["rules"] = processRules($result["rules"]);
    // $result["rules"] = processRules($result["rules"]);

    // foreach($result["rules"] as &$rule) { unset($rule["original"]); }
    
    // if ($key < 100) continue;
    // if ($key > 1) break;
    $results[] = $result;
}
echo json_encode($results);


function database($results) {
    foreach($results as $result) {
        $source_language_id = get_or_add_language($result["source_language"]);
        $target_language_id = get_or_add_language($result["target_language"]);
        $citation = $result["citation"];
        get_or_add_transition($source_language_id, $target_language_id, $citation); 
    
        foreach($result["rules"] as $rule) {
            $data = [
                "source_segment" => $rule["source"],
                "target_segment" => $rule["target"],
                "source_language" => $result["source_language"],
                "target_language" => $result["target_language"],
                "notes" => $rule["environment"]." ".$rule["notes"],
            ];
            try {
                echo add_pair($data, true);
            }
            catch(Exception $e) {
                printr($e);
            }
        }
    }
}


?>