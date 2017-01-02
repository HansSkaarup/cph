<?php

function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}


# Connect to PostgreSQL database
$conn = pg_connect("dbname='Semester1' user='postgres' password='postgres' host='localhost'");
if (!$conn) {
    echo "Not connected : " . pg_error();
    exit;
}

# If no input to adress, echo error message. Otherwise continue and set variable adress = input from GET function.
if (empty($_GET['adresse'])) {
    echo "missing required paramater: <i>adresse_input</i>";
    exit;
} else
    $adresse = $_GET['adresse'];


# Build SQL SELECT statement and return the geometry as a GeoJSON element in EPSG: 4326
$sql = "SELECT ROUND(SUM(st_area(park.geom))/10000) as ha
FROM park, adresser
WHERE st_intersects(park.geom, st_buffer(adresser.geom, 500))
AND (adresser.vejnavn || ' ' || adresser.husnr || ', ' || adresser.postnr || ' ' || adresser.postnrnavn) = '" . $adresse . "' ";


# Try query or error
$rs = pg_query($conn, $sql);
if (!$rs) {
    echo "An SQL error occured.\n";
    exit;
}

$db_array = array();

// Retrieve the data from the database
// pg_fetch_assoc takes value and column name (associative array),
//    while pg_fetch_row only takes value (enumerative array).
while ($row = pg_fetch_assoc($rs)) {
    $db_array[] = $row;
}

// Prints the data in JSON format
echo json_encode($db_array);
?>