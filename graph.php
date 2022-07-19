<?php
include "mysql.php";

$language = "West Germanic";
$language_id = get_language($language)[0]["id"];

function get_parents($languages) {
    if (!is_array($languages)) $languages = [$languages];
    $languages = implode("','", $languages);
    $query = "SELECT
    source_language.value AS source
    FROM transitions
    INNER JOIN languages AS source_language
    INNER JOIN languages AS target_language
    ON  transitions.source_language_id = source_language.id
    AND transitions.target_language_id = target_language.id
    WHERE target_language.value IN ('".$languages."')";
    $languages = get_query($query);
    if (!$languages) return [];
    $languages = array_map(function($language) {
        return $language["source"];
    }, $languages);
    return $languages;
}
function get_ancestors($language) {
    $count = [];
    $languages = [$language];
    while (empty($count[count($count)-2]) or $count[count($count)-2] !== $count[count($count)-1]) {
        $languages = implode("','", $languages);
        $query = "SELECT
    source_language.value AS source
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
        }
        $languages = array_unique($result);
        $count[] = count($languages);
    }
    return $languages;
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
function get_structure($language) {
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

printr(get_parents($language));
printr(get_ancestors($language));
printr(get_children($language));
printr(get_descendants($language));
// printr(get_family($language));
// printr(get_structure($language));
