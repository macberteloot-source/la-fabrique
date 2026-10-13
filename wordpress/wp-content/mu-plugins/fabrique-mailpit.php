<?php
/**
 * Plugin Name: La Fabrique — e-mails vers Mailpit (développement)
 * Description: Hors production, envoie les e-mails de WordPress et WooCommerce au serveur SMTP Mailpit (interface : http://localhost:8025).
 * Version:     1.0.0
 * Author:      La Fabrique (projet de démonstration)
 * License:     GPL-2.0-or-later
 *
 * Actif seulement si l'environnement n'est pas « production » et si FABRIQUE_SMTP_HOST est défini
 * (variable d'environnement fournie par docker-compose.yml, ou constante dans wp-config.php).
 *
 * @package Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hôte SMTP de développement (constante prioritaire, sinon variable d'environnement), ou chaîne vide.
 */
function fabrique_mailpit_hote(): string {
	if ( 'production' === wp_get_environment_type() ) {
		return '';
	}
	return defined( 'FABRIQUE_SMTP_HOST' ) ? (string) constant( 'FABRIQUE_SMTP_HOST' ) : (string) getenv( 'FABRIQUE_SMTP_HOST' );
}

/**
 * Configure PHPMailer pour Mailpit : SMTP sans authentification ni chiffrement (réseau Docker interne).
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Instance utilisée par wp_mail().
 */
function fabrique_mailpit_configurer( $phpmailer ): void {
	$hote = fabrique_mailpit_hote();
	if ( '' === $hote ) {
		return;
	}
	$port = defined( 'FABRIQUE_SMTP_PORT' ) ? (int) constant( 'FABRIQUE_SMTP_PORT' ) : (int) getenv( 'FABRIQUE_SMTP_PORT' );

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- propriétés de PHPMailer.
	$phpmailer->isSMTP();
	$phpmailer->Host        = $hote;
	$phpmailer->Port        = $port > 0 ? $port : 1025;
	$phpmailer->SMTPAuth    = false;
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->SMTPSecure  = '';
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
add_action( 'phpmailer_init', 'fabrique_mailpit_configurer' );

/**
 * Expéditeur par défaut valide : « wordpress@localhost » est refusé par PHPMailer (domaine sans point).
 *
 * @param string $expediteur Adresse proposée par WordPress.
 */
function fabrique_mailpit_expediteur( $expediteur ): string {
	$expediteur = (string) $expediteur;
	$domaine    = substr( (string) strrchr( $expediteur, '@' ), 1 );
	if ( '' !== fabrique_mailpit_hote() && ! str_contains( $domaine, '.' ) ) {
		return 'bonjour@la-fabrique.test';
	}
	return $expediteur;
}
add_filter( 'wp_mail_from', 'fabrique_mailpit_expediteur' );
