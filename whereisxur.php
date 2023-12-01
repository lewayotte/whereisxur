<?php
	
error_reporting( E_ERROR | E_WARNING );
	
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();

$db_dir = __DIR__ . '/db';

global $xur_db;
$xur_db = new \SleekDB\Store( 'last_location', $db_dir );

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
	$content = file_get_contents( $url );
	
	preg_match( '/\>(Xûr .*)/', $content, $match );

	if ( !empty( $match[1] ) ) {

		return htmlspecialchars_decode( strip_tags( $match[1] ) );
		
	}

	return false;
}

function has_xur_moved( $xur_location ) {
	
	global $xur_db;
	
	if ( $last_location = $xur_db->findOneBy( [ 'last_location', '=', $xur_location ] ) ) {

		return false;
		
	} else {
		
		$xur_db->insert(
			[
				'last_location' => $xur_location,
			]
		);
		
	}
	
	return true;
	
}

function send_to_discord( $payload ) {
		
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
