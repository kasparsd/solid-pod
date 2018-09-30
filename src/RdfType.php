<?php

namespace Preseto\SolidPod;

class RdfType {

	protected $mime_type_map = [
		'text/turtle' => 'turtle',
		'application/rdf+xml' => 'rdfxml',
		'application/json' => 'json',
		'application/ld+json' => 'jsonld',
	];

	protected $requested;

	public function __construct( $accept_header ) {
		$this->requested = $this->parse_accept( $accept_header );
	}

	public function requested() {
		return $this->requested;
	}

	public function type() {
		if ( ! empty( $this->requested ) ) {
			return array_values( $this->requested )[0];
		}

		return null;
	}

	public function mime_type() {
		if ( ! empty( $this->requested ) ) {
			return array_keys( $this->requested )[0];
		}

		return null;
	}

	protected function parse_accept( $accept_header ) {
		$accepted_mime_types = array_map( 'trim', preg_split( '#(,|;)#', $accept_header ) );

		return array_intersect_key(
			$this->mime_type_map,
			array_flip( $accepted_mime_types )
		);
	}

}
