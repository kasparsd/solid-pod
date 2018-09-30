<?php

namespace Preseto\SolidPod;

class WebId {

	const META_KEY_RSA_PRIVATE_KEY = 'solid_pod_rsa_private_key';

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
		$public_key = $this->get_rsa_key_public();

		if ( RsaKey::is_supported() && ! empty( $public_key ) ) {
			return new RsaKey( null, $public_key );
		}

		return null;
	}

	protected function get_rsa_key_public() {
		// TODO Add sanitization.
		return (string) get_user_meta( self::META_KEY_RSA_PUBLIC_KEY, $this->user->ID );
	}

}
