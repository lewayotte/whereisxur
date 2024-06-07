<?php

global $xur_db, $debug;

$debug = false;

error_reporting( E_ERROR | E_WARNING );
	
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();

$db_dir = __DIR__ . '/db';
$xur_db = $db_dir . '/last_location.txt'; 

if ( $xur_location = get_xur_location() ) {

	if ( has_xur_moved( $xur_location ) ) {
		
		$payload = array(
			'content'    => $xur_location
		);
		
		send_to_discord( $payload );
		
	}

}

function get_xur_location() {
	
	$url = 'https://whereisxur.com/';
	$options  = array('http' => array('user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'));
	$context  = stream_context_create($options);
	$content = file_get_contents( $url, false, $context );
	
	preg_match( '/\>(Xûr .*\.)/', $content, $match );

	if ( !empty( $match[1] ) ) {

		return htmlspecialchars_decode( strip_tags( $match[1] ) );
		
	}

	return false;
}

function has_xur_moved( $xur_location ) {
	
	global $xur_db, $debug;

	$last_location = file_get_contents( $xur_db ); 

	if ( $debug ) {

		print_r( $xur_location );
		print_r( $last_location );

	}
	
	if ( $last_location == $xur_location ) {

		return false;
		
	} else {

		file_put_contents( $xur_db, $xur_location );
		
	}
	
	return true;
	
}

function send_to_discord( $payload ) {

	global $debug;

	if ( $debug ) {

		print_r( $payload );

	} else {
		
		$curl = curl_init();

		// How to Setup a Discord Webhook: https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks
		curl_setopt_array( $curl, 
			array(
				CURLOPT_URL            => $_ENV['DISCORD_WEBHOOK'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => $payload,
			)
		);
	
		$response = curl_exec( $curl ) ;
		curl_close( $curl );
	
		return $response;

	}
	
}
