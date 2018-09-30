<?php

namespace Preseto\SolidPod;

class RsaPublicKey {

	protected $key;

	protected $details = [];

	public function __construct( $public_key ) {
		$this->key = openssl_pkey_get_public( $public_key );

		if ( ! empty( $this->key ) ) {
			$this->details = openssl_pkey_get_details( $this->key );
		}
	}

	public static function is_supported() {
		return function_exists( 'openssl_pkey_get_public' );
	}

	public function exponent() {
		if ( ! empty( $this->details['rsa']['e'] ) ) {
			return intval( $this->details['rsa']['e'] );
		}

		return null;
	}

	public function modulus() {
		if ( ! empty( $this->details['rsa']['n'] ) ) {
			return bin2hex( $this->details['rsa']['n'] );
		}

		return null;
	}

}
