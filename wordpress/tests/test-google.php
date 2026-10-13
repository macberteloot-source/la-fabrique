<?php
/**
 * Tests de la connexion Google : vérification du jeton (clés RSA générées localement), cache des clés,
 * routes REST (création de compte, refus par e-mail, association, nonce, Origin, limitation de débit).
 *
 * Les clés publiques sont injectées par le filtre « fabrique_google_jwks » (réservé aux tests, ignoré en
 * production) ; le téléchargement des clés de Google est simulé avec « pre_http_request ».
 * Lancement : wp eval-file tests/test-google.php
 *
 * @package Fabrique\Tests
 */

use Fabrique\Boutique\Connexion_Google;
use Fabrique\Boutique\Jeton_Google;
use Fabrique\Boutique\Reglages;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

/**
 * Encodage base64url.
 *
 * @param string $octets Données.
 */
function fabrique_test_b64( string $octets ): string {
	return rtrim( strtr( base64_encode( $octets ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- fabrication de jetons de test.
}

/**
 * Deux paires de clés RSA générées pour la durée du test (la seconde joue l'attaquant ou la rotation).
 *
 * @return array{privee: OpenSSLAsymmetricKey, publique_pem: string, jwk: array, privee2: OpenSSLAsymmetricKey, jwk2: array}
 */
function fabrique_test_cles(): array {
	static $cles = null;
	if ( null === $cles ) {
		$fabriquer                  = static function ( string $kid ) {
			$privee  = openssl_pkey_new(
				array(
					'private_key_bits' => 2048,
					'private_key_type' => OPENSSL_KEYTYPE_RSA,
				)
			);
			$details = openssl_pkey_get_details( $privee );
			return array(
				$privee,
				$details['key'],
				array(
					'kty' => 'RSA',
					'alg' => 'RS256',
					'use' => 'sig',
					'kid' => $kid,
					'n'   => fabrique_test_b64( $details['rsa']['n'] ),
					'e'   => fabrique_test_b64( $details['rsa']['e'] ),
				),
			);
		};
		list( $privee, $pem, $jwk ) = $fabriquer( 'cle-test-1' );
		list( $privee2, , $jwk2 )   = $fabriquer( 'cle-test-2' );
		$cles                       = array(
			'privee'       => $privee,
			'publique_pem' => $pem,
			'jwk'          => $jwk,
			'privee2'      => $privee2,
			'jwk2'         => $jwk2,
		);
	}
	return $cles;
}

/**
 * Identifiant client utilisé par les tests.
 */
function fabrique_test_client(): string {
	return Reglages::google_client_id();
}

/**
 * Revendications d'un jeton valide, avec surcharges (null = revendication retirée).
 *
 * @param array<string, mixed> $surcharges Valeurs à remplacer.
 * @return array<string, mixed>
 */
function fabrique_test_revendications( array $surcharges = array() ): array {
	$revendications = array_merge(
		array(
			'iss'            => 'https://accounts.google.com',
			'aud'            => fabrique_test_client(),
			'sub'            => '110000000000000000001',
			'email'          => 'camille.test@example.com',
			'email_verified' => true,
			'given_name'     => 'Camille',
			'family_name'    => 'Test',
			'iat'            => time() - 10,
			'exp'            => time() + 3600,
			'nonce'          => 'nonce-de-test',
		),
		$surcharges
	);
	return array_filter(
		$revendications,
		static function ( $valeur ) {
			return null !== $valeur;
		}
	);
}

/**
 * Fabrique un jeton signé.
 *
 * @param array<string, mixed>      $revendications Revendications.
 * @param array<string, mixed>      $entete         Surcharges de l'en-tête.
 * @param OpenSSLAsymmetricKey|null $cle            Clé privée (par défaut, la clé 1).
 */
function fabrique_test_jeton( array $revendications, array $entete = array(), $cle = null ): string {
	$entete  = array_filter(
		array_merge(
			array(
				'alg' => 'RS256',
				'kid' => 'cle-test-1',
				'typ' => 'JWT',
			),
			$entete
		),
		static function ( $valeur ) {
			return null !== $valeur;
		}
	);
	$donnees = fabrique_test_b64( wp_json_encode( $entete ) ) . '.' . fabrique_test_b64( wp_json_encode( $revendications ) );

	switch ( $entete['alg'] ?? '' ) {
		case 'RS256':
			openssl_sign( $donnees, $signature, $cle ?? fabrique_test_cles()['privee'], OPENSSL_ALGO_SHA256 );
			break;
		case 'HS256':
			// Attaque classique : la clé publique (connue de tous) utilisée comme secret HMAC.
			$signature = hash_hmac( 'sha256', $donnees, fabrique_test_cles()['publique_pem'], true );
			break;
		default:
			$signature = 'signature';
	}
	return $donnees . '.' . fabrique_test_b64( $signature );
}

/**
 * Vérifie un jeton avec le nonce de test.
 *
 * @param string $jeton Jeton.
 * @return array<string, mixed>|WP_Error
 */
function fabrique_test_verifier( string $jeton ) {
	return Jeton_Google::verifier( $jeton, fabrique_test_client(), Jeton_Google::hacher_nonce( 'nonce-de-test' ) );
}

/**
 * Injecte les clés de test (filtre réservé aux tests).
 */
function fabrique_test_injecter_cles(): array {
	return array( 'keys' => array( fabrique_test_cles()['jwk'] ) );
}

/**
 * Valeur actuelle du cookie du nonce (empreinte), ou chaîne vide.
 */
function fabrique_test_cookie(): string {
	return isset( $_COOKIE[ Connexion_Google::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ Connexion_Google::COOKIE ] ) ) : '';
}

/**
 * POST sur une route Google.
 *
 * @param string               $route   Route (connexion | association).
 * @param string               $jeton   Jeton.
 * @param array<string,string> $entetes En-têtes (Origin du site par défaut).
 */
function fabrique_test_post( string $route, string $jeton, ?array $entetes = null ): WP_REST_Response {
	$requete = new WP_REST_Request( 'POST', '/fabrique/v1/google/' . $route );
	$requete->set_body_params( array( 'credential' => $jeton ) );
	$entetes = $entetes ?? array( 'origin' => Connexion_Google::origine( home_url() ) );
	foreach ( $entetes as $nom => $valeur ) {
		$requete->set_header( $nom, $valeur );
	}
	return rest_ensure_response( rest_do_request( $requete ) );
}

/**
 * Adresse IPv6 aléatoire (plage de documentation) : chaque essai a son propre compteur de débit,
 * même si les tests sont relancés plusieurs fois en dix minutes.
 */
function fabrique_test_ip(): string {
	return sprintf( '2001:db8::%x:%x:%x', wp_rand( 1, 0xffff ), wp_rand( 1, 0xffff ), wp_rand( 1, 0xffff ) );
}

/**
 * Prépare une requête « navigateur » : nouvelle IP de test, nonce émis (cookie), jeton portant ce nonce.
 *
 * @param array<string, mixed> $surcharges Revendications.
 */
function fabrique_test_jeton_navigateur( array $surcharges = array() ): string {
	$_SERVER['REMOTE_ADDR'] = fabrique_test_ip();
	$nonce                  = Connexion_Google::emettre_nonce();
	return fabrique_test_jeton( fabrique_test_revendications( array_merge( array( 'nonce' => $nonce ), $surcharges ) ) );
}

// ------------------------------------------------------------------ Préparation.

$fabrique_test_reglages = get_option( Reglages::OPTION );
if ( ! Reglages::google_client_id() ) {
	update_option( Reglages::OPTION, array_merge( Reglages::tout(), array( 'google_client_id' => 'test-fabrique.apps.googleusercontent.com' ) ) );
}
add_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );
add_filter( 'pre_wp_mail', '__return_false' ); // Pas d'e-mail « nouveau compte » pendant les tests.
add_filter( 'send_auth_cookies', '__return_false' ); // Pas d'en-tête Set-Cookie en ligne de commande.
wp_set_current_user( 0 );

fabrique_test_groupe( 'Vérification du jeton (RS256 maison)' );

fabrique_test(
	'jeton valide : revendications renvoyées (iss avec ou sans https://)',
	function () {
		$resultat = fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications() ) );
		fabrique_vrai( is_array( $resultat ), 'refusé : ' . ( is_wp_error( $resultat ) ? $resultat->get_error_code() : '' ) );
		fabrique_egal( '110000000000000000001', $resultat['sub'] );
		fabrique_vrai( is_array( fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'iss' => 'accounts.google.com' ) ) ) ) ), 'iss sans https refusé' );
	}
);

fabrique_test(
	'en-tête alg : « none », HS256 (clé publique comme secret) et RS512 refusés',
	function () {
		foreach ( array( 'none', 'HS256', 'RS512', '' ) as $alg ) {
			fabrique_erreur_wp( 'fabrique_jeton_alg', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'alg' => $alg ) ) ), "alg={$alg}" );
		}
		fabrique_erreur_wp( 'fabrique_jeton_alg', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'alg' => null ) ) ), 'alg absent' );
	}
);

fabrique_test(
	'kid inconnu ou absent refusé',
	function () {
		fabrique_erreur_wp( 'fabrique_jeton_kid', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'kid' => 'inconnu' ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_kid', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'kid' => null ) ) ) );
	}
);

fabrique_test(
	'signature : autre clé privée, charge modifiée après signature',
	function () {
		$cles = fabrique_test_cles();
		fabrique_erreur_wp( 'fabrique_jeton_signature', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array(), $cles['privee2'] ) ) );

		$morceaux    = explode( '.', fabrique_test_jeton( fabrique_test_revendications() ) );
		$morceaux[1] = fabrique_test_b64( wp_json_encode( fabrique_test_revendications( array( 'email' => 'pirate@example.com' ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_signature', fabrique_test_verifier( implode( '.', $morceaux ) ) );
	}
);

fabrique_test(
	'aud : autre identifiant client, tableau → refusés',
	function () {
		fabrique_erreur_wp( 'fabrique_jeton_aud', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'aud' => 'autre.apps.googleusercontent.com' ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_aud', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'aud' => array( fabrique_test_client() ) ) ) ) ) );
	}
);

fabrique_test(
	'iss étranger refusé',
	function () {
		fabrique_erreur_wp( 'fabrique_jeton_iss', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'iss' => 'https://accounts.google.com.example' ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_iss', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'iss' => null ) ) ) ) );
	}
);

fabrique_test(
	'exp / iat : 5 minutes de tolérance, pas plus',
	function () {
		fabrique_vrai( is_array( fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'exp' => time() - 240 ) ) ) ) ), 'exp dans la tolérance refusé' );
		fabrique_erreur_wp( 'fabrique_jeton_exp', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'exp' => time() - 310 ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_exp', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'exp' => null ) ) ) ) );
		fabrique_vrai( is_array( fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'iat' => time() + 240 ) ) ) ) ), 'iat dans la tolérance refusé' );
		fabrique_erreur_wp( 'fabrique_jeton_iat', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'iat' => time() + 310 ) ) ) ) );
	}
);

fabrique_test(
	'nonce : différent, absent, ou aucun nonce attendu → refusé',
	function () {
		fabrique_erreur_wp( 'fabrique_jeton_nonce', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'nonce' => 'autre' ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_nonce', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'nonce' => null ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_nonce', Jeton_Google::verifier( fabrique_test_jeton( fabrique_test_revendications() ), fabrique_test_client(), '' ) );
		// Le cookie contient une empreinte HMAC : le nonce en clair ne suffit pas.
		fabrique_erreur_wp( 'fabrique_jeton_nonce', Jeton_Google::verifier( fabrique_test_jeton( fabrique_test_revendications() ), fabrique_test_client(), 'nonce-de-test' ) );
	}
);

fabrique_test(
	'email_verified : false ou absent refusé ; e-mail invalide refusé',
	function () {
		fabrique_erreur_wp( 'fabrique_jeton_email_non_verifie', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'email_verified' => false ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_email_non_verifie', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'email_verified' => null ) ) ) ) );
		fabrique_erreur_wp( 'fabrique_jeton_email', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications( array( 'email' => 'pas-un-email' ) ) ) ) );
	}
);

fabrique_test(
	'format : morceaux manquants, JSON invalide, jeton trop long, identifiant client non configuré',
	function () {
		foreach ( array( 'abc', 'a.b', 'a.b.c.d', 'e30.e30.', 'pas du tout un jeton', 'YQ.YQ.YQ' ) as $jeton ) {
			fabrique_erreur_wp( 'fabrique_jeton_format', fabrique_test_verifier( $jeton ), $jeton );
		}
		fabrique_erreur_wp( 'fabrique_jeton_format', fabrique_test_verifier( str_repeat( 'a', 5000 ) . '.b.c' ) );
		fabrique_erreur_wp( 'fabrique_google_non_configure', Jeton_Google::verifier( fabrique_test_jeton( fabrique_test_revendications() ), '', 'x' ) );
	}
);

fabrique_test(
	'JWK → PEM : la clé reconstruite (DER fait main) est identique à l’originale',
	function () {
		$jwk     = fabrique_test_cles()['jwk'];
		$pem     = Jeton_Google::jwk_vers_pem( $jwk['n'], $jwk['e'] );
		$details = openssl_pkey_get_details( openssl_pkey_get_public( $pem ) );
		fabrique_egal( $jwk['n'], fabrique_test_b64( $details['rsa']['n'] ) );
		fabrique_egal( $jwk['e'], fabrique_test_b64( $details['rsa']['e'] ) );
		fabrique_egal( '', Jeton_Google::jwk_vers_pem( '', 'AQAB' ) );
	}
);

fabrique_test_groupe( 'Clés publiques de Google : cache et rechargement' );

fabrique_test(
	'durée du cache d’après Cache-Control (bornée entre 5 min et 1 jour)',
	function () {
		fabrique_egal( 21600, Jeton_Google::duree_cache( 'public, max-age=21600, must-revalidate, no-transform' ) );
		fabrique_egal( 300, Jeton_Google::duree_cache( 'max-age=10' ) );
		fabrique_egal( DAY_IN_SECONDS, Jeton_Google::duree_cache( 'max-age=9999999' ) );
		fabrique_egal( HOUR_IN_SECONDS, Jeton_Google::duree_cache( 'no-cache' ) );
	}
);

fabrique_test(
	'JWKS téléchargé une fois puis lu en cache ; kid inconnu → un seul rechargement ; rotation de clé',
	function () {
		remove_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );
		delete_transient( Jeton_Google::TRANSIENT );
		delete_transient( Jeton_Google::VERROU );

		$appels   = 0;
		$publiees = array( fabrique_test_cles()['jwk'] );
		$google   = static function ( $pre, $args, $url ) use ( &$appels, &$publiees ) {
			if ( Jeton_Google::URL_CLES !== $url ) {
				return $pre;
			}
			++$appels;
			return array(
				'headers'  => array( 'cache-control' => 'public, max-age=21600, must-revalidate, no-transform' ),
				'body'     => wp_json_encode( array( 'keys' => $publiees ) ),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};
		add_filter( 'pre_http_request', $google, 10, 3 );

		try {
			fabrique_vrai( is_array( fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications() ) ) ), 'jeton valide refusé' );
			fabrique_egal( 1, $appels, 'premier téléchargement :' );
			$expiration = (int) get_option( '_transient_timeout_' . Jeton_Google::TRANSIENT );
			fabrique_vrai( ! wp_using_ext_object_cache() ? abs( $expiration - time() - 21600 ) < 5 : true, 'durée du transient ≠ max-age' );

			fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications() ) );
			fabrique_egal( 1, $appels, 'deuxième vérification servie par le cache :' );

			fabrique_erreur_wp( 'fabrique_jeton_kid', fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'kid' => 'inconnu' ) ) ) );
			fabrique_egal( 2, $appels, 'kid inconnu → un rechargement :' );
			fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications(), array( 'kid' => 'encore-inconnu' ) ) );
			fabrique_egal( 2, $appels, 'pas de rafale de rechargements (verrou d’une minute) :' );

			// Rotation : Google publie une nouvelle clé ; après le verrou, elle est trouvée au premier jeton.
			delete_transient( Jeton_Google::VERROU );
			$publiees[] = fabrique_test_cles()['jwk2'];
			$jeton2     = fabrique_test_jeton( fabrique_test_revendications(), array( 'kid' => 'cle-test-2' ), fabrique_test_cles()['privee2'] );
			fabrique_vrai( is_array( fabrique_test_verifier( $jeton2 ) ), 'nouvelle clé non prise en compte' );
			fabrique_egal( 3, $appels );
		} finally {
			remove_filter( 'pre_http_request', $google, 10 );
			delete_transient( Jeton_Google::TRANSIENT );
			delete_transient( Jeton_Google::VERROU );
			add_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );
		}
	}
);

fabrique_test(
	'Google injoignable et aucun cache : refus propre (kid introuvable)',
	function () {
		remove_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );
		delete_transient( Jeton_Google::TRANSIENT );
		delete_transient( Jeton_Google::VERROU );
		$panne = static function ( $pre, $args, $url ) {
			return Jeton_Google::URL_CLES === $url ? new WP_Error( 'http_request_failed', 'injoignable' ) : $pre;
		};
		add_filter( 'pre_http_request', $panne, 10, 3 );
		$resultat = fabrique_test_verifier( fabrique_test_jeton( fabrique_test_revendications() ) );
		remove_filter( 'pre_http_request', $panne, 10 );
		delete_transient( Jeton_Google::VERROU );
		add_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );
		fabrique_erreur_wp( 'fabrique_jeton_kid', $resultat );
	}
);

fabrique_test_groupe( 'Routes REST et comptes' );

$fabrique_test_utilisateurs = array();

fabrique_test(
	'Origin absent ou étranger → 403',
	function () {
		$jeton = fabrique_test_jeton_navigateur();
		fabrique_egal( 403, fabrique_test_post( 'connexion', $jeton, array() )->get_status() );
		fabrique_egal( 403, fabrique_test_post( 'connexion', $jeton, array( 'origin' => 'https://pirate.example' ) )->get_status() );
		fabrique_egal( 'fabrique_origine', fabrique_test_post( 'connexion', $jeton, array( 'origin' => 'null' ) )->get_data()['code'] );
	}
);

fabrique_test(
	'nouveau compte : rôle customer, prénom et nom de Google, sub enregistré, session ouverte',
	function () use ( &$fabrique_test_utilisateurs ) {
		$email   = 'camille.' . wp_rand() . '@example.com';
		$reponse = fabrique_test_post( 'connexion', fabrique_test_jeton_navigateur( array( 'email' => $email ) ) );
		fabrique_egal( 200, $reponse->get_status(), fabrique_test_exporter( $reponse->get_data() ) );
		fabrique_egal( array( 'succes' => true ), $reponse->get_data() );

		$utilisateur = get_user_by( 'email', $email );
		fabrique_vrai( $utilisateur instanceof WP_User, 'compte non créé' );
		$fabrique_test_utilisateurs[] = $utilisateur->ID;
		fabrique_egal( array( 'customer' ), $utilisateur->roles );
		fabrique_egal( 'Camille', $utilisateur->first_name );
		fabrique_egal( 'Test', $utilisateur->last_name );
		fabrique_egal( '110000000000000000001', get_user_meta( $utilisateur->ID, Connexion_Google::META, true ) );
		fabrique_egal( $utilisateur->ID, get_current_user_id() );
		wp_set_current_user( 0 );
	}
);

fabrique_test(
	'rejeu : même jeton sans cookie, ou avec l’ancien cookie → 401 et nouveau nonce proposé',
	function () {
		$jeton   = fabrique_test_jeton_navigateur( array( 'sub' => '110000000000000000001' ) );
		$cookie  = fabrique_test_cookie();
		$premier = fabrique_test_post( 'connexion', $jeton );
		fabrique_egal( 200, $premier->get_status() );
		wp_set_current_user( 0 );

		$sans_cookie = fabrique_test_post( 'connexion', $jeton );
		fabrique_egal( 401, $sans_cookie->get_status() );
		fabrique_egal( 'fabrique_nonce_absent', $sans_cookie->get_data()['code'] );
		fabrique_vrai( ! empty( $sans_cookie->get_data()['data']['nonce'] ), 'pas de nouveau nonce' );

		$_COOKIE[ Connexion_Google::COOKIE ] = $cookie; // Cookie intercepté et rejoué.
		fabrique_egal( 401, fabrique_test_post( 'connexion', $jeton )->get_status() );
		fabrique_egal( 0, get_current_user_id() );
	}
);

fabrique_test(
	'compte existant avec la même adresse : refus 409 avec le message prévu, aucune liaison',
	function () use ( &$fabrique_test_utilisateurs ) {
		$email                        = 'dominique.' . wp_rand() . '@example.com';
		$existant                     = wp_insert_user(
			array(
				'user_login' => 'dominique' . wp_rand(),
				'user_email' => $email,
				'user_pass'  => wp_generate_password(),
				'role'       => 'customer',
			)
		);
		$fabrique_test_utilisateurs[] = $existant;

		$reponse = fabrique_test_post(
			'connexion',
			fabrique_test_jeton_navigateur(
				array(
					'email' => $email,
					'sub'   => '110000000000000000002',
				)
			)
		);
		fabrique_egal( 409, $reponse->get_status() );
		fabrique_egal( 'fabrique_compte_existant', $reponse->get_data()['code'] );
		fabrique_egal( 'Un compte existe déjà avec cette adresse. Connectez-vous avec votre mot de passe (ou « Mot de passe oublié ? »), puis associez Google dans Mon compte → Connexions.', $reponse->get_data()['message'] );
		fabrique_egal( '', get_user_meta( $existant, Connexion_Google::META, true ) );
		fabrique_egal( 0, Connexion_Google::utilisateur_par_sub( '110000000000000000002' ) );
		fabrique_egal( 0, get_current_user_id() );
	}
);

fabrique_test(
	'association depuis Mon compte, puis connexion par Google du compte existant',
	function () use ( &$fabrique_test_utilisateurs ) {
		$dominique = end( $fabrique_test_utilisateurs );
		wp_set_current_user( $dominique );
		$reponse = fabrique_test_post( 'association', fabrique_test_jeton_navigateur( array( 'sub' => '110000000000000000002' ) ) );
		fabrique_egal( 200, $reponse->get_status(), fabrique_test_exporter( $reponse->get_data() ) );
		fabrique_egal( '110000000000000000002', get_user_meta( $dominique, Connexion_Google::META, true ) );
		wp_set_current_user( 0 );

		$connexion = fabrique_test_post( 'connexion', fabrique_test_jeton_navigateur( array( 'sub' => '110000000000000000002' ) ) );
		fabrique_egal( 200, $connexion->get_status() );
		fabrique_egal( $dominique, get_current_user_id() );
		wp_set_current_user( 0 );
	}
);

fabrique_test(
	'association refusée : sub déjà lié à un autre compte ; autre Google déjà associé ; visiteur non connecté',
	function () use ( &$fabrique_test_utilisateurs ) {
		list( $camille, $dominique ) = $fabrique_test_utilisateurs;

		wp_set_current_user( $camille );
		$deja = fabrique_test_post( 'association', fabrique_test_jeton_navigateur( array( 'sub' => '110000000000000000002' ) ) );
		fabrique_egal( 409, $deja->get_status() );
		fabrique_egal( 'fabrique_google_deja_associe', $deja->get_data()['code'] );

		wp_set_current_user( $dominique );
		$autre = fabrique_test_post( 'association', fabrique_test_jeton_navigateur( array( 'sub' => '110000000000000000003' ) ) );
		fabrique_egal( 'fabrique_autre_google', $autre->get_data()['code'] );
		fabrique_egal( '110000000000000000002', get_user_meta( $dominique, Connexion_Google::META, true ) );

		wp_set_current_user( 0 );
		fabrique_egal( 401, fabrique_test_post( 'association', fabrique_test_jeton_navigateur() )->get_status() );
	}
);

fabrique_test(
	'connexion demandée par un utilisateur déjà connecté → 400',
	function () use ( &$fabrique_test_utilisateurs ) {
		wp_set_current_user( $fabrique_test_utilisateurs[0] );
		fabrique_egal( 'fabrique_deja_connecte', fabrique_test_post( 'connexion', fabrique_test_jeton_navigateur() )->get_data()['code'] );
		wp_set_current_user( 0 );
	}
);

fabrique_test(
	'jeton refusé : 401, code public générique (détail seulement dans le journal), nouveau nonce et cookie',
	function () {
		$reponse = fabrique_test_post( 'connexion', fabrique_test_jeton_navigateur( array( 'aud' => 'autre.apps.googleusercontent.com' ) ) );
		fabrique_egal( 401, $reponse->get_status() );
		fabrique_egal( 'fabrique_google_refuse', $reponse->get_data()['code'] );
		$nonce = $reponse->get_data()['data']['nonce'];
		fabrique_vrai( 1 === preg_match( '/^[a-f0-9]{32}$/', $nonce ), 'nonce absent' );
		fabrique_egal( Jeton_Google::hacher_nonce( $nonce ), fabrique_test_cookie() );
	}
);

fabrique_test(
	'adresse non vérifiée par Google : aucun compte créé',
	function () {
		$email   = 'non.verifiee.' . wp_rand() . '@example.com';
		$reponse = fabrique_test_post(
			'connexion',
			fabrique_test_jeton_navigateur(
				array(
					'email'          => $email,
					'sub'            => '110000000000000000009',
					'email_verified' => false,
				)
			)
		);
		fabrique_egal( 401, $reponse->get_status() );
		fabrique_egal( false, get_user_by( 'email', $email ) );
	}
);

fabrique_test(
	'limitation de débit : 10 tentatives par IP, la 11e → 429',
	function () {
		$_SERVER['REMOTE_ADDR'] = fabrique_test_ip();
		$statuts                = array();
		for ( $i = 0; $i < 11; $i++ ) {
			unset( $_COOKIE[ Connexion_Google::COOKIE ] );
			$statuts[] = fabrique_test_post( 'connexion', fabrique_test_jeton( fabrique_test_revendications() ) )->get_status();
		}
		fabrique_egal( array_merge( array_fill( 0, 10, 401 ), array( 429 ) ), $statuts );
	}
);

fabrique_test(
	'sans identifiant client : routes 404, onglet « Connexions » absent du menu',
	function () {
		$menu = array(
			'dashboard'       => 'Tableau de bord',
			'customer-logout' => 'Déconnexion',
		);
		fabrique_egal( array( 'dashboard', 'connexions', 'customer-logout' ), array_keys( Connexion_Google::menu_compte( $menu ) ) );

		if ( Reglages::google_client_id_par_constante() ) {
			fabrique_test_ligne( '      (identifiant défini par constante : cas « non configuré » non testable ici)' );
			return;
		}
		$avant = get_option( Reglages::OPTION );
		update_option( Reglages::OPTION, array_merge( Reglages::tout(), array( 'google_client_id' => '' ) ) );
		$statut = fabrique_test_post( 'connexion', fabrique_test_jeton_navigateur() )->get_status();
		$items  = array_keys( Connexion_Google::menu_compte( $menu ) );
		fabrique_test_restaurer_option( Reglages::OPTION, $avant );
		fabrique_egal( 404, $statut );
		fabrique_egal( array( 'dashboard', 'customer-logout' ), $items );
	}
);

fabrique_test(
	'dissociation sans nonce valide : refusée, association conservée',
	function () use ( &$fabrique_test_utilisateurs ) {
		$dominique = $fabrique_test_utilisateurs[1];
		wp_set_current_user( $dominique );
		$_POST['fabrique_google_dissocier'] = '1';
		$_POST['_fabrique_nonce']           = 'faux';
		Connexion_Google::traiter_dissociation();
		unset( $_POST['fabrique_google_dissocier'], $_POST['_fabrique_nonce'] );
		wc_clear_notices();
		wp_set_current_user( 0 );
		fabrique_egal( '110000000000000000002', get_user_meta( $dominique, Connexion_Google::META, true ) );
	}
);

// ------------------------------------------------------------------ Nettoyage.
foreach ( $fabrique_test_utilisateurs as $fabrique_test_utilisateur ) {
	wp_delete_user( $fabrique_test_utilisateur );
}
fabrique_test_restaurer_option( Reglages::OPTION, $fabrique_test_reglages );
remove_filter( 'fabrique_google_jwks', 'fabrique_test_injecter_cles' );

fabrique_test_fin();
