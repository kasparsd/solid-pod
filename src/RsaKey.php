<?php

namespace Preseto\SolidPod;

class RsaKey {

	protected $private;

	protected $public;

	public function __construct( $private_key = null, $public_key = null ) {
		if ( ! empty( $private_key ) ) {
			$this->private = openssl_pkey_get_private( $private_key );
		}

		if ( ! empty( $public_key ) ) {
			$this->public = openssl_pkey_get_public( $public_key );
		}
	}

	public static function is_supported() {
		return function_exists( 'openssl_pkey_new' );
	}

	public function new( $params = [] ) {
		$params = array_merge( [
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		], $params );

		$pkey = openssl_pkey_new( $params );

		// Set the private.
		openssl_pkey_export( $pkey, $this->private );

		$pkey_details = openssl_pkey_get_details( $pkey );

		$this->public = $pkey_details['key'];
	}

	public function public() {
		if ( ! empty( $this->public ) ) {
			return $this->public;
		}

		return null;
	}

	public function private() {
		if ( ! empty( $this->private ) ) {
			return $this->private;
		}

		return null;
	}

	protected function details_public() {
		if ( $this->public ) {
			return openssl_pkey_get_details( $this->public );
		}

		return null;
	}

	public function modulus() {
		$details = $this->details_public();

		if ( ! empty( $details['rsa']['n'] ) ) {
			return bin2hex( $details['rsa']['n'] );
		}

		return null;
	}

	public function exponent() {
		$details = $this->details_public();

		if ( ! empty( $details['rsa']['e'] ) ) {
			return intval( $details['rsa']['e'] );
		}

		return null;
	}

}
