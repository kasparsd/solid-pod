<?php

namespace Preseto\SolidPod;

class SolidPodPlugin {

	const POD_QUERY_VAR = 'solid-pod';

	protected $plugin;

	protected $openid_server;

	public function __construct( $plugin ) {
		global $wpdb;

		$this->plugin = $plugin;

		$this->openid_server = new OpenId\Server( $wpdb );
	}

	public function init_hooks() {
		add_action( 'template_redirect', [ $this, 'request' ] );
		add_action( 'init', [ $this, 'register_well_known_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'register_well_known_query' ] );

		register_activation_hook( $this->plugin->file(), array( $this, 'activation_hook' ) );
	}

	public function activation_hook() {
		$this->openid_server->create_tables();
	}

	public function request() {
		$pod_query = get_query_var( self::POD_QUERY_VAR );

		if ( ! empty( $pod_query ) ) {
			$this->handle_pod_request( $pod_query );
			exit;
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
		// TODO.
	}

	protected function get_openid_configuration() {
		return [
			'issuer' => site_url(),
		];
	}

	public function register_well_known_rewrite() {
		$destination = sprintf(
			'index.php?%s=openid-config',
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
