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
$sql = "SELECT alder_pct_, alder_pct0, alder_pct1, alder_pct2, alder_pct3, alder_pct4, alder_pct5 
FROM socio_data, adresser WHERE st_intersects(adresser.geom, socio_data.geom) 
AND (adresser.vejnavn || ' ' || adresser.husnr || ', ' || adresser.postnr || ' ' || adresser.postnrnavn) = '" . $adresse . "' ";


# Try query or error
$rs = pg_query($conn, $sql);
if (!$rs) {
    echo "An SQL error occured.\n";
    exit;
}

# Build GeoJSON
$dataOutput = '';
$labelArray = array(
    "0-5", "6-17", "18-29", "30-39", "40-49", "50-64", "65+"
);
while ($row = pg_fetch_assoc($rs)) {
    $i = 0;
    foreach ($row as $key => $val) {
        # example line { y: 9.21, label: "0-5" },

        $dataOutput .= '{ "y": ' . escapeJsonString($val) . ', "label": "' . $labelArray[$i] . '"}';
        if ($i != 6){
            $dataOutput .= ',';
        }
        $i ++;
    }
}



$output = '{"data":[ ' . $dataOutput . ' ]}';
echo $output;
?>