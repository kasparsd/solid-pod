<?php

namespace Preseto\SolidPod\OpenId;

class Storage extends \OAuth2\Storage\Pdo {

	const DB_PREFIX = 'solid_pod';

	protected $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;

		$dsn = sprintf(
			'mysql:dbname=%s;host=%s',
			$wpdb->dbname,
			$wpdb->dbhost
		);

		$pdo = new \PDO( $dsn, $wpdb->dbuser, $wpdb->dbpassword );

		parent::__construct( $pdo, $this->build_config() );
	}

	protected function build_config() {
		$config = [
			'client_table' => 'oauth_clients',
			'access_token_table' => 'oauth_access_tokens',
			'refresh_token_table' => 'oauth_refresh_tokens',
			'code_table' => 'oauth_authorization_codes',
			'user_table' => 'oauth_users', // Not used!
			'jwt_table' => 'oauth_jwt',
			'jti_table' => 'oauth_jti',
			'scope_table' => 'oauth_scopes',
			'public_key_table' => 'oauth_public_keys',
		];

		foreach ( $config as &$value ) {
			$value = sprintf( '%s%s_%s', $this->wpdb->prefix, self::DB_PREFIX, $value );
		}

		return $config;
	}

	public function getUser( $username ) {
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			return false;
		}

		return [
			'user_id' => $username,
			'password' => $user->data->user_pass,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'email' => $user->user_email,
			'email_verified' => true,
			'scope' => '',
		];
	}

	public function setUser( $username, $password, $first_name = null, $last_name = null ) {
		$user = get_user_by( 'login', $username );

		if ( $user && wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
			// Update user data.
			return true;
		} else {
			// Create a user?
		}

		return false;
	}

	protected function hashPassword( $password ) {
		return wp_hash_password( $password );
	}

	public function get_create_tables_sql() {
		return array_filter( explode( ';', $this->getBuildSql() ), function ( $table ) {
			return ( false === strpos( $table, 'oauth_users' ) );
		} );
	}

	public function create_tables() {
		$created = [];
		$tables_sql = $this->get_create_tables_sql();

		foreach ( $tables_sql as $table_create_sql ) {
			$created[] = $this->wpdb->query( $table_create_sql );
		}

		return ! in_array( false, $created, true );
	}

}
