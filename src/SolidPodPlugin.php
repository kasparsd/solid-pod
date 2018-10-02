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
		add_action( 'template_redirect', [ $this, 'request_pod' ] );
		add_action( 'init', [ $this, 'register_well_known_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'register_well_known_query' ] );

		register_activation_hook( $this->plugin->file(), array( $this, 'activation_hook' ) );
	}

	public function activation_hook() {
		$this->openid_server->create_tables();
	}

	public function request() {
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

}
