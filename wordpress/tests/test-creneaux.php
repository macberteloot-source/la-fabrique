<?php
/**
 * Tests du créneau de retrait : calcul, horaires, validation serveur, enregistrement (HPOS), affichage.
 *
 * Lancement : wp eval-file tests/test-creneaux.php
 *
 * @package Fabrique\Tests
 */

use Fabrique\Boutique\Creneau_Retrait;
use Fabrique\Boutique\Creneaux;
use Fabrique\Boutique\Reglages;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';

/**
 * Horaires par défaut (mercredi 17-19 h, samedi 10-12 h).
 *
 * @return array<int, array{jour: int, debut: string, fin: string}>
 */
function fabrique_test_horaires(): array {
	return Reglages::analyser_horaires( Reglages::defauts()['horaires'] )['horaires'];
}

/**
 * Date à Paris.
 *
 * @param string $date Date lisible.
 */
function fabrique_test_paris( string $date ): DateTimeImmutable {
	return new DateTimeImmutable( $date, new DateTimeZone( 'Europe/Paris' ) );
}

fabrique_test_groupe( 'Calcul des créneaux' );

fabrique_test(
	'mardi 13 oct. 16 h, délai 24 h : le mercredi 14 à 17 h est proposé ; 6 créneaux dans l’ordre',
	function () {
		$creneaux = Creneaux::calculer( fabrique_test_horaires(), 6, 24, fabrique_test_paris( '2026-10-13 16:00' ) );
		fabrique_egal(
			array(
				'2026-10-14 17:00-19:00' => 'Mercredi 14 oct. · 17 h – 19 h',
				'2026-10-17 10:00-12:00' => 'Samedi 17 oct. · 10 h – 12 h',
				'2026-10-21 17:00-19:00' => 'Mercredi 21 oct. · 17 h – 19 h',
				'2026-10-24 10:00-12:00' => 'Samedi 24 oct. · 10 h – 12 h',
				'2026-10-28 17:00-19:00' => 'Mercredi 28 oct. · 17 h – 19 h',
				'2026-10-31 10:00-12:00' => 'Samedi 31 oct. · 10 h – 12 h',
			),
			$creneaux
		);
	}
);

fabrique_test(
	'mardi 13 oct. 18 h 30 : moins de 24 h avant mercredi 17 h → premier créneau samedi 17',
	function () {
		$creneaux = Creneaux::calculer( fabrique_test_horaires(), 6, 24, fabrique_test_paris( '2026-10-13 18:30' ) );
		fabrique_egal( '2026-10-17 10:00-12:00', array_key_first( $creneaux ) );
		fabrique_egal( 6, count( $creneaux ) );
	}
);

fabrique_test(
	'délai 0 : un créneau déjà commencé n’est pas proposé',
	function () {
		$creneaux = Creneaux::calculer( fabrique_test_horaires(), 2, 0, fabrique_test_paris( '2026-10-14 17:30' ) );
		fabrique_egal( array( '2026-10-17 10:00-12:00', '2026-10-21 17:00-19:00' ), array_keys( $creneaux ) );
	}
);

fabrique_test(
	'changement d’heure (25 oct.) et de mois : libellés justes',
	function () {
		$creneaux = Creneaux::calculer( fabrique_test_horaires(), 3, 24, fabrique_test_paris( '2026-10-26 09:00' ) );
		fabrique_egal( array( 'Mercredi 28 oct. · 17 h – 19 h', 'Samedi 31 oct. · 10 h – 12 h', 'Mercredi 4 nov. · 17 h – 19 h' ), array_values( $creneaux ) );
	}
);

fabrique_test(
	'libellés : demi-heures, mois abrégés, clé invalide',
	function () {
		fabrique_egal( 'Samedi 1 août · 9 h 30 – 12 h', Creneaux::libelle( '2026-08-01 09:30-12:00' ) );
		fabrique_egal( 'Jeudi 31 déc. · 17 h – 19 h 45', Creneaux::libelle( '2026-12-31 17:00-19:45' ) );
		fabrique_egal( '', Creneaux::libelle( '2026-02-30 17:00-19:00' ) );
		fabrique_egal( '', Creneaux::libelle( '<script>' ) );
	}
);

fabrique_test(
	'horaires vides ou nombre nul : aucun créneau',
	function () {
		fabrique_egal( array(), Creneaux::calculer( array(), 6, 24, fabrique_test_paris( '2026-10-13 16:00' ) ) );
		fabrique_egal( array(), Creneaux::calculer( fabrique_test_horaires(), 0, 24, fabrique_test_paris( '2026-10-13 16:00' ) ) );
	}
);

fabrique_test_groupe( 'Horaires saisis dans les réglages' );

fabrique_test(
	'formats acceptés (17:00-19:00, 17h-19h, tiret long), lignes refusées signalées, tri chronologique',
	function () {
		$analyse = Reglages::analyser_horaires( "Samedi 10h-12h\nmercredi 17:00 – 19:30\nlundi 25:00-26:00\njeudi 19:00-17:00\nn’importe quoi\n" );
		fabrique_egal(
			array(
				array(
					'jour'  => 3,
					'debut' => '17:00',
					'fin'   => '19:30',
				),
				array(
					'jour'  => 6,
					'debut' => '10:00',
					'fin'   => '12:00',
				),
			),
			$analyse['horaires']
		);
		fabrique_egal( array( 'lundi 25:00-26:00', 'jeudi 19:00-17:00', 'n’importe quoi' ), $analyse['erreurs'] );
	}
);

fabrique_test(
	'nettoyage des réglages : horaires normalisés, bornes, identifiant Google contrôlé',
	function () {
		$propre = Reglages::nettoyer(
			array(
				'seuil'            => '39,90',
				'horaires'         => "samedi 9h-11h\nmercredi 17h-19h",
				'nb_creneaux'      => '99',
				'delai'            => '-5',
				'google_client_id' => 'pas-un-identifiant',
			)
		);
		fabrique_egal( 39.9, $propre['seuil'] );
		fabrique_egal( "mercredi 17:00-19:00\nsamedi 09:00-11:00", $propre['horaires'] );
		fabrique_egal( 20, $propre['nb_creneaux'] );
		fabrique_egal( 5, $propre['delai'], 'absint(-5) :' );
		fabrique_egal( Reglages::tout()['google_client_id'], $propre['google_client_id'], 'identifiant invalide refusé :' );
	}
);

fabrique_test_groupe( 'fabrique_horaires_retrait() (contrat avec le thème)' );

/**
 * Horaires renvoyés par fabrique_horaires_retrait() pour une saisie donnée (option restaurée ensuite).
 *
 * @param string|null $horaires Texte des horaires (null : réglages par défaut).
 * @return string[]
 */
function fabrique_test_horaires_publics( ?string $horaires ): array {
	$avant   = get_option( Reglages::OPTION );
	$valeurs = Reglages::defauts();
	if ( null !== $horaires ) {
		$valeurs['horaires'] = $horaires;
	}
	update_option( Reglages::OPTION, $valeurs );
	$resultat = fabrique_horaires_retrait();
	fabrique_test_restaurer_option( Reglages::OPTION, $avant );
	return $resultat;
}

fabrique_test(
	'horaires par défaut → « Mercredi, 17 h – 19 h », « Samedi, 10 h – 12 h »',
	function () {
		fabrique_egal( array( 'Mercredi, 17 h – 19 h', 'Samedi, 10 h – 12 h' ), fabrique_test_horaires_publics( null ) );
	}
);

fabrique_test(
	'réglage « jeudi 8:30-12:00 » → « Jeudi, 8 h 30 – 12 h »',
	function () {
		fabrique_egal( array( 'Jeudi, 8 h 30 – 12 h' ), fabrique_test_horaires_publics( 'jeudi 8:30-12:00' ) );
	}
);

fabrique_test(
	'ordre de la semaine, indépendant de la saisie et de la méthode de retrait ; jamais vide',
	function () {
		fabrique_egal(
			array( 'Lundi, 9 h – 10 h', 'Samedi, 9 h – 11 h', 'Samedi, 14 h – 16 h 30' ),
			fabrique_test_horaires_publics( "samedi 14:00-16:30\nsamedi 9h-11h\nlundi 9h-10h" )
		);
		// Option illisible (écrite hors de la page de réglages) : horaires par défaut, jamais une liste vide.
		fabrique_egal( array( 'Mercredi, 17 h – 19 h', 'Samedi, 10 h – 12 h' ), fabrique_test_horaires_publics( 'n’importe quoi' ) );
	}
);

fabrique_test_groupe( 'Checkout : validation et enregistrement' );

/**
 * Valide un checkout simulé.
 *
 * @param string      $methode Tarif choisi.
 * @param string|null $valeur  Valeur postée (null : champ absent).
 */
function fabrique_test_valider( string $methode, ?string $valeur ): WP_Error {
	unset( $_POST[ Creneau_Retrait::CHAMP ] );
	if ( null !== $valeur ) {
		$_POST[ Creneau_Retrait::CHAMP ] = $valeur;
	}
	$erreurs = new WP_Error();
	Creneau_Retrait::valider( array( 'shipping_method' => array( $methode ) ), $erreurs );
	return $erreurs;
}

fabrique_test(
	'retrait choisi sans créneau → erreur « Choisissez un créneau… » liée au champ',
	function () {
		$erreurs = fabrique_test_valider( 'local_pickup:3', null );
		fabrique_egal( array( 'fabrique_creneau_requis' ), $erreurs->get_error_codes() );
		fabrique_egal( array( 'id' => 'fabrique_creneau' ), $erreurs->get_error_data( 'fabrique_creneau_requis' ) );
		fabrique_egal( array( 'fabrique_creneau_requis' ), fabrique_test_valider( 'local_pickup:3', '' )->get_error_codes() );
	}
);

fabrique_test(
	'valeur hors liste (forgée, passée, mal formée) → refusée',
	function () {
		foreach ( array( '2020-01-01 17:00-19:00', '2026-10-15 03:00-04:00', 'samedi', '2026-10-10 10:00-12:00' ) as $valeur ) {
			fabrique_egal( array( 'fabrique_creneau_invalide' ), fabrique_test_valider( 'local_pickup:3', $valeur )->get_error_codes(), $valeur );
		}
		// Balises retirées par sanitize_text_field() : la valeur devient vide, donc « requis ».
		fabrique_egal( array( 'fabrique_creneau_requis' ), fabrique_test_valider( 'local_pickup:3', '<script>alert(1)</script>' )->get_error_codes() );
	}
);

fabrique_test(
	'créneau de la liste → accepté ; autre livraison → champ ignoré',
	function () {
		$premier = (string) array_key_first( Creneaux::disponibles() );
		fabrique_egal( array(), fabrique_test_valider( 'local_pickup:3', $premier )->get_error_codes() );
		fabrique_egal( array(), fabrique_test_valider( 'flat_rate:1', null )->get_error_codes() );
		fabrique_egal( array(), fabrique_test_valider( 'free_shipping:2', 'nimporte' )->get_error_codes() );
	}
);

fabrique_test(
	'enregistrement dans la commande (WC_Order, HPOS) et affichage : récapitulatif, e-mail, administration',
	function () {
		$premier = (string) array_key_first( Creneaux::disponibles() );
		$libelle = Creneaux::disponibles()[ $premier ];

		$commande  = wc_create_order();
		$livraison = new WC_Order_Item_Shipping();
		$livraison->set_method_title( 'Retrait à l’atelier' );
		$livraison->set_method_id( 'local_pickup' );
		$livraison->set_total( '0' );
		$commande->add_item( $livraison );
		$commande->add_product( wc_get_product( wc_get_product_id_by_sku( 'dripper-gres' ) ), 1 );
		$commande->calculate_totals();

		$_POST[ Creneau_Retrait::CHAMP ] = $premier;
		Creneau_Retrait::enregistrer( $commande, array( 'shipping_method' => array( 'local_pickup:3' ) ) );
		unset( $_POST[ Creneau_Retrait::CHAMP ] );
		$commande->save();

		$relue = wc_get_order( $commande->get_id() );
		try {
			fabrique_egal( $premier, $relue->get_meta( '_fabrique_creneau' ) );
			fabrique_egal( $libelle, $relue->get_meta( '_fabrique_creneau_libelle' ) );

			$lignes = $relue->get_order_item_totals();
			$cles   = array_keys( $lignes );
			fabrique_egal( 'fabrique_creneau', $cles[ array_search( 'shipping', $cles, true ) + 1 ], 'ligne juste après l’expédition :' );
			fabrique_egal( esc_html( $libelle ), $lignes['fabrique_creneau']['value'] );

			WC()->mailer();
			$courriel = wc_get_template_html(
				'emails/email-order-details.php',
				array(
					'order'         => $relue,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => null,
				)
			);
			fabrique_contient( 'Créneau de retrait :', $courriel, 'e-mail :' );
			fabrique_contient( esc_html( $libelle ), $courriel, 'e-mail :' );

			ob_start();
			Creneau_Retrait::afficher_admin( $relue );
			fabrique_contient( esc_html( $libelle ), (string) ob_get_clean(), 'administration :' );

			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				fabrique_test_ligne( '      (stockage HPOS actif : métas lues dans wc_orders_meta)' );
			}
		} finally {
			$relue->delete( true );
		}
	}
);

fabrique_test(
	'commande livrée (pas de retrait) : aucune méta, aucune ligne ajoutée',
	function () {
		$commande                        = wc_create_order();
		$_POST[ Creneau_Retrait::CHAMP ] = (string) array_key_first( Creneaux::disponibles() );
		Creneau_Retrait::enregistrer( $commande, array( 'shipping_method' => array( 'flat_rate:1' ) ) );
		unset( $_POST[ Creneau_Retrait::CHAMP ] );
		$commande->save();
		$meta   = wc_get_order( $commande->get_id() )->get_meta( '_fabrique_creneau' );
		$lignes = wc_get_order( $commande->get_id() )->get_order_item_totals();
		$commande->delete( true );
		fabrique_egal( '', $meta );
		fabrique_vrai( ! isset( $lignes['fabrique_creneau'] ), 'ligne ajoutée à tort' );
	}
);

fabrique_test_fin();
