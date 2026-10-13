<?php
/**
 * Tests de la barre « livraison offerte » : seuil, calcul, textes, balisage, fragments, tarifs.
 *
 * Prérequis : bin/reglages.php et bin/seed.php (zone France métropolitaine, produits).
 * Lancement : wp eval-file tests/test-livraison.php
 *
 * @package Fabrique\Tests
 */

use Fabrique\Boutique\Livraison_Offerte;
use Fabrique\Boutique\Reglages;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';

/** Espace insécable placé par wc_price() entre le montant et le symbole. */
const FABRIQUE_TEST_NBSP = "\u{00A0}";

/**
 * Vide le panier et le remplit (sku => quantité).
 *
 * @param array<string, int> $articles Articles.
 */
function fabrique_test_panier( array $articles ): void {
	WC()->cart->empty_cart();
	foreach ( $articles as $sku => $quantite ) {
		WC()->cart->add_to_cart( wc_get_product_id_by_sku( $sku ), $quantite );
	}
	WC()->cart->calculate_totals();
}

/**
 * Instance de la méthode « Livraison offerte » de la zone France métropolitaine.
 */
function fabrique_test_methode_offerte(): WC_Shipping_Method {
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $methode ) {
			if ( 'free_shipping' === $methode->id ) {
				return $methode;
			}
		}
	}
	fabrique_echec( 'méthode free_shipping absente : lancer bin/reglages.php' );
}

/**
 * Change une option de la méthode « Livraison offerte » et renvoie l'ancienne valeur.
 *
 * @param string $option Option.
 * @param string $valeur Nouvelle valeur.
 */
function fabrique_test_option_offerte( string $option, string $valeur ): string {
	$methode             = fabrique_test_methode_offerte();
	$cle                 = $methode->get_instance_option_key();
	$reglages            = (array) get_option( $cle, array() );
	$ancienne            = (string) ( $reglages[ $option ] ?? '' );
	$reglages[ $option ] = $valeur;
	update_option( $cle, $reglages );
	return $ancienne;
}

wc_load_cart();
WC()->customer->set_shipping_country( 'FR' );

fabrique_test_groupe( 'Seuil et calcul' );

fabrique_test(
	'le seuil vient de la méthode « Livraison offerte » de la zone (45 €)',
	function () {
		$seuil = Livraison_Offerte::seuil();
		fabrique_egal( 45.0, $seuil['seuil'] );
		fabrique_egal( 'zone', $seuil['source'] );
		fabrique_egal( false, $seuil['ignorer_remises'] );
	}
);

fabrique_test(
	'min_amount modifié dans la zone → nouveau seuil',
	function () {
		$ancien = fabrique_test_option_offerte( 'min_amount', '60' );
		$seuil  = Livraison_Offerte::seuil()['seuil'];
		fabrique_test_option_offerte( 'min_amount', $ancien );
		fabrique_egal( 60.0, $seuil );
	}
);

fabrique_test(
	'zone sans livraison offerte (Belgique) → réglage du plugin (45 € puis 50 €)',
	function () {
		WC()->customer->set_shipping_country( 'BE' );
		$par_defaut = Livraison_Offerte::seuil();
		$avant      = get_option( Reglages::OPTION );
		update_option( Reglages::OPTION, array_merge( Reglages::tout(), array( 'seuil' => 50 ) ) );
		$regle = Livraison_Offerte::seuil();
		fabrique_test_restaurer_option( Reglages::OPTION, $avant );
		WC()->customer->set_shipping_country( 'FR' );

		fabrique_egal( 'reglage', $par_defaut['source'] );
		fabrique_egal( 45.0, $par_defaut['seuil'] );
		fabrique_egal( 50.0, $regle['seuil'] );
	}
);

fabrique_test(
	'panier vide : « Livraison offerte dès 45 € », progression 0',
	function () {
		fabrique_test_panier( array() );
		$etat = Livraison_Offerte::etat();
		fabrique_egal( 'Livraison offerte dès 45' . FABRIQUE_TEST_NBSP . '€', $etat['texte'] );
		fabrique_egal( 0, $etat['progression'] );
		fabrique_egal( false, $etat['atteint'] );
	}
);

fabrique_test(
	'32 € : « Plus que 13 € pour la livraison offerte », 71 %',
	function () {
		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		$etat = Livraison_Offerte::etat();
		fabrique_egal( 'Plus que 13' . FABRIQUE_TEST_NBSP . '€ pour la livraison offerte', $etat['texte'] );
		fabrique_egal( 71, $etat['progression'] );
	}
);

fabrique_test(
	'montant avec centimes : seuil 45,30 € et panier de 32 € → « Plus que 13,30 € » ; format « 12,30 € »',
	function () {
		$ancien = fabrique_test_option_offerte( 'min_amount', '45.30' );
		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		$texte = Livraison_Offerte::etat()['texte'];
		fabrique_test_option_offerte( 'min_amount', $ancien );
		fabrique_egal( 'Plus que 13,30' . FABRIQUE_TEST_NBSP . '€ pour la livraison offerte', $texte );
		fabrique_egal( '12,30' . FABRIQUE_TEST_NBSP . '€', Livraison_Offerte::montant( 12.3 ) );
		fabrique_egal( '45' . FABRIQUE_TEST_NBSP . '€', Livraison_Offerte::montant( 45.0 ) );
	}
);

fabrique_test(
	'remise de 10 % déduite comme WooCommerce, sauf si la méthode ignore les remises',
	function () {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'fabrique-test-10' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->save();

		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		WC()->cart->apply_coupon( 'fabrique-test-10' );
		WC()->cart->calculate_totals();
		$avec_remise = Livraison_Offerte::etat();

		$ancien      = fabrique_test_option_offerte( 'ignore_discounts', 'yes' );
		$sans_remise = Livraison_Offerte::etat();
		fabrique_test_option_offerte( 'ignore_discounts', $ancien );

		WC()->cart->remove_coupons();
		$coupon->delete( true );
		wc_clear_notices();

		fabrique_egal( 28.8, $avec_remise['total'] );
		fabrique_egal( 'Plus que 16,20' . FABRIQUE_TEST_NBSP . '€ pour la livraison offerte', $avec_remise['texte'] );
		fabrique_egal( 32.0, $sans_remise['total'] );
		fabrique_egal( 'Plus que 13' . FABRIQUE_TEST_NBSP . '€ pour la livraison offerte', $sans_remise['texte'] );
	}
);

fabrique_test(
	'seuil atteint (101 €) : « La livraison est offerte ✓ », 100 %, is-atteint',
	function () {
		fabrique_test_panier(
			array(
				'dripper-gres'  => 1,
				'moulin-manuel' => 1,
			)
		);
		$etat = Livraison_Offerte::etat();
		fabrique_egal( 'La livraison est offerte ✓', $etat['texte'] );
		fabrique_egal( 100, $etat['progression'] );
		fabrique_egal( true, $etat['atteint'] );
		fabrique_contient( 'class="lf-livraison is-atteint"', Livraison_Offerte::rendu() );
	}
);

fabrique_test(
	'pile au seuil (32 € pour un seuil de 32 €) : livraison offerte, comme WooCommerce (total ≥ min_amount)',
	function () {
		$ancien = fabrique_test_option_offerte( 'min_amount', '32' );
		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		$etat = Livraison_Offerte::etat();
		fabrique_test_option_offerte( 'min_amount', $ancien );
		fabrique_egal( true, $etat['atteint'] );
	}
);

fabrique_test(
	'fabrique_seuil_livraison_offerte() : « 45 € » (espace insécable), suit la zone, sinon le réglage',
	function () {
		fabrique_egal( '45' . FABRIQUE_TEST_NBSP . '€', fabrique_seuil_livraison_offerte() );

		$ancien = fabrique_test_option_offerte( 'min_amount', '60' );
		$zone   = fabrique_seuil_livraison_offerte();
		fabrique_test_option_offerte( 'min_amount', $ancien );
		fabrique_egal( '60' . FABRIQUE_TEST_NBSP . '€', $zone );

		WC()->customer->set_shipping_country( 'BE' );
		$avant = get_option( Reglages::OPTION );
		update_option( Reglages::OPTION, array_merge( Reglages::tout(), array( 'seuil' => 39.5 ) ) );
		$repli = fabrique_seuil_livraison_offerte();
		fabrique_test_restaurer_option( Reglages::OPTION, $avant );
		WC()->customer->set_shipping_country( 'FR' );
		fabrique_egal( '39,50' . FABRIQUE_TEST_NBSP . '€', $repli );
	}
);

fabrique_test_groupe( 'Balisage, action et fragments' );

fabrique_test(
	'balisage BEM du contrat (role status, progressbar, aria-valuenow, --lf-progression)',
	function () {
		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		$html  = Livraison_Offerte::rendu();
		$motif = '#^<div class="lf-livraison" role="status" data-lf-livraison><p class="lf-livraison__texte">Plus que 13.€ pour la livraison offerte</p>'
			. '<div class="lf-livraison__barre" role="progressbar" aria-label="[^"]+" aria-valuemin="0" aria-valuemax="100" aria-valuenow="71">'
			. '<span class="lf-livraison__jauge" style="--lf-progression: 71%"></span></div></div>$#u';
		fabrique_vrai( 1 === preg_match( $motif, $html ), 'balisage inattendu : ' . $html );
	}
);

fabrique_test(
	'do_action( « fabrique_livraison_barre », « entete » ) affiche la barre ; le filtre peut la masquer',
	function () {
		ob_start();
		do_action( 'fabrique_livraison_barre', 'entete' );
		$html = (string) ob_get_clean();
		fabrique_contient( 'data-lf-livraison', $html );

		$masquer = static function ( $afficher, $emplacement ) {
			return 'entete' === $emplacement ? false : $afficher;
		};
		add_filter( 'fabrique_livraison_barre_afficher', $masquer, 10, 2 );
		ob_start();
		do_action( 'fabrique_livraison_barre', 'entete' );
		$vide = (string) ob_get_clean();
		remove_filter( 'fabrique_livraison_barre_afficher', $masquer, 10 );
		fabrique_egal( '', $vide );
	}
);

fabrique_test(
	'fragments AJAX (ajout au panier, checkout) : clé div[data-lf-livraison] à jour',
	function () {
		fabrique_test_panier( array( 'moulin-manuel' => 1 ) );
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre de WooCommerce.
		fabrique_contient( 'La livraison est offerte ✓', (string) ( $fragments['div[data-lf-livraison]'] ?? '' ) );
		$checkout = apply_filters( 'woocommerce_update_order_review_fragments', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre de WooCommerce.
		fabrique_vrai( isset( $checkout['div[data-lf-livraison]'] ), 'fragment absent du checkout' );
	}
);

fabrique_test(
	'emplacements gérés par le plugin : panier, panier vide, commande, mini-panier',
	function () {
		foreach ( array( 'woocommerce_before_cart_table', 'woocommerce_cart_is_empty', 'woocommerce_checkout_before_order_review', 'woocommerce_before_mini_cart' ) as $crochet ) {
			fabrique_vrai( false !== has_action( $crochet ), "rien sur {$crochet}" );
		}
		fabrique_vrai( false !== has_action( 'wp_enqueue_scripts', array( Livraison_Offerte::class, 'charger_fragments' ) ), 'script des fragments non chargé' );
	}
);

fabrique_test_groupe( 'Tarifs quand la livraison est offerte' );

fabrique_test(
	'Colissimo payant masqué si « Livraison offerte » est disponible ; retrait conservé',
	function () {
		$colissimo = new WC_Shipping_Rate( 'flat_rate:1', 'Colissimo', 5.9, array(), 'flat_rate', 1 );
		$offerte   = new WC_Shipping_Rate( 'free_shipping:2', 'Livraison offerte', 0, array(), 'free_shipping', 2 );
		$retrait   = new WC_Shipping_Rate( 'local_pickup:3', 'Retrait', 0, array(), 'local_pickup', 3 );

		$avec = Livraison_Offerte::masquer_tarifs_payants(
			array(
				'flat_rate:1'     => $colissimo,
				'free_shipping:2' => $offerte,
				'local_pickup:3'  => $retrait,
			)
		);
		fabrique_egal( array( 'free_shipping:2', 'local_pickup:3' ), array_keys( $avec ) );

		$sans = Livraison_Offerte::masquer_tarifs_payants(
			array(
				'flat_rate:1'    => $colissimo,
				'local_pickup:3' => $retrait,
			)
		);
		fabrique_egal( array( 'flat_rate:1', 'local_pickup:3' ), array_keys( $sans ) );
	}
);

fabrique_test(
	'tarifs réels du panier : 32 € → Colissimo + retrait ; 101 € → offerte + retrait',
	function () {
		$methodes = static function () {
			WC()->shipping()->reset_shipping();
			WC()->cart->calculate_shipping();
			$ids = array();
			foreach ( WC()->shipping()->get_packages() as $colis ) {
				foreach ( $colis['rates'] as $tarif ) {
					$ids[] = $tarif->get_method_id();
				}
			}
			sort( $ids );
			return $ids;
		};
		fabrique_test_panier( array( 'dripper-gres' => 1 ) );
		fabrique_egal( array( 'flat_rate', 'local_pickup' ), $methodes() );
		fabrique_test_panier(
			array(
				'dripper-gres'  => 1,
				'moulin-manuel' => 1,
			)
		);
		fabrique_egal( array( 'free_shipping', 'local_pickup' ), $methodes() );
	}
);

WC()->cart->empty_cart();
fabrique_test_fin();
