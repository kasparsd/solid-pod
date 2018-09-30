<?php

class RsaPublicKey {

	public function __construct( $exponent, $modulus ) {
		$this->exponent = $exponent;
		$this->modulus = $modulus;
	}

	public function exponent() {
		return $this->exponent;
	}

	public function modulus() {
		return $this->modulus;
	}

}
