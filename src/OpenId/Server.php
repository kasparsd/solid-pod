<?php

namespace Preseto\SolidPod\OpenId;

use OAuth2\Request;
use OAuth2\Response;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\AuthorizationCode;

class Server {

	protected $storage;

	protected $server;

	public function __construct( $wpdb ) {
		$this->storage = new Storage( $wpdb );
		$this->server = new \OAuth2\Server( $this->storage );

		$this->server->addGrantType( new ClientCredentials( $this->storage ) );
		$this->server->addGrantType( new AuthorizationCode( $this->storage ) );
	}

	public function create_tables() {
		return $this->storage->create_tables();
	}

	public function request_token( $request ) {
		return $this->server->handleTokenRequest( $request )->send();
	}

	public function request_resource( $request ) {
		if ( $this->server->verifyResourceRequest( $request ) ) {
			return wp_json_encode( [
				'success' => true,
				'message' => 'You accessed my APIs!',
			] );
		}

		return $this->server->getResponse()->send();
	}

	public function request_authorize( $request ) {
		$response = new Response();

		if ( $server->validateAuthorizeRequest( $request, $response ) ) {
			$server->handleAuthorizeRequest( $request, $response, true );
		}

		return $response->send();
	}

}
