<?php
/**
 * Réglages WooCommerce de la démo (idempotent) : monnaie, pays, inscription,
 * zone « France métropolitaine » et ses trois modes de livraison, paiement à la livraison.
 *
 * Lancé par bin/setup.sh : wp eval-file bin/reglages.php
 *
 * @package Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Affiche une ligne dans le terminal (WP-CLI) ou ne fait rien.
 *
 * @param string $message Message.
 */
function fabrique_reglages_log( string $message ): void {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	}
}

/**
 * Options simples de WooCommerce (euro, France, prix TTC sans TVA, comptes clients).
 */
function fabrique_reglages_options(): void {
	$options = array(
		// Boutique : France, euro, format « 12,30 € ».
		'woocommerce_store_address'                  => 'Atelier à Fives',
		'woocommerce_store_city'                     => 'Lille',
		'woocommerce_default_country'                => 'FR',
		'woocommerce_allowed_countries'              => 'specific',
		'woocommerce_specific_allowed_countries'     => array( 'FR' ),
		'woocommerce_ship_to_countries'              => '',
		'woocommerce_default_customer_address'       => 'base',
		'woocommerce_currency'                       => 'EUR',
		'woocommerce_currency_pos'                   => 'right_space',
		'woocommerce_price_thousand_sep'             => ' ',
		'woocommerce_price_decimal_sep'              => ',',
		'woocommerce_price_num_decimals'             => 2,
		'woocommerce_weight_unit'                    => 'kg',
		'woocommerce_dimension_unit'                 => 'cm',
		// Prix TTC, pas de calcul de TVA (démo).
		'woocommerce_calc_taxes'                     => 'no',
		'woocommerce_prices_include_tax'             => 'yes',
		'woocommerce_tax_display_shop'               => 'incl',
		'woocommerce_tax_display_cart'               => 'incl',
		// Panier : ajout en AJAX sur les listes, frais de port visibles avant l'adresse.
		// Stock : géré, réservé 60 min pour les commandes en attente de paiement, affiché seulement quand il est bas.
		'woocommerce_manage_stock'                   => 'yes',
		'woocommerce_hold_stock_minutes'             => 60,
		'woocommerce_notify_low_stock'               => 'yes',
		'woocommerce_notify_no_stock'                => 'yes',
		'woocommerce_stock_format'                   => 'low_amount',
		'woocommerce_enable_ajax_add_to_cart'        => 'yes',
		'woocommerce_cart_redirect_after_add'        => 'no',
		'woocommerce_enable_shipping_calc'           => 'yes',
		'woocommerce_shipping_cost_requires_address' => 'no',
		// Comptes : inscription sur « Mon compte » et au checkout, mot de passe généré et envoyé par e-mail.
		'woocommerce_enable_guest_checkout'          => 'yes',
		'woocommerce_enable_checkout_login_reminder' => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_enable_myaccount_registration'  => 'yes',
		'woocommerce_registration_generate_username' => 'yes',
		'woocommerce_registration_generate_password' => 'yes',
		// E-mails.
		'woocommerce_email_from_name'                => 'La Fabrique',
		'woocommerce_email_from_address'             => 'bonjour@la-fabrique.test',
		// Boutique ouverte (WooCommerce 9.1+ active un mode « bientôt disponible » à l'installation).
		'woocommerce_coming_soon'                    => 'no',
		'woocommerce_store_pages_only'               => 'no',
		// Administration épurée : pas d'assistant ni de suivi.
		'woocommerce_onboarding_profile'             => array( 'skipped' => true ),
		'woocommerce_task_list_hidden'               => 'yes',
		'woocommerce_show_marketplace_suggestions'   => 'no',
		'woocommerce_allow_tracking'                 => 'no',
	);

	foreach ( $options as $nom => $valeur ) {
		update_option( $nom, $valeur );
	}
	fabrique_reglages_log( '  options WooCommerce : ' . count( $options ) . ' réglages appliqués' );
}

/**
 * Zone « France métropolitaine » : Colissimo 5,90 €, livraison offerte dès 45 €, retrait à l'atelier.
 */
function fabrique_reglages_livraison(): void {
	$nom_zone = 'France métropolitaine';
	$zone     = null;

	foreach ( WC_Shipping_Zones::get_zones() as $donnees ) {
		if ( $nom_zone === $donnees['zone_name'] ) {
			$zone = new WC_Shipping_Zone( (int) $donnees['id'] );
			break;
		}
	}
	if ( ! $zone ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $nom_zone );
		$zone->set_zone_order( 1 );
	}
	$zone->set_locations(
		array(
			array(
				'code' => 'FR',
				'type' => 'country',
			),
		)
	);
	$zone->save();

	$methodes = array(
		'flat_rate'     => array(
			'title'      => 'Colissimo',
			'tax_status' => 'none',
			'cost'       => '5.90',
		),
		'free_shipping' => array(
			'title'            => 'Livraison offerte',
			'requires'         => 'min_amount',
			'min_amount'       => '45',
			'ignore_discounts' => 'no',
		),
		'local_pickup'  => array(
			'title'      => 'Retrait à l’atelier',
			'tax_status' => 'none',
			'cost'       => '0',
		),
	);

	// Instances déjà présentes dans la zone, par type de méthode.
	$existantes = array();
	foreach ( $zone->get_shipping_methods() as $instance_id => $methode ) {
		$existantes[ $methode->id ] = (int) $instance_id;
	}

	foreach ( $methodes as $methode_id => $reglages ) {
		$instance_id = $existantes[ $methode_id ] ?? (int) $zone->add_shipping_method( $methode_id );
		if ( ! $instance_id ) {
			fabrique_reglages_log( "  ! impossible d’ajouter la méthode {$methode_id}" );
			continue;
		}
		$cle    = 'woocommerce_' . $methode_id . '_' . $instance_id . '_settings';
		$actuel = get_option( $cle, array() );
		update_option( $cle, array_merge( is_array( $actuel ) ? $actuel : array(), $reglages ) );
	}

	// Les transients de tarifs dépendent des réglages : on les invalide.
	WC_Cache_Helper::get_transient_version( 'shipping', true );
	fabrique_reglages_log( "  zone « {$nom_zone} » : Colissimo, Livraison offerte dès 45 €, Retrait à l’atelier" );
}

/**
 * Paiement : uniquement « Paiement à la livraison / au retrait » (cod).
 */
function fabrique_reglages_paiement(): void {
	update_option(
		'woocommerce_cod_settings',
		array(
			'enabled'            => 'yes',
			'title'              => 'Paiement à la livraison / au retrait',
			'description'        => 'Démo : aucun paiement n’est demandé. Vous réglez à réception du colis ou au retrait à l’atelier.',
			'instructions'       => 'Vous réglerez à réception du colis ou au retrait à l’atelier (démo : aucune commande n’est réellement expédiée).',
			'enable_for_methods' => array(),
			'enable_for_virtual' => 'yes',
		)
	);

	foreach ( array( 'bacs', 'cheque', 'paypal' ) as $passerelle ) {
		$reglages = get_option( 'woocommerce_' . $passerelle . '_settings', array() );
		if ( is_array( $reglages ) && $reglages ) {
			$reglages['enabled'] = 'no';
			update_option( 'woocommerce_' . $passerelle . '_settings', $reglages );
		}
	}
	update_option( 'woocommerce_gateway_order', array( 'cod' => 0 ) );
	fabrique_reglages_log( '  paiement : à la livraison / au retrait (cod) uniquement' );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'WooCommerce doit être actif avant bin/reglages.php.' );
	}
	return;
}

fabrique_reglages_log( 'Réglages WooCommerce…' );
fabrique_reglages_options();
fabrique_reglages_livraison();
fabrique_reglages_paiement();
