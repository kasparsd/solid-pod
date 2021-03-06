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
		return $this->document_uri() . '#i';
	}

	public function account_uri() {
		return $this->document_uri() . '#account';
	}

	public function rsa_public_key() {
		$public_key = $this->rsa_get_public_key();

		if ( RsaKey::is_supported() && ! empty( $public_key ) ) {
			return new RsaKey( null, $public_key );
		}

		return null;
	}

	public function rsa_generate_keys() {
		$key = new RsaKey();
		$key->new();

		$this->rsa_store_public_key( $key->public() );
		$this->rsa_store_private_key( $key->private() );
	}

	protected function rsa_get_public_key() {
		// TODO Add sanitization.
		return (string) get_user_meta( $this->user->ID, self::META_KEY_RSA_PUBLIC_KEY, true );
	}

	protected function rsa_store_public_key( $key ) {
		return update_user_meta( $this->user->ID, self::META_KEY_RSA_PUBLIC_KEY, $key );
	}

	protected function rsa_store_private_key( $key ) {
		return update_user_meta( $this->user->ID, self::META_KEY_RSA_PRIVATE_KEY, $key );
	}

}
