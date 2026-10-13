<?php
/**
 * Mini-bibliothèque d'assertions pour les tests lancés avec « wp eval-file » (PHPUnit n'est pas requis).
 *
 * Usage dans un fichier de test :
 *   require_once __DIR__ . '/lib.php';
 *   fabrique_test( 'nom du cas', function () { fabrique_egal( 2, 1 + 1 ); } );
 *   fabrique_test_fin();
 *
 * @package Fabrique\Tests
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compteurs partagés par les fonctions ci-dessous.
 *
 * @return array{reussis: int, echecs: int, erreurs: string[]}
 */
function &fabrique_test_etat(): array {
	static $etat = array(
		'reussis' => 0,
		'echecs'  => 0,
		'erreurs' => array(),
	);
	return $etat;
}

/**
 * Exécute un cas de test et affiche son résultat.
 *
 * @param string   $nom Nom du cas.
 * @param callable $cas Fonction contenant les assertions.
 */
function fabrique_test( string $nom, callable $cas ): void {
	$etat = &fabrique_test_etat();
	try {
		$cas();
		++$etat['reussis'];
		fabrique_test_ligne( "  \033[32m✓\033[0m {$nom}" );
	} catch ( Throwable $e ) {
		++$etat['echecs'];
		$detail            = $e instanceof AssertionError ? $e->getMessage() : get_class( $e ) . ' : ' . $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')';
		$etat['erreurs'][] = "{$nom} — {$detail}";
		fabrique_test_ligne( "  \033[31m✗ {$nom}\033[0m\n      {$detail}" );
	}
}

/**
 * Affiche une ligne.
 *
 * @param string $texte Texte.
 */
function fabrique_test_ligne( string $texte ): void {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::line( $texte );
	}
}

/**
 * Affiche un titre de groupe.
 *
 * @param string $titre Titre.
 */
function fabrique_test_groupe( string $titre ): void {
	fabrique_test_ligne( "\n\033[1m{$titre}\033[0m" );
}

/**
 * Représentation courte d'une valeur pour les messages.
 *
 * @param mixed $valeur Valeur.
 */
function fabrique_test_exporter( $valeur ): string {
	$texte = wp_json_encode( $valeur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$texte = false === $texte ? var_export( $valeur, true ) : $texte; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- message de test.
	return strlen( $texte ) > 300 ? substr( $texte, 0, 300 ) . '…' : $texte;
}

/**
 * Échec immédiat.
 *
 * @param string $message Message.
 * @throws AssertionError Toujours (erreur native de PHP, distinguée des erreurs inattendues).
 */
function fabrique_echec( string $message ): void {
	throw new AssertionError( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- message de test, affiché en console.
}

/**
 * Vérifie l'égalité stricte.
 *
 * @param mixed  $attendu Valeur attendue.
 * @param mixed  $obtenu  Valeur obtenue.
 * @param string $message Contexte.
 */
function fabrique_egal( $attendu, $obtenu, string $message = '' ): void {
	if ( $attendu !== $obtenu ) {
		fabrique_echec( trim( $message . ' attendu ' . fabrique_test_exporter( $attendu ) . ', obtenu ' . fabrique_test_exporter( $obtenu ) ) );
	}
}

/**
 * Vérifie qu'une condition est vraie.
 *
 * @param mixed  $condition Condition.
 * @param string $message   Contexte.
 */
function fabrique_vrai( $condition, string $message = '' ): void {
	if ( true !== (bool) $condition ) {
		fabrique_echec( '' !== $message ? $message : 'condition fausse' );
	}
}

/**
 * Vérifie qu'une valeur est une WP_Error portant le code attendu.
 *
 * @param string $code    Code attendu.
 * @param mixed  $valeur  Valeur obtenue.
 * @param string $message Contexte.
 */
function fabrique_erreur_wp( string $code, $valeur, string $message = '' ): void {
	if ( ! is_wp_error( $valeur ) ) {
		fabrique_echec( trim( $message . " erreur « {$code} » attendue, obtenu " . fabrique_test_exporter( $valeur ) ) );
	}
	fabrique_egal( $code, $valeur->get_error_code(), $message );
}

/**
 * Vérifie qu'une chaîne en contient une autre.
 *
 * @param string $aiguille Texte cherché.
 * @param string $texte    Texte.
 * @param string $message  Contexte.
 */
function fabrique_contient( string $aiguille, string $texte, string $message = '' ): void {
	if ( ! str_contains( $texte, $aiguille ) ) {
		fabrique_echec( trim( $message . ' « ' . $aiguille . ' » absent de ' . fabrique_test_exporter( $texte ) ) );
	}
}

/**
 * Remet une option dans son état d'avant le test (supprimée si elle n'existait pas).
 *
 * @param string $nom    Nom de l'option.
 * @param mixed  $valeur Valeur lue avant le test (false : option absente).
 */
function fabrique_test_restaurer_option( string $nom, $valeur ): void {
	if ( false === $valeur ) {
		delete_option( $nom );
		return;
	}
	update_option( $nom, $valeur );
}

/**
 * Affiche le bilan et sort avec un code d'erreur s'il y a des échecs.
 */
function fabrique_test_fin(): void {
	$etat = fabrique_test_etat();
	fabrique_test_ligne( sprintf( "\n%d réussi(s), %d échec(s)", $etat['reussis'], $etat['echecs'] ) );
	if ( $etat['echecs'] > 0 && class_exists( 'WP_CLI' ) ) {
		WP_CLI::halt( 1 );
	}
}
