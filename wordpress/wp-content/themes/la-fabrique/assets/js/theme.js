/**
 * La Fabrique — comportements du thème (JavaScript natif, sans dépendance).
 *
 * 1. Panneaux <dialog> : menu mobile, recherche, mini-panier (ouverture animée, aria-expanded).
 * 2. En-tête : compact au défilement, masqué en descendant, réaffiché en remontant.
 * 3. Apparitions au défilement et titres révélés mot à mot.
 * 4. Boutons « magnétiques » (pointeur précis uniquement).
 * 5. Panier : rebond du compteur et ouverture du mini-panier après un ajout.
 *
 * Toutes les animations reposent sur transform et opacity, et sont coupées si l'utilisateur
 * a demandé à limiter les animations (prefers-reduced-motion).
 */
( function () {
	'use strict';

	var reduit = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var pointeurFin = window.matchMedia( '(hover: hover) and (pointer: fine)' );

	/* 1. Panneaux ---------------------------------------------------------------------------- */

	function declencheurs( dialogue ) {
		return document.querySelectorAll( '[data-lf-dialogue="' + dialogue.id + '"]' );
	}

	function marquer( dialogue, ouvert ) {
		declencheurs( dialogue ).forEach( function ( bouton ) {
			bouton.setAttribute( 'aria-expanded', ouvert ? 'true' : 'false' );
		} );
	}

	function ouvrir( dialogue ) {
		if ( ! dialogue || dialogue.open ) {
			return;
		}
		document.querySelectorAll( 'dialog.lf-panneau[open]' ).forEach( fermer );
		dialogue.showModal();
		marquer( dialogue, true );
		// Élément qui reçoit le focus : celui que le panneau désigne (data-lf-focus), sinon « Fermer ».
		var cible = dialogue.querySelector( dialogue.getAttribute( 'data-lf-focus' ) || '[data-lf-ferme]' ) ||
			dialogue.querySelector( '[data-lf-ferme]' );
		if ( cible ) {
			cible.focus();
		}
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				dialogue.classList.add( 'is-visible' );
			} );
		} );
	}

	function fermer( dialogue ) {
		if ( ! dialogue || ! dialogue.open || dialogue.dataset.fermeture ) {
			return;
		}
		dialogue.dataset.fermeture = '1';
		dialogue.classList.remove( 'is-visible' );
		marquer( dialogue, false );

		var fini = false;
		var terminer = function () {
			if ( fini ) {
				return;
			}
			fini = true;
			delete dialogue.dataset.fermeture;
			dialogue.close();
		};
		if ( reduit.matches ) {
			terminer();
			return;
		}
		dialogue.addEventListener( 'transitionend', function surFin( evenement ) {
			if ( evenement.target === dialogue ) {
				dialogue.removeEventListener( 'transitionend', surFin );
				terminer();
			}
		} );
		window.setTimeout( terminer, 650 );
	}

	document.querySelectorAll( 'dialog.lf-panneau' ).forEach( function ( dialogue ) {
		// Échap : on anime la fermeture au lieu de la fermeture brutale par défaut.
		dialogue.addEventListener( 'cancel', function ( evenement ) {
			evenement.preventDefault();
			fermer( dialogue );
		} );
		// Clic sur le fond (en dehors du panneau).
		dialogue.addEventListener( 'click', function ( evenement ) {
			if ( evenement.target !== dialogue ) {
				return;
			}
			var cadre = dialogue.getBoundingClientRect();
			var dedans = evenement.clientX >= cadre.left && evenement.clientX <= cadre.right &&
				evenement.clientY >= cadre.top && evenement.clientY <= cadre.bottom;
			if ( ! dedans ) {
				fermer( dialogue );
			}
		} );
		dialogue.querySelectorAll( '[data-lf-ferme]' ).forEach( function ( bouton ) {
			bouton.addEventListener( 'click', function () {
				fermer( dialogue );
			} );
		} );
		// Un lien suivi depuis le panneau ferme le panneau (ancre sur la même page, par exemple).
		dialogue.addEventListener( 'click', function ( evenement ) {
			if ( evenement.target.closest( 'a[href^="#"]' ) ) {
				fermer( dialogue );
			}
		} );
	} );

	// Les déclencheurs : boutons (menu, recherche) et lien du panier (qui reste un lien sans JS).
	document.addEventListener( 'click', function ( evenement ) {
		var declencheur = evenement.target.closest( '[data-lf-dialogue]' );
		if ( ! declencheur || evenement.metaKey || evenement.ctrlKey || evenement.shiftKey || evenement.button > 0 ) {
			return;
		}
		var dialogue = document.getElementById( declencheur.getAttribute( 'data-lf-dialogue' ) );
		if ( ! dialogue ) {
			return;
		}
		evenement.preventDefault();
		ouvrir( dialogue );
	} );

	document.querySelectorAll( 'a[data-lf-dialogue]' ).forEach( function ( lien ) {
		lien.setAttribute( 'aria-haspopup', 'dialog' );
		lien.setAttribute( 'aria-expanded', 'false' );
		lien.setAttribute( 'aria-controls', lien.getAttribute( 'data-lf-dialogue' ) );
	} );

	// Le menu mobile se referme si l'écran repasse en largeur « bureau ».
	window.matchMedia( '(min-width: 60.0625rem)' ).addEventListener( 'change', function ( requete ) {
		if ( requete.matches ) {
			fermer( document.getElementById( 'lf-menu-mobile' ) );
		}
	} );

	/* 2. En-tête ------------------------------------------------------------------------------ */

	var entete = document.querySelector( '[data-lf-entete]' );
	if ( entete ) {
		var dernier = window.scrollY;
		var attente = false;
		var majEntete = function () {
			attente = false;
			var y = window.scrollY;
			entete.classList.toggle( 'is-compact', y > 8 );
			if ( Math.abs( y - dernier ) < 6 ) {
				return;
			}
			var descend = y > dernier;
			var cacher = descend && y > 260 && ! entete.contains( document.activeElement ) && ! document.querySelector( 'dialog[open]' );
			entete.classList.toggle( 'is-cache', cacher );
			dernier = y;
		};
		window.addEventListener( 'scroll', function () {
			if ( ! attente ) {
				attente = true;
				window.requestAnimationFrame( majEntete );
			}
		}, { passive: true } );
		entete.addEventListener( 'focusin', function () {
			entete.classList.remove( 'is-cache' );
		} );
		majEntete();
	}

	/* 3. Apparitions et titres révélés ----------------------------------------------------------- */

	// Les titres marqués data-lf-revele="lignes" sont découpés en mots ; l'intitulé accessible
	// reste la phrase entière (aria-label), les morceaux sont masqués aux lecteurs d'écran.
	function decouper( titre ) {
		var texte = titre.textContent.replace( /\s+/g, ' ' ).trim();
		if ( ! texte || titre.children.length ) {
			titre.classList.add( 'is-decoupe' );
			return;
		}
		titre.setAttribute( 'aria-label', texte );
		var conteneur = document.createElement( 'span' );
		conteneur.setAttribute( 'aria-hidden', 'true' );
		texte.split( ' ' ).forEach( function ( mot, index ) {
			var masque = document.createElement( 'span' );
			var interieur = document.createElement( 'span' );
			masque.className = 'lf-mot';
			interieur.textContent = mot;
			interieur.style.setProperty( '--lf-i', index );
			masque.appendChild( interieur );
			conteneur.appendChild( masque );
			conteneur.appendChild( document.createTextNode( ' ' ) );
		} );
		titre.textContent = '';
		titre.appendChild( conteneur );
		titre.classList.add( 'is-decoupe' );
	}

	var aReveler = [];
	document.querySelectorAll( '[data-lf-revele="lignes"]' ).forEach( function ( titre ) {
		decouper( titre );
		aReveler.push( titre );
	} );

	// Cartes produits de WooCommerce : même apparition que les cartes du thème, seulement pour celles
	// qui sont encore sous la ligne de flottaison (celles déjà affichées ne doivent pas clignoter).
	document.querySelectorAll( 'ul.products > li.product' ).forEach( function ( carte ) {
		if ( carte.getBoundingClientRect().top > window.innerHeight ) {
			carte.setAttribute( 'data-lf-revele', '' );
		}
	} );

	// Échelonnement : rang de chaque élément parmi ses frères révélés.
	document.querySelectorAll( '[data-lf-revele]:not([data-lf-revele="lignes"])' ).forEach( function ( element ) {
		if ( ! element.style.getPropertyValue( '--lf-ordre' ) && element.parentElement ) {
			var freres = Array.prototype.filter.call( element.parentElement.children, function ( frere ) {
				return frere.hasAttribute( 'data-lf-revele' );
			} );
			element.style.setProperty( '--lf-ordre', Math.min( freres.indexOf( element ), 6 ) );
		}
		aReveler.push( element );
	} );
	document.querySelectorAll( '[data-lf-courbe]' ).forEach( function ( element ) {
		aReveler.push( element );
	} );

	if ( reduit.matches || ! ( 'IntersectionObserver' in window ) ) {
		aReveler.forEach( function ( element ) {
			element.classList.add( 'is-visible' );
		} );
	} else {
		var observateur = new IntersectionObserver( function ( entrees ) {
			entrees.forEach( function ( entree ) {
				if ( entree.isIntersecting ) {
					entree.target.classList.add( 'is-visible' );
					observateur.unobserve( entree.target );
				}
			} );
		}, { rootMargin: '0px 0px -6% 0px', threshold: 0.08 } );
		aReveler.forEach( function ( element ) {
			observateur.observe( element );
		} );
	}

	// Hero : on attend les polices (au plus 700 ms) pour que le titre ne change pas de forme en montant.
	var hero = document.querySelector( '.lf-hero' );
	if ( hero ) {
		var lancer = function () {
			hero.classList.add( 'is-pret' );
		};
		if ( reduit.matches || ! document.fonts ) {
			lancer();
		} else {
			Promise.race( [
				document.fonts.ready,
				new Promise( function ( resoudre ) {
					window.setTimeout( resoudre, 700 );
				} ),
			] ).then( function () {
				window.requestAnimationFrame( lancer );
			} );
		}
	}

	/* 4. Boutons magnétiques -------------------------------------------------------------------- */

	function magnetique( element ) {
		element.addEventListener( 'pointermove', function ( evenement ) {
			if ( reduit.matches ) {
				return;
			}
			var cadre = element.getBoundingClientRect();
			var x = ( evenement.clientX - cadre.left - cadre.width / 2 ) * 0.28;
			var y = ( evenement.clientY - cadre.top - cadre.height / 2 ) * 0.4;
			element.style.setProperty( '--mx', x.toFixed( 1 ) + 'px' );
			element.style.setProperty( '--my', y.toFixed( 1 ) + 'px' );
		} );
		element.addEventListener( 'pointerleave', function () {
			element.style.setProperty( '--mx', '0px' );
			element.style.setProperty( '--my', '0px' );
		} );
	}

	if ( pointeurFin.matches ) {
		document.querySelectorAll( '[data-lf-magnetique], .single_add_to_cart_button, .checkout-button, #place_order' ).forEach( magnetique );
	}

	/* 5. Panier ----------------------------------------------------------------------------------- */

	// Événement émis par woocommerce.js (relais des événements jQuery de WooCommerce).
	document.addEventListener( 'lf:panier-ajoute', function () {
		var compteur = document.querySelector( '.lf-panier-compteur' );
		if ( compteur && ! reduit.matches ) {
			compteur.classList.remove( 'is-rebond' );
			void compteur.offsetWidth; // Relance l'animation.
			compteur.classList.add( 'is-rebond' );
		}
		var annonce = document.getElementById( 'lf-annonce' );
		if ( annonce && window.laFabriqueBoutique ) {
			annonce.textContent = '';
			window.setTimeout( function () {
				annonce.textContent = window.laFabriqueBoutique.ajoute;
			}, 60 );
		}
		var panier = document.getElementById( 'lf-panier' );
		if ( panier ) {
			window.setTimeout( function () {
				ouvrir( panier );
			}, 120 );
		}
	} );
}() );
