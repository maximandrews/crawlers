<?php
	//Checking path to image which should be posted (set manually from command line)
	if (isset($argv[1])) {
		$imageName = $argv[1];
	}
	elseif (!isset($imageName)) {
		echo "Please provide path to image which should be posted.";
		exit;
	}

	if (!preg_match('/[a-zA-Z0-9]+\.[a-zA-Z]/', $imageName)) {
		echo "Not a path provided. Please try again.";
		exit;
	}

	$extension = str_replace('.', '', strstr($imageName, '.'));
	switch ($extension) {
		case "png":
			$image = @imagecreatefrompng($imageName);
			break;
		case ("jpeg" or "jpg"):
			$image = @imagecreatefromjpeg($imageName);
			break;
		case "gif":
			$image = @imagecreatefromgif($imageName);
			break;
	}

	if ((!$image) or (!isset($image))) {
		echo "Please provide accessible path to existing image which should be posted.";
		exit;
	}

	imagedestroy($image);

	//App properties
	$app_id = "191232707711780";
	$app_secret = "88ef147af3cd9af7585962e7e4d32325";

	//$access_token = "191232707711780|EMzYebKrK-0Hypv-IuYW3WTjmXI";
	$access_token = "CAACt7NWc0yQBACSyyrIOUYZACqOEbRQFP6CnMOSUfCIyU2KeIcmeg6bJiy8YQ8f8S811nxQkOx621CgXqCyfOdW0DxoayfuVASmU2T3aU4w2KYlnnbnu6x0kONEmlKiD7ifNOR67j6gIZB2pn1jtgouydQ25oZD";

	//upload image
	$args = array('access_token' => $access_token, 'message' => 'Photo from application');
	$args['source'] = new CurlFile($imageName, 'image/'.$extension);

	$ch = curl_init();
	$url = 'https://graph.facebook.com/100006327070594/photos?access_token='.$access_token;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$data = curl_exec($ch);

	//returns the image id
	print_r(json_decode($data,true));

	curl_close($ch);
?>