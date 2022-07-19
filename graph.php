<html>
<head>
<title>Graph | Diachron DB</title>
<meta charset='UTF-8'>
<link href='https://www.matthewmorrone.com/psi-fff.ico' rel='shortcut icon' type='image/x-icon'>
<link rel='shortcut icon' href='https://www.matthewmorrone.com/psi-white.ico' id='psi-white'>
<link rel='shortcut icon' href='https://www.matthewmorrone.com/psi-black.ico' id='psi-black'>
<?php
include "mysql.php";

$language = $_GET["language"];
echo $language;
$language_id = get_language($language)[0]["id"];

function get_parent($languages) {
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

// printr("get_parent($language): ".json_encode(get_parent($language))); echo "<br>";
// printr("get_structure(get_parent($language)): ".json_encode(get_structure(get_parent($language)))); echo "<br>";
// printr("get_ancestors($language): ".json_encode(get_ancestors($language))); echo "<br>";
// printr("get_structure(get_ancestors($language)): ".json_encode(get_structure(get_ancestors($language)))); echo "<br>";
// printr("get_children($language): ".json_encode(get_children($language))); echo "<br>";
// printr("get_structure(get_children($language)): ".json_encode(get_structure(get_children($language)))); echo "<br>";
// printr("get_descendants($language): ".json_encode(get_descendants($language))); echo "<br>";
// printr("get_structure(get_descendants($language)): ".json_encode(get_structure(get_descendants($language)))); echo "<br>";
// printr("get_family($language): ".json_encode(get_family($language))); echo "<br>";
// printr("get_structure($language): ".json_encode(get_structure($language))); echo "<br>";
// printr("get_structure_all($language): ".json_encode(get_structure_all($language))); echo "<br>";

$data = get_data(get_structure(get_descendants($language)));
?>
<style>
#cy {
    width: 100%;
    height: 100%;
    display: block;
    margin: auto;
}
</style>
</head>
<body>
<select id="layout">
    <option>random</option>
    <optgroup label="Geometric">
        <option>grid</option>
        <option>circle</option>
        <option>concentric</option>
        <option selected>avsdf</option>
    </optgroup>
    <optgroup label="Hierarchical">
        <option>dagre</option>
        <option>breadthfirst</option>
        <!-- <option>elk</option> -->
        <option>klay</option>
    </optgroup>
    <optgroup label="Force-directed">
        <option>fcose</option>
        <option>cose-bilkent</option>
        <option>cose</option>
        <option>cosep</option>
        <option>cola</option>
        <option>cise</option>
        <option>euler</option>
        <option>spread</option>
    </optgroup>
</select>
<div id='cy'></div>
<script>
let data = <?php echo json_encode($data); ?>;
</script>
<script src='https://matthewmorrone.com/icon.js'></script>
<script src='https://matthewmorrone.com/jquery.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.22.1/cytoscape.min.js"></script>
<script src="https://unpkg.com/layout-base/layout-base.js"></script>
<script src="https://unpkg.com/avsdf-base/avsdf-base.js"></script>
<script src="https://ivis-at-bilkent.github.io/cytoscape.js-avsdf/cytoscape-avsdf.js"></script>
<script src="https://cdn.rawgit.com/cpettitt/dagre/v0.7.4/dist/dagre.min.js"></script>
<script src="https://cdn.rawgit.com/cytoscape/cytoscape.js-dagre/1.5.0/cytoscape-dagre.js"></script>
<script src="https://cdn.jsdelivr.net/npm/elkjs@0.7.0/lib/elk.bundled.js"></script>
<script src="https://cytoscape.org/cytoscape.js-elk/cytoscape-elk.js"></script>
<script src="https://unpkg.com/klayjs@0.4.1/klay.js"></script>
<script src="https://cytoscape.org/cytoscape.js-klay/cytoscape-klay.js"></script>
<script src="https://unpkg.com/layout-base/layout-base.js"></script>
<script src="https://unpkg.com/cose-base/cose-base.js"></script>
<script src="https://unpkg.com/cytoscape-layout-utilities/cytoscape-layout-utilities.js"></script>
<script src="https://ivis-at-bilkent.github.io/cytoscape.js-fcose/cytoscape-fcose.js"></script>
<script src="https://cdn.rawgit.com/cytoscape/cytoscape.js-cose-bilkent/1.6.5/cytoscape-cose-bilkent.js"></script>
<script src="https://unpkg.com/webcola/WebCola/cola.min.js"></script>
<script src="https://cytoscape.org/cytoscape.js-cola/cytoscape-cola.js"></script>
<script src="https://unpkg.com/layout-base@1.0.2/layout-base.js"></script>
<script src="https://unpkg.com/avsdf-base/avsdf-base.js"></script>
<script src="https://unpkg.com/cose-base@1.0.3/cose-base.js"></script>
<script src="https://unpkg.com/cytoscape-graphml/cytoscape-graphml.js"></script>
<script src="https://raw.githack.com/iVis-at-Bilkent/cytoscape.js-layvo/unstable/cytoscape-layvo.js"></script>
<script src="https://ivis-at-bilkent.github.io/cytoscape.js-cise/cytoscape-cise.js"></script>
<script src="https://unpkg.com/weaverjs@1.2.0/dist/weaver.min.js"></script>
<script src="https://cytoscape.org/cytoscape.js-spread/cytoscape-spread.js"></script>
<script src="https://cytoscape.org/cytoscape.js-euler/cytoscape-euler.js"></script>
<script src="https://ivis-at-bilkent.github.io/cytoscape.js-cosep/demo/forTestingCose/cytoscape-cosep-port.js"></script>
<script src="https://ivis-at-bilkent.github.io/cytoscape.js-cosep/cytoscape-cosep.js"></script>
<script type="module">
$(function() {
    let cy = cytoscape({
        container: document.getElementById('cy'), // container to render in
        elements: data,
        selectionType: 'single',
        layout: {
            name: $("#layout").val()
        },
        style: [{
            selector: 'node',
            style: {
                'label': 'data(id)'
            },
            css: {
                "text-rotation": "autorotate",
            }
        },{
            selector: 'edge',
            style: {
                'width': 1,
                'target-arrow-shape': 'triangle',
                'curve-style': 'bezier'
            }
        }],
    });
    $("#layout").change(function() {
        let animate = $(this).val() !== 'cise' && $(this).val() !== 'avsdf';
        let layout = cy.layout({
            name: $(this).val(),
            animate: animate,
            nodeDimensionsIncludeLabels: true,
            fit: true,
            padding: 20,
        });
        layout.run();
    })
});
</script>
</body>
</html>
