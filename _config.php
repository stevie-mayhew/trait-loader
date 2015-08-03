<?php

$flush = (isset($_GET['flush']) || isset($_REQUEST['url']) && (
	$_REQUEST['url'] == 'dev/build' || $_REQUEST['url'] == '/dev/build' || $_REQUEST['url'] == BASE_URL . '/dev/build'
));
$manifest = new sgn\TraitManifest(BASE_PATH, $flush);

SS_ClassLoader::instance()->pushManifest($manifest, false);
