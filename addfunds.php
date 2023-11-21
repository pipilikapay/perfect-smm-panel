<?php
	if ($_GET && $_GET["success"]) :
	    $success = 1;
	    $successText = "Your payment paid successfully";
	endif;

	if ($_GET && $_GET["cancel"]) :
	    $error = 1;
	    $errorText = "Your payment cancelled successfully";
	endif;

	elseif ($method_id == 65) :
	$api_key = $extra['api_key'];
	$secret_key = $extra['secret_key'];
	$panel_URL = $extra['panel_URL'];

	$namount = $amount * $extra['exchange_rate'];
	$fee = $extra['fee'];
	$famount = $namount;

	$famount = number_format((float) $famount, 2, ".", "");
	$txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);

    $baseURL = $panel_URL;

    $callbackURL= site_url('addfunds?success=true');
    $webhookURL= site_url('payment/payweblink');
    $cancelURL= site_url('addfunds?cancel=true');

    $apiKey= $api_key;
    $secretKey= $secret_key;

    $metadata = array(
        'customerID' => $user['client_id'],
        'orderID' => $txnid
    );

    $requestbody = array(
        'apiKey' => $apiKey,
        'secretkey' => $secretKey,
        'amount' => $famount,
        'fullname' => isset($user['username']) ? $user['username'] : 'John Doe',
        'email' => $user['email'],
        'successurl' => $callbackURL,
        'webhookUrl' => $webhookURL,
        'cancelurl' => $cancelURL,
        'metadata' => json_encode($metadata)
    );
    $url = curl_init("$baseURL/payment/api/create_payment");                     
    $requestbodyJson = json_encode($requestbody);

    $header = array(
        'Content-Type:application/json'
    );

    curl_setopt($url, CURLOPT_HTTPHEADER, $header);
    curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($url, CURLOPT_POSTFIELDS, $requestbodyJson);
    curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $response = curl_exec($url);
    curl_close($url);


	$result = json_decode($response, true);
	if ($result['status']) {
		$order_id = $txnid;
		$insert = $conn->prepare("INSERT INTO payments SET client_id=:c_id, payment_amount=:amount, payment_privatecode=:code, payment_method=:method, payment_create_date=:date, payment_ip=:ip, payment_extra=:extra");
		$insert->execute(array("c_id" => $user['client_id'], "amount" => $amount, "code" => $paymentCode, "method" => $method_id, "date" => date("Y.m.d H:i:s"), "ip" => GetIP(), "extra" => $order_id));

		if ($insert) {
		$pwlURL = $result['paymentURL'];
		}
	} else {
		echo $response;
		exit();
	}

	// Redirects to payweblink
	echo '<div class="dimmer active" style="min-height: 400px;">
		<div class="loader"></div>
		<div class="dimmer-content">
			<center>
				<h2>Please do not refresh this page</h2>
			</center>
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin:auto;background:#fff;display:block;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
				<circle cx="50" cy="50" r="32" stroke-width="8" stroke="#e15b64" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round">
					<animateTransform attributeName="transform" type="rotate" dur="1s" repeatCount="indefinite" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform>
				</circle>
				<circle cx="50" cy="50" r="23" stroke-width="8" stroke="#f8b26a" stroke-dasharray="36.12831551628262 36.12831551628262" stroke-dashoffset="36.12831551628262" fill="none" stroke-linecap="round">
					<animateTransform attributeName="transform" type="rotate" dur="1s" repeatCount="indefinite" keyTimes="0;1" values="0 50 50;-360 50 50"></animateTransform>
				</circle>
			</svg>
			<form action="' . $pwlURL . '" method="get" name="payweblinkForm" id="pay">
				<script type="text/javascript">
					document.getElementById("pay").submit();
				</script>
			</form>
		</div>
	</div>';
?>