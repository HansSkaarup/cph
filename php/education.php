<?php
/**
 * Created by PhpStorm.
 * User: georgina scholes
 * Date: 7/12/2016
 * Time: 9:33 PM
 */
/**
 * PostGIS to GeoJSON
 * Query a PostGIS table or view and return the results in GeoJSON format, suitable for use in OpenLayers, Leaflet, etc.
 *
 * @param 		string		$geotable		The PostGIS layer name *REQUIRED*
 * @param 		string		$geomfield		The PostGIS geometry field *REQUIRED*
 * @param 		string		$srid			The SRID of the returned GeoJSON *OPTIONAL (If omitted, EPSG: 4326 will be used)*
 * @param 		string 		$fields 		Fields to be returned *OPTIONAL (If omitted, all fields will be returned)* NOTE- Uppercase field names should be wrapped in double quotes
 * @param 		string		$parameters		SQL WHERE clause parameters *OPTIONAL*
 * @param 		string		$orderby		SQL ORDER BY constraint *OPTIONAL*
 * @param 		string		$sort			SQL ORDER BY sort order (ASC or DESC) *OPTIONAL*
 * @param 		string		$limit			Limit number of results returned *OPTIONAL*
 * @param 		string		$offset			Offset used in conjunction with limit *OPTIONAL*
 * @return 		string					resulting geojson string
 */
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
$sql = "SELECT pct_ingen_, pct_gymnas, pct_under_, pct_erh_fa, pct_kort_v, pct_mellem, pct_lang_v
FROM socio_data, adresser WHERE st_intersects(adresser.geom, socio_data.geom)
AND (adresser.vejnavn || ' ' || adresser.husnr || ', ' || adresser.postnr || ' ' || adresser.postnrnavn) = '" . $adresse . "' ";


//echo $sql;

# Try query or error
$rs = pg_query($conn, $sql);
if (!$rs) {
    echo "An SQL error occured.\n";
    exit;
}

# Build GeoJSON
$dataOutput = '';
$labelArray = array(
    "ingen", "gymnasie", "under", "erhhverv", "kort_vid", "mellem_vid", "lang_vid"
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