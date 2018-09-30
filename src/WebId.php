<?php

namespace Preseto\SolidPod;

class WebId {

	const META_KEY_RSA_MODULUS = 'solid_pod_public_key_modulus';
	const META_KEY_RSA_EXPONENT = 'solid_pod_public_key_exponent';

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
		$exponent = $this->get_rsa_public_key_modulus();
		$modulus = $this->get_rsa_public_key_exponent();

		if ( ! empty( $exponent ) && ! empty( $modulus ) ) {
			return new RsaPublicKey( $exponent, $modulus );
		}

		return null;
	}

	protected function get_rsa_public_key_modulus() {
		// TODO Add sanitization.
		return (string) get_the_author_meta( self::META_KEY_RSA_MODULUS, $this->user->ID );
	}

	protected function get_rsa_public_key_exponent() {
		// TODO Add sanitization.
		return (string) get_the_author_meta( self::META_KEY_RSA_EXPONENT, $user->ID );
	}

}
