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
		return explode( ';', $this->getBuildSql() );
	}

	public function create_tables() {
		$created = [];
		$tables_sql = $this->get_create_tables_sql();

		foreach ( $tables_sql as $table_create_sql ) {
			$created[] = $this->wpdb->query( $table_create_sql );
		}

		return ! in_array( false, $created, true );
	}

	public function getBuildSql( $table_name = 'unused' ) {
		$sql = "
			CREATE TABLE {$this->config['client_table']} (
			  client_id             VARCHAR(80)   NOT NULL,
			  client_secret         VARCHAR(80),
			  redirect_uri          VARCHAR(2000),
			  grant_types           VARCHAR(80),
			  scope                 VARCHAR(4000),
			  user_id               VARCHAR(80),
			  PRIMARY KEY (client_id)
			);

			CREATE TABLE {$this->config['access_token_table']} (
			  access_token         VARCHAR(40)    NOT NULL,
			  client_id            VARCHAR(80)    NOT NULL,
			  user_id              VARCHAR(80),
			  expires              TIMESTAMP      NOT NULL,
			  scope                VARCHAR(4000),
			  PRIMARY KEY (access_token)
			);

			CREATE TABLE {$this->config['code_table']} (
			  authorization_code  VARCHAR(40)    NOT NULL,
			  client_id           VARCHAR(80)    NOT NULL,
			  user_id             VARCHAR(80),
			  redirect_uri        VARCHAR(2000),
			  expires             TIMESTAMP      NOT NULL,
			  scope               VARCHAR(4000),
			  id_token            VARCHAR(1000)  NULL DEFAULT NULL,
			  PRIMARY KEY (authorization_code)
			);

			CREATE TABLE {$this->config['refresh_token_table']} (
			  refresh_token       VARCHAR(40)    NOT NULL,
			  client_id           VARCHAR(80)    NOT NULL,
			  user_id             VARCHAR(80),
			  expires             TIMESTAMP      NOT NULL,
			  scope               VARCHAR(4000),
			  PRIMARY KEY (refresh_token)
			);

			CREATE TABLE {$this->config['scope_table']} (
			  scope               VARCHAR(80)  NOT NULL,
			  is_default          BOOLEAN,
			  PRIMARY KEY (scope)
			);

			CREATE TABLE {$this->config['jwt_table']} (
			  client_id           VARCHAR(80)   NOT NULL,
			  subject             VARCHAR(80),
			  public_key          VARCHAR(2000) NOT NULL
			);

			CREATE TABLE {$this->config['jti_table']} (
			  issuer              VARCHAR(80)   NOT NULL,
			  subject             VARCHAR(80),
			  audiance            VARCHAR(80),
			  expires             TIMESTAMP     NOT NULL,
			  jti                 VARCHAR(2000) NOT NULL
			);

			CREATE TABLE {$this->config['public_key_table']} (
			  client_id            VARCHAR(80),
			  public_key           VARCHAR(2000),
			  private_key          VARCHAR(2000),
			  encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
			)
		";

		return $sql;
	}

}
