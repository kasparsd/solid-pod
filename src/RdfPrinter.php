<?php

namespace Preseto\SolidPod;

class RdfPrinter {

	protected $graph;

	public function __construct( $graph ) {
		$this->graph = $graph;
	}

	public function print( $rdf_type ) {
		header( 'Content-Type: ' . $rdf_type->mime_type() );

		echo $this->graph->serialise( $rdf_type->type() );
	}

}
