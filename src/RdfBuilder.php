<?php

namespace Preseto\SolidPod;

use \EasyRdf\Graph;

class RdfBuilder {

	public function buildUserGraph( $user ) {
		$graph = new Graph();
		$webid = new WebId( $user );

		$author_resource = $graph->resource( $webid->webid_uri(), 'foaf:Person' );
		$account_resource = $graph->resource( $webid->account_uri(), 'sioc:UserAccount' );

		$author_resource->set( 'foaf:name', $user->display_name ?: null );
		$author_resource->set( 'foaf:givenName', $user->user_firstname ?: null );
		$author_resource->set( 'foaf:familyName', $user->user_lastname ?: null );
		$author_resource->set( 'foaf:nick', $user->nickname ?: null );
		$author_resource->set( 'bio:olb', $user->user_description ?: null );
		$author_resource->set( 'foaf:account', $account_resource );

		$account_resource->set( 'sioc:name', $user->display_name ?: null );
		$account_resource->set( 'sioc:account_of', $author_resource );

		$rsa_public_key = $webid->rsa_public_key();

		if ( $rsa_public_key ) {
			$key_resource = $graph->newBNode( 'cert:RSAPublicKey' );
			$key_resource->set( 'cert:exponent', new \EasyRdf\Literal\Integer( $rsa_public_key->exponent() ) );
			$key_resource->set('cert:modulus', new \EasyRdf\Literal\HexBinary( $rsa_public_key->modulus() ) );
			$author_resource->set( 'cert:key', $key_resource );
		}

		return $graph;
	}

}
