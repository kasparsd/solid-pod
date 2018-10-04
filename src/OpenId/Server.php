<?php

namespace Preseto\SolidPod\OpenId;

use Preseto\SolidPod\WebId;
use phpseclib\Crypt\RSA;
use OAuth2\Response;

class Server {

	protected $storage;

	protected $server;

	public function __construct( $wpdb, $issuer ) {
		$this->storage = new Storage( $wpdb );

		$server_config = [
			'use_openid_connect' => true,
			'allow_implicit' => true,
			'enforce_state' => false,
			'issuer' => $issuer,
		];

		$this->server = new \OAuth2\Server( $this->storage, $server_config );

		$key_storage = new \OAuth2\Storage\Memory( [
			'keys' => [
				'public_key' => $this->key_public(),
				'private_key' => $this->key_private(),
			]
		] );

		$this->server->addStorage( $key_storage, 'public_key' );
	}

	public function create_tables() {
		return $this->storage->create_tables();
	}

	public function response( $params = [], $status_code = 200, $headers = [] ) {
		$headers = array_merge( $headers, [
			'Access-Control-Allow-Origin' => '*',
		] );

		return new Response( $params, $status_code, $headers );
	}

	public function request_register( $request ) {
		$response = $this->response();

		$client_id = $request->query( 'client_id', $request->request('client_id') );
		$redirect_uri = $request->query( 'redirect_uris', $request->request('redirect_uris' ) );
		$grant_types = $request->query( 'grant_types', $request->request('grant_types') );
		$response_types = $request->query( 'response_types', $request->request('response_types') );
		$scope = $request->query( 'scope', $request->request('scope') );

		// Register our client per Dynamic Client Registration.
		if ( empty( $client_id ) && ! empty( $redirect_uri ) ) {
			$client_id = md5( uniqid() );

			if ( is_array( $redirect_uri ) ) {
				$redirect_uri = implode( ' ', $redirect_uri );
			}

			if ( is_array( $grant_types ) ) {
				$grant_types = implode( ' ', $grant_types );
			}

			if ( is_array( $response_types ) ) {
				$response_types = implode( ' ', $response_types );
			}

			$this->storage->setClientDetails(
				$client_id,
				'', // No secret for you.
				$redirect_uri,
				$grant_types,
				$scope
			);

			$response->setStatusCode( 201 );

			$response->setParameters( [
				'client_id' => $client_id,
				'client_secret' => '',
			] );
		}

		return $response;
	}

	public function request_authorize( $request ) {
		$response = $this->response();

		if ( is_user_logged_in() && $this->server->validateAuthorizeRequest( $request, $response ) ) {
			$user_webid = new WebId( wp_get_current_user() );
			$this->server->handleAuthorizeRequest( $request, $response, true, $user_webid->webid_uri() );
		}

		return $response;
	}

	public function request_jwks( $request ) {
		$rsa = new RSA();
		$rsa->loadKey( $this->key_public() );

		$encoded = \JOSE_JWK::encode( $rsa );

		$key = array_merge( $encoded->components, [
			'alg' => 'RS256',
			'use' => 'sig',
		] );

		return $this->response( [
			'keys' => [
				$key,
			],
		] );
	}

	protected function key_public() {
		return '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5wI98Bc4aVuPIl/nkfDU
O0L7xhJCGHM65yz1KZ62io1pwFxK5ILoF5nheIkWe7V+xz8RiGNWxsPejbleI1aF
vYx9TGFVgV4YDeoGsMjoVg8Ywr1Z8pggKljJ3TuqdLdLoSaS1X0zjnLMjd3i5K0R
ZndWf0cCLl7An8nBlU1tioG4PyUAzNtUgS4XXUq5HUXMBaYCYnDhXHcJU1k82sqK
DYwMm6ZAr/RVe4JRfCZFU09dpb+ViMjLs6TBI0qgIH4QWR3s6mn9ZEILj7Gfi7Y1
XlBSQCGAyKhFoDQGud0on/jsma2KbiEaUZh0RtxnMqOq0BshBVeYKnj570c6cYCP
KQIDAQAB
-----END PUBLIC KEY-----';
	}

	protected function key_private() {
		return '-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA5wI98Bc4aVuPIl/nkfDUO0L7xhJCGHM65yz1KZ62io1pwFxK
5ILoF5nheIkWe7V+xz8RiGNWxsPejbleI1aFvYx9TGFVgV4YDeoGsMjoVg8Ywr1Z
8pggKljJ3TuqdLdLoSaS1X0zjnLMjd3i5K0RZndWf0cCLl7An8nBlU1tioG4PyUA
zNtUgS4XXUq5HUXMBaYCYnDhXHcJU1k82sqKDYwMm6ZAr/RVe4JRfCZFU09dpb+V
iMjLs6TBI0qgIH4QWR3s6mn9ZEILj7Gfi7Y1XlBSQCGAyKhFoDQGud0on/jsma2K
biEaUZh0RtxnMqOq0BshBVeYKnj570c6cYCPKQIDAQABAoIBACHU5DlLTUmwzQ+d
uA5ZlNtw/eqONfvatF+y59zGj7lO6JPxcE5zFYaPVMQQX6iSdhS8Gdc9pTHK8ccT
xMOsIj4WWytafelKXH99LKmrYstnvpqWnJu4x80r37R0zov1ZDAftBVsFqbgDTEh
s98FsayuAY72WU1tNwyvwZgaFNbZzWP9OI5kO0p4KgddoMdBJc3fiEQtwMgIqXI3
n3o0jML9n+NPU0wUKdgKJ1bzGgdfESrEplpT4rozq4Gd5VB3besllzueM7tyheAV
usGmDRW4RTgBAlzwycsl3H78w5XVCOtWh+oK1chYJSJj3807j27vc5clHB/Pit84
9OZaoWECgYEA9c9DYPHpskiUNWsVATrvO1RcSlelyEMNXAj/64Ri7KpbfoFuCA+m
yuadYCAnUny2SSK5ZC9EzjGPVg1eflJIJGwkA0nRwB7TRwCV1fvcQAn6huYe03Ne
exTpjDMM4mIb/77JW6p6dkRPcXrQnEipR0dAoIp6Lbz+aEUeyWuek8UCgYEA8JXm
TeALP1rqhq+yzqOAF6By5ajMkOqlAoLrORIENVMfaHg7x3H2AVIpFImKT8BuSJSU
U2kjnz8E46nBfClNmKO/VIz6n7N5uLNJX9mNSGSRrNTNpJYEh8/ZBkQ0wpJYE9cD
CSQhjRUTTpXSOpZ1K4J7X+yF80jyXMepenu7sBUCgYEA7QOknF2mUNnazocqAb2p
3zNAqg5JNAgzYYLsAVPtbvX0ss5qIiu1T/99z6oxQEAwI+TvjcJmPctbwkWxz2vX
VAdR0jnjnbQbVGMkFuh7PuRgRKKvJoQfnd5UM3MmAUNIbBiyX0jKPC8xyuH5NDh7
wNbbYfEkTeXmCPRc8ypqBWECgYB/Ved6ak2pr7YXqWDLS/BdEPgfI9N6FCdYB/D1
2NpPWvpxscl6C5A4LMM/cni5M1FrqvBCoZBQFevBj7SvwbCnTGvVFXUFF9oh7wqz
55KGsWwE/PEG3rvxIgps7aa7IPfrME8UBIKZiIEag+OsTLwhVkN0htxy4rYcczT5
dgnl7QKBgAJEZf/ST1JJSub4pUfk6kwWg5exMB2rhV5OgmRWziRvh87qkaOWQm35
HQOBycbN+hm/99S0xiBRmLLGUxvBPQphseEfiCX9xwqcJINYGJ3Ph9cbyXfvo5eP
h9jB4yFk0Gd4QR9IYM6Ao0KXF7xzWUATT36Q4oMcfzvBzmT7C2n1
-----END RSA PRIVATE KEY-----';
	}

}
