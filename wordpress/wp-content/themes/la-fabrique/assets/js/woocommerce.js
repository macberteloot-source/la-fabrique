/**
 * La Fabrique — petits compléments pour WooCommerce.
 *
 * 1. Boutons − et + du champ quantité (ajoutés par des hooks PHP, cachés sans JavaScript).
 * 2. Relais : WooCommerce émet des événements jQuery (added_to_cart…) ; on les traduit en
 *    événements natifs pour que theme.js reste en JavaScript natif.
 */
( function () {
	'use strict';

	/* 1. Quantité ------------------------------------------------------------------------------ */

	function afficherBoutons( racine ) {
		( racine || document ).querySelectorAll( '[data-lf-quantite][hidden]' ).forEach( function ( bouton ) {
			var champ = bouton.parentElement.querySelector( 'input.qty' );
			// Pas de boutons autour d'une quantité figée (produit vendu à l'unité : champ caché).
			if ( champ && 'number' === champ.type ) {
				bouton.hidden = false;
				bouton.parentElement.classList.add( 'lf-quantite' );
			}
		} );
	}

	document.addEventListener( 'click', function ( evenement ) {
		var bouton = evenement.target.closest( '[data-lf-quantite]' );
		if ( ! bouton ) {
			return;
		}
		var champ = bouton.parentElement.querySelector( 'input.qty' );
		if ( ! champ || champ.disabled || champ.readOnly ) {
			return;
		}
		var pas = parseFloat( champ.step ) || 1;
		var min = '' !== champ.min ? parseFloat( champ.min ) : 0;
		var max = '' !== champ.max ? parseFloat( champ.max ) : Infinity;
		var valeur = ( parseFloat( champ.value ) || 0 ) + pas * parseInt( bouton.getAttribute( 'data-lf-quantite' ), 10 );
		champ.value = Math.min( Math.max( valeur, min ), max );
		// « input » et « change » : WooCommerce active le bouton « Mettre à jour le panier » sur ces événements.
		champ.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		champ.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

	afficherBoutons( document );

	/* 2. Relais des événements jQuery de WooCommerce ------------------------------------------- */

	var $ = window.jQuery;
	if ( ! $ ) {
		return;
	}
	$( document.body ).on( 'updated_wc_div updated_cart_totals updated_checkout wc_fragments_refreshed wc_fragments_loaded', function () {
		afficherBoutons( document );
	} );
	$( document.body ).on( 'added_to_cart', function ( evenement, fragments, hash, $bouton ) {
		document.dispatchEvent( new CustomEvent( 'lf:panier-ajoute', {
			detail: { bouton: $bouton && $bouton[ 0 ] ? $bouton[ 0 ] : null },
		} ) );
	} );
}() );
