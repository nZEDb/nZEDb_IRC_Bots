<?php

define('req_settings', true);

$preData = false;

if (isset($_POST['data'])) {

	$data = @unserialize($_POST['data']);

	if ($data !== false && is_array($data) && isset($data[0]['ident'])) {
		require(dirname(__FILE__) . '/../settings.php');
		require(dirname(__FILE__) . '/../Classes/DB.php');
		$db = new nzedb\db\DB;

		$preData = array();
		foreach ($data as $request) {
			$result = $db->queryOneRow(
				sprintf('
					SELECT title, groupname, reqid
					FROM predb
					WHERE reqid = %d
					AND groupname = %s
					LIMIT 1',
					$request['reqid'],
					$db->escapeString($request['group'])
				)
			);
	
			if ($result !== false) {
				$result['ident'] = $request['ident'];
				$preData[] = $result;
			}
		}
	}

} else if (isset($_GET['reqid']) && isset($_GET['group']) && is_numeric($_GET['reqid'])) {

	require(dirname(__FILE__) . '/../settings.php');
	require(dirname(__FILE__) . '/../Classes/DB.php');
	$db = new nzedb\db\DB;

	$preData = $db->queryOneRow(
		sprintf('
			SELECT title, groupname, reqid
			FROM predb
			WHERE reqid = %d
			AND groupname = %s
			LIMIT 1',
			$_GET['reqid'],
			$db->escapeString(trim($_GET['group']))
		)
	);
	
	if ($preData !== false) {
		$preData['ident'] = 0;
		$preData = array($preData);
	}
}

header('Content-type: text/xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<requests>\n";

if ($preData !== false) {
	foreach ($preData as $pre) {
		echo (
			'    <request name="' .
			preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '.', $pre['title']) .
			'" group="' . $pre['groupname'] .
			'" reqid="' . $pre['reqid'] .
			'" ident="' . $pre['ident'] .
			"\"/>\n"
		);
	}
}

echo '</requests>';
