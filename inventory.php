<?php

include "mysql.php";

$languages = get_languages();
$languages = [["id"=>5, "value"=>"Bench"]];

function array_zip($arr) {
    $out = array();
    foreach($arr as $key => $subarr) {
        foreach($subarr as $subkey => $subvalue) {
            $out[$subkey][$key] = $subvalue;
        }
    }
    return $out;
}
function insertArrayAtPosition($array, $insert, $position) {
    return array_merge(array_slice($array, 0, $position+1, TRUE), [$insert], array_slice($array, $position+1, NULL, TRUE));
}

foreach($languages as $language) {
    $language = $language["value"];
    $ancestors = get_ancestor_tree($language, true);
    if (count($ancestors) < 2) continue;

    $origin = $ancestors[0][0];
    $origin_id = get_language($origin)[0]["id"];
    $inventory = get_inventory($origin_id);
    // printr($origin, $origin_id, implode(",", $inventory));
    foreach($ancestors as $ancestor) {
        $pairs = get_pairs_by_transition_id($ancestor["transition"]);
        $ancestor["oldInventory"] = implode(",", $inventory);
        foreach($pairs as $pair) {
            
            $index = array_search($pair["source_segment"], $inventory);
            if ($index) {
                echo $pair["source_segment"] . " → " . $pair["target_segment"] . " / " . $pair["environment"]."<br>";
                echo implode(",", $inventory)."<br>";
                $inventory = insertArrayAtPosition($inventory, $pair["target_segment"], $index);
                echo implode(",", $inventory)."<br>";
            }
        }
        foreach($pairs as $pair) {
            
            $index = array_search($pair["source_segment"], $inventory);
            if ($index) {
                echo $pair["source_segment"] . " → " . $pair["target_segment"] . " / " . $pair["environment"]."<br>";
                echo implode(",", $inventory)."<br>";
                if (!$pair["environment"]) {
                    unset($inventory[$index]);
                }
                echo implode(",", $inventory)."<br>";
            }
        }
        $inventory = array_filter($inventory, function($segment) {
            return strcmp($segment, "∅") !== 0;
        });
        $ancestor["newInventory"] = implode(",", $inventory);
        printr($ancestor);
    }
}
