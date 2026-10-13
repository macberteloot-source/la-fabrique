<?php
/**
 * Commandes fictives des 30 derniers jours, pour remplir le tableau de bord des ventes en démonstration.
 *
 * Usage : wp eval-file bin/commandes-demo.php   (après setup.sh / seed.php)
 * Relançable : les commandes de démonstration précédentes sont supprimées puis recréées.
 * Les e-mails WooCommerce sont coupés pendant le script, et le stock de la boutique n'est pas touché (commandes fictives).
 *
 * @package LaFabrique
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_create_order' ) ) {
	echo "WooCommerce doit être actif.\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- script en ligne de commande.
	return;
}

// Pas d'e-mails pour des commandes fictives.
foreach ( WC()->mailer()->get_emails() as $fabrique_demo_email ) {
	add_filter( 'woocommerce_email_enabled_' . $fabrique_demo_email->id, '__return_false' );
}

// 1. Suppression des commandes de démonstration précédentes.
$fabrique_demo_anciennes = wc_get_orders(
	array(
		'limit'      => -1,
		'meta_key'   => '_fabrique_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- script ponctuel.
		'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- script ponctuel.
	)
);
foreach ( $fabrique_demo_anciennes as $fabrique_demo_commande ) {
	$fabrique_demo_commande->delete( true );
}

// 2. Catalogue utilisé : variations 250 g / 1 kg des cafés et quelques accessoires.
$fabrique_demo_skus     = array(
	'wazemmes-250-g'     => 6,
	'beffroi-250-g'      => 4,
	'vieux-lille-250-g'  => 4,
	'fives-250-g'        => 5,
	'wazemmes-1-kg'      => 2,
	'fives-1-kg'         => 2,
	'braderie-250-g'     => 2,
	'nuit-blanche-250-g' => 2,
	'dripper-gres'       => 1,
	'coffret-decouverte' => 2,
);
$fabrique_demo_produits = array();
foreach ( $fabrique_demo_skus as $fabrique_demo_sku => $fabrique_demo_poids ) {
	$fabrique_demo_id = wc_get_product_id_by_sku( $fabrique_demo_sku );
	if ( $fabrique_demo_id ) {
		// Le poids sert à tirer au sort les produits : les cafés du quotidien se vendent plus.
		$fabrique_demo_produits = array_merge( $fabrique_demo_produits, array_fill( 0, $fabrique_demo_poids, $fabrique_demo_id ) );
	}
}
if ( ! $fabrique_demo_produits ) {
	echo "Lancez d'abord bin/seed.php.\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- script en ligne de commande.
	return;
}

$fabrique_demo_clients  = array( 'camille', 'jules', 'ines', 'malo', 'lea', 'hugo', 'nora', 'sacha', 'zoe', 'theo', 'emma', 'louis' );
$fabrique_demo_creneaux = class_exists( '\Fabrique\Boutique\Creneaux' ) ? array_keys( \Fabrique\Boutique\Creneaux::disponibles() ) : array();
$fabrique_demo_zone     = WC_Shipping_Zones::get_zone_matching_package(
	array(
		'destination' => array(
			'country'  => 'FR',
			'state'    => '',
			'postcode' => '59000',
			'city'     => 'Lille',
			'address'  => '',
		),
	)
);
$fabrique_demo_methodes = array();
foreach ( $fabrique_demo_zone->get_shipping_methods( true ) as $fabrique_demo_methode ) {
	$fabrique_demo_methodes[ $fabrique_demo_methode->id ] = $fabrique_demo_methode;
}

// 3. Vingt-quatre commandes réparties sur 28 jours.
$fabrique_demo_total = 0;
for ( $fabrique_demo_i = 0; $fabrique_demo_i < 24; $fabrique_demo_i++ ) {
	$fabrique_demo_commande = wc_create_order( array( 'status' => 'pending' ) );
	$fabrique_demo_lignes   = wp_rand( 1, 3 );
	for ( $fabrique_demo_l = 0; $fabrique_demo_l < $fabrique_demo_lignes; $fabrique_demo_l++ ) {
		$fabrique_demo_produit = wc_get_product( $fabrique_demo_produits[ wp_rand( 0, count( $fabrique_demo_produits ) - 1 ) ] );
		if ( $fabrique_demo_produit && $fabrique_demo_produit->is_in_stock() ) {
			$fabrique_demo_commande->add_product( $fabrique_demo_produit, wp_rand( 1, 2 ) );
		}
	}

	$fabrique_demo_prenom  = $fabrique_demo_clients[ wp_rand( 0, count( $fabrique_demo_clients ) - 1 ) ];
	$fabrique_demo_adresse = array(
		'first_name' => ucfirst( $fabrique_demo_prenom ),
		'last_name'  => 'Démo',
		'email'      => $fabrique_demo_prenom . '@exemple.test',
		'address_1'  => '1 rue de la Démo',
		'city'       => 'Lille',
		'postcode'   => '59000',
		'country'    => 'FR',
	);
	$fabrique_demo_commande->set_address( $fabrique_demo_adresse, 'billing' );
	$fabrique_demo_commande->set_address( $fabrique_demo_adresse, 'shipping' );
	$fabrique_demo_commande->calculate_totals();

	// Livraison : retrait une fois sur trois, sinon livraison offerte au-dessus du seuil, sinon Colissimo.
	$fabrique_demo_sous_total = (float) $fabrique_demo_commande->get_subtotal();
	if ( 0 === $fabrique_demo_i % 3 && isset( $fabrique_demo_methodes['local_pickup'] ) ) {
		$fabrique_demo_choix = 'local_pickup';
	} elseif ( $fabrique_demo_sous_total >= 45 && isset( $fabrique_demo_methodes['free_shipping'] ) ) {
		$fabrique_demo_choix = 'free_shipping';
	} else {
		$fabrique_demo_choix = 'flat_rate';
	}
	if ( isset( $fabrique_demo_methodes[ $fabrique_demo_choix ] ) ) {
		$fabrique_demo_livraison = new WC_Order_Item_Shipping();
		$fabrique_demo_livraison->set_method_title( $fabrique_demo_methodes[ $fabrique_demo_choix ]->get_title() );
		$fabrique_demo_livraison->set_method_id( $fabrique_demo_choix );
		$fabrique_demo_livraison->set_instance_id( (string) $fabrique_demo_methodes[ $fabrique_demo_choix ]->get_instance_id() );
		$fabrique_demo_livraison->set_total( 'flat_rate' === $fabrique_demo_choix ? '5.90' : '0' );
		$fabrique_demo_commande->add_item( $fabrique_demo_livraison );
	}
	if ( 'local_pickup' === $fabrique_demo_choix && $fabrique_demo_creneaux ) {
		$fabrique_demo_commande->update_meta_data( '_fabrique_creneau', $fabrique_demo_creneaux[ wp_rand( 0, min( 3, count( $fabrique_demo_creneaux ) - 1 ) ) ] );
	}

	$fabrique_demo_commande->set_payment_method( 'cod' );
	$fabrique_demo_commande->update_meta_data( '_fabrique_demo', '1' );
	$fabrique_demo_commande->calculate_totals();
	$fabrique_demo_commande->set_date_created( time() - wp_rand( 0, 28 ) * DAY_IN_SECONDS - wp_rand( 0, 10 ) * HOUR_IN_SECONDS );
	$fabrique_demo_commande->save();
	// Commandes fictives : on indique que le stock est « déjà réduit » pour que WooCommerce n'y touche pas.
	$fabrique_demo_commande->get_data_store()->set_stock_reduced( $fabrique_demo_commande->get_id(), true );
	$fabrique_demo_commande->update_status( $fabrique_demo_i < 14 ? 'completed' : 'processing' );
	++$fabrique_demo_total;
}

delete_transient( 'fabrique_kpi_30_jours' );
printf( "%d commandes de démonstration créées.\n", (int) $fabrique_demo_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- script en ligne de commande.
