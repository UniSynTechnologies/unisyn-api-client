<?php
require_once __DIR__ . '/api.class.php';

// start session if not started to store the access token
if (session_id() == "") {
    session_start();
}

if ( !empty($_POST['endpoint']) ) {
	$endpoint = $_POST['endpoint'];
}
else if ( !empty($_GET['endpoint']) ) {
    $endpoint = $_GET['endpoint'];
}
else {
	echo '{"error": "No endpoint specified"}';
	die();
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
	if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
		$method = 'DELETE';
	}
	else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
		$method = 'PUT';
	}
	else {
		echo '{"error": "Unexpected Header"}';
		die();
	}
}

$payload = array();
if ( !empty($_POST['payload']) ) {
	$payload = $_POST['payload'];
}
if ( !empty($_FILES['file']['name']) ) {
	if ( !empty($_FILES['file']['error']) ) {
		$phpFileUploadErrors = array(
			0 => 'There is no error, the file uploaded with success.',
			1 => 'The uploaded file exceeds the maximum allowed file size of ' . ini_get("upload_max_filesize") . '.',
			2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
			3 => 'The uploaded file was only partially uploaded.',
			4 => 'No file was uploaded.',
			6 => 'Missing a temporary folder.',
			7 => 'Failed to write file to disk.',
			8 => 'A server extension stopped the file upload.',
		);
		$friendlyErrorText = $phpFileUploadErrors[$_FILES['file']['error']];
		echo json_encode( array('response' => $friendlyErrorText ) );
		die();
	}
	$fileContent = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

	$payload['file'] = json_encode(array(
		'name' => $_FILES['file']['name'],
		'body' => $fileContent,
		'type' => $_FILES['file']['type'],
	));
}

$centralAPIResponse = (new centralAPI)->doCall($endpoint, $method, $payload);

echo $centralAPIResponse;
