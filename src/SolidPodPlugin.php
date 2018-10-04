<?php

namespace Preseto\SolidPod;

use OAuth2\Request;

class SolidPodPlugin {

	const POD_QUERY_VAR = 'solid-pod';

	protected $plugin;

	protected $openid_server;

	public function __construct( $plugin ) {
		global $wpdb;

		$this->plugin = $plugin;

		$this->openid_server = new OpenId\Server( $wpdb, $this->openid_base_url() );
	}

	public function init_hooks() {
		add_action( 'init', [ $this, 'register_well_known_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'register_well_known_query' ] );
		add_action( 'template_redirect', [ $this, 'request' ] );

		register_activation_hook( $this->plugin->file(), array( $this, 'activation_hook' ) );
	}

	public function activation_hook() {
		$this->openid_server->create_tables();
		flush_rewrite_rules();
	}

	public function request() {
		$pod_query = get_query_var( self::POD_QUERY_VAR );

		if ( ! empty( $pod_query ) ) {
			$routes_available = $this->get_routes();

			if ( in_array( $pod_query, $routes_available, true ) ) {
				$this->handle_pod_request( $pod_query );
				exit;
			}
		}

		if ( is_author() ) {
			$author = get_queried_object();
			$rdf_type = new RdfType( $_SERVER['HTTP_ACCEPT'] );
			$requested_rdf_type = $rdf_type->requested();

			if ( ! empty( $requested_rdf_type ) && is_a( $author, 'WP_User' ) ) {
				$rdf = new RdfBuilder();
				$printer = new RdfPrinter( $rdf->buildUserGraph( $author ) );
				$printer->print( $rdf_type );
				exit;
			}
		}
	}

	public function handle_pod_request( $request_type ) {
		$request = Request::createFromGlobals();

		$login_url = $this->login_url( [
			'redirect_to' => urlencode( $this->route_url( $request_type ) ),
		] );

		if ( 'config' === $request_type ) {
			$response = $this->openid_server->response( $this->get_openid_configuration() );
			$response->send();
			exit;
		}

		if ( 'jwks' === $request_type ) {
			$response = $this->openid_server->request_jwks( $request );
			$response->send();
			exit;
		}

		// Dynamic Client Registration (First Time Only)
		if ( 'register' === $request_type ) {
			$response = $this->openid_server->request_register( $request );
			$response->send();
			exit;
		}

		// TODO Add confirmation prompt!
		if ( 'authorize' === $request_type ) {
			$response = $this->openid_server->request_authorize( $request );
			$response->send();
			exit;
		}
	}

	public function login_url( $query_params = [] ) {
		$login_url = wp_login_url();

		if ( ! empty( $query_params ) ) {
			$login_url = add_query_arg( $query_params, $login_url );
		}

		return $login_url;
	}

	public function openid_base_url() {
		return site_url( '/' );
	}

	protected function get_routes() {
		return [
			'config',
			'jwks',
			'register',
			'authorize',
			'token',
			'userinfo',
			'logout',
		];
	}

	public function route_url( $route ) {
		$routes = $this->get_routes();

		if ( in_array( $route, $routes, true ) ) {
			return add_query_arg( self::POD_QUERY_VAR, $route, $this->openid_base_url() );
		}

		return null;
	}

	protected function get_openid_configuration() {
		return [
			'issuer' => $this->openid_base_url(),
			'authorization_endpoint' => $this->route_url( 'authorize' ),
			'registration_endpoint' => $this->route_url( 'register' ),
			'token_endpoint' => $this->route_url( 'token' ),
			'userinfo_endpoint' => $this->route_url( 'userinfo' ),
			'jwks_uri' => $this->route_url( 'jwks' ),
			'scopes_supported' => [
				'openid',
				'profile',
				'email',
			],
			'response_types_supported' => [
				'code',
				'token',
				'id_token',
				'code id_token',
				'token id_token',
				'code token id_token',
			],
			'claims_supported' => [
				// TODO
			],
			'id_token_signing_alg_values_supported' => [
				// TODO
			]
		];
	}

	public function register_well_known_rewrite() {
		$destination = sprintf(
			'index.php?%s=config',
			self::POD_QUERY_VAR
		);

		add_rewrite_rule(
			'^.well-known/openid-configuration/?',
			$destination,
			'top'
		);
	}

	public function register_well_known_query( $query_vars ) {
		$query_vars[] = self::POD_QUERY_VAR;

		return $query_vars;
	}

}
