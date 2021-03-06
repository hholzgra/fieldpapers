<?php

require_once '../lib/lib.everything.php';

enforce_master_on_off_switch($_SERVER['HTTP_ACCEPT_LANGUAGE']);

$context = default_context(false);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$query = <<<EOQ
SELECT
    COUNT(pages.print_id) AS pages,
    prints.created,
    prints.composed,
    prints.orientation,
    prints.layout,
    prints.place_woeid,
    prints.region_woeid,
    prints.country_woeid,
    prints.private,
    prints.cloned,
    prints.refreshed,
    pages.provider
FROM prints
LEFT JOIN pages ON pages.print_id=prints.id
GROUP BY prints.id
ORDER BY created DESC
EOQ;

$res = $context->db->query($query);

if (PEAR::isError($res)) {
    die_with_code(500, $res->message);
}

$rsp = array();

while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $rsp[] = array(
        "pages"			=> (int) $row['pages'],
        "created"		=> date("c", strtotime($row['created'])),
        "composed" 		=> date("c", strtotime($row['composed'])),
        "orientation"	=> $row['orientation'],
        "layout"		=> $row['layout'],
        "provider"		=> $row['provider'],
    );
}

//echo json_encode($rsp);
$metric_data = json_encode($rsp);
$context->sm->assign('metric_data', $metric_data);

$providers = get_map_providers();
$providers_by_name = array();
foreach ($providers as $i => $provider) {
	$providers_by_name[$provider[0]] = $provider[1];
}
$context->sm->assign('providers', json_encode($providers_by_name));

header("Content-Type: text/html; charset=UTF-8");
print $context->sm->fetch("metrics.html.tpl");
?>
