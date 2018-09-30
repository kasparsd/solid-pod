<?php

namespace Preseto\SolidPod;

class WebId {

	const META_KEY_RSA_PUBLIC_KEY = 'solid_pod_rsa_public_key';

	public function __construct( $user ) {
		$this->user = $user;
	}

	protected function document_uri() {
		return get_author_posts_url( $this->user->ID );
	}

	public function webid_uri() {
		return $this->document_uri() . '#me';
	}

	public function account_uri() {
		return $this->document_uri() . '#account';
	}

	public function rsa_public_key() {
		$public_key = $this->get_rsa_public_key();

		if ( RsaPublicKey::is_supported() && ! empty( $public_key ) ) {
			return new RsaPublicKey( $public_key );
		}

		return null;
	}

	protected function get_rsa_public_key() {
		// TODO Add sanitization.
		return (string) get_the_author_meta( self::META_KEY_RSA_PUBLIC_KEY, $this->user->ID );
	}

}
