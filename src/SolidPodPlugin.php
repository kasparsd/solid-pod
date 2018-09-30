<?php

namespace Preseto\SolidPod;

class SolidPodPlugin {

	protected $plugin;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	public function init_hooks() {
		add_action( 'template_redirect', [ $this, 'request' ] );
	}

	public function request() {
		if ( is_author() ) {
			$rdf_type = new RdfType( $_SERVER['HTTP_ACCEPT'] );
			$requested_rdf_type = $rdf_type->requested();

			if ( ! empty( $requested_rdf_type ) ) {
				$author = get_queried_object();
				$rdf = new RdfBuilder();
				$printer = new RdfPrinter( $rdf->buildUserGraph( $author ) );
				$printer->print( $rdf_type );
				die;
			}
		}
	}

}
