/**
 * La Fabrique — recettes : filtres de l'archive et section « Recettes avec ce café ».
 *
 * Données : route REST du plugin fabrique-recettes, GET /wp-json/fabrique/v1/recettes
 * (paramètres methode, cafe, par_page, page). Configuration et textes traduits :
 * window.laFabriqueRecettes (voir inc/recettes.php).
 *
 * Amélioration progressive : sans JavaScript, les filtres sont de vrais liens vers les pages de
 * méthode et la fiche produit affiche une liste de liens rendue par le serveur. En cas d'erreur
 * réseau, on suit simplement le lien (filtres) ou on garde la liste (fiche produit).
 */
( function () {
	'use strict';

	var config = window.laFabriqueRecettes;
	var modele = document.getElementById( 'lf-modele-carte-recette' );
	if ( ! config || ! modele || ! ( 'fetch' in window ) ) {
		return;
	}
	var textes = config.textes;
	var reduit = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/* Outils ------------------------------------------------------------------------------------ */

	function format( gabarit, valeurs ) {
		var index = 0;
		return gabarit.replace( /%(\d\$)?[sd]/g, function ( correspondance, position ) {
			var cle = position ? parseInt( position, 10 ) - 1 : index++;
			return String( valeurs[ cle ] );
		} );
	}

	function temps( minutes ) {
		minutes = parseInt( minutes, 10 );
		if ( ! minutes ) {
			return '';
		}
		if ( minutes < 60 ) {
			return format( textes.minutes, [ minutes ] );
		}
		var reste = minutes % 60;
		var heures = Math.floor( minutes / 60 );
		return reste ? format( textes.heuresMin, [ heures, ( '0' + reste ).slice( -2 ) ] ) : format( textes.heures, [ heures ] );
	}

	function nombre( total ) {
		return format( 1 === total ? textes.une : textes.plusieurs, [ total ] );
	}

	// N'accepte que des liens http(s) du même site (les données viennent de notre route, par prudence).
	function lienSur( adresse ) {
		try {
			var url = new URL( adresse, window.location.href );
			return ( 'http:' === url.protocol || 'https:' === url.protocol ) ? url.href : '';
		} catch ( erreur ) {
			return '';
		}
	}

	function remplir( carte, champ, valeur ) {
		var ligne = carte.querySelector( '[data-champ="' + champ + '"]' );
		if ( ! ligne ) {
			return;
		}
		if ( ! valeur ) {
			ligne.remove();
			return;
		}
		ligne.querySelector( '[data-valeur]' ).textContent = valeur;
	}

	/**
	 * Construit une carte à partir d'une recette de l'API (textContent uniquement : aucun HTML injecté).
	 *
	 * @param {Object} recette Recette (format de la route REST).
	 * @param {number} niveau  Niveau du titre (2 ou 3).
	 * @return {HTMLElement} Carte.
	 */
	function carte( recette, niveau ) {
		var element = modele.content.firstElementChild.cloneNode( true );
		var image = element.querySelector( 'img' );
		if ( recette.image && recette.image.url && lienSur( recette.image.url ) ) {
			image.src = lienSur( recette.image.url );
			image.width = recette.image.largeur || 900;
			image.height = recette.image.hauteur || 600;
		} else {
			image.remove();
		}

		var methode = element.querySelector( '[data-champ="methode"]' );
		if ( recette.methode && recette.methode.nom ) {
			methode.textContent = recette.methode.nom;
		} else {
			methode.remove();
		}

		var lien = element.querySelector( '[data-champ="titre"]' );
		lien.href = lienSur( recette.lien );
		lien.textContent = recette.titre;
		if ( 3 === niveau ) {
			var h2 = element.querySelector( 'h2' );
			var h3 = document.createElement( 'h3' );
			h3.className = h2.className;
			while ( h2.firstChild ) {
				h3.appendChild( h2.firstChild );
			}
			h2.replaceWith( h3 );
		}

		remplir( element, 'temps', temps( recette.temps ) );
		remplir( element, 'difficulte', recette.difficulte_libelle );
		remplir( element, 'ratio', recette.ratio );
		element.setAttribute( 'data-lf-revele', '' );
		return element;
	}

	// Les nouvelles cartes apparaissent avec le même glissement que les autres.
	function reveler( cartes ) {
		cartes.forEach( function ( element, index ) {
			element.style.setProperty( '--lf-ordre', Math.min( index, 6 ) );
		} );
		if ( reduit.matches ) {
			cartes.forEach( function ( element ) {
				element.classList.add( 'is-visible' );
			} );
			return;
		}
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				cartes.forEach( function ( element ) {
					element.classList.add( 'is-visible' );
				} );
			} );
		} );
	}

	function appeler( parametres, signal ) {
		var url = new URL( config.route, window.location.href );
		Object.keys( parametres ).forEach( function ( cle ) {
			if ( '' !== parametres[ cle ] && null !== parametres[ cle ] ) {
				url.searchParams.set( cle, parametres[ cle ] );
			}
		} );
		return window.fetch( url.href, {
			headers: { Accept: 'application/json' },
			credentials: 'same-origin',
			signal: signal,
		} ).then( function ( reponse ) {
			if ( ! reponse.ok ) {
				throw new Error( 'HTTP ' + reponse.status );
			}
			return reponse.json();
		} );
	}

	/* Archive : filtres par méthode ------------------------------------------------------------- */

	function initArchive( racine ) {
		var filtres = racine.querySelector( '[data-lf-filtres]' );
		var grille = racine.querySelector( '[data-lf-recettes-grille]' );
		var statut = racine.querySelector( '[data-lf-recettes-statut]' );
		var titre = racine.querySelector( '[data-lf-recettes-titre]' );
		var pagination = racine.querySelector( '[data-lf-recettes-pagination]' );
		if ( ! filtres || ! grille || ! statut ) {
			return;
		}

		var controleur = null;
		var etat = { methode: racine.getAttribute( 'data-lf-methode' ) || '', page: 1, pages: 1 };
		var boutonPlus = null;

		function nomMethode( slug ) {
			var trouve = ( config.methodes || [] ).filter( function ( methode ) {
				return methode.slug === slug;
			} )[ 0 ];
			return trouve ? trouve.nom : '';
		}

		function activer( slug ) {
			filtres.querySelectorAll( '[data-lf-methode]' ).forEach( function ( lien ) {
				if ( lien.getAttribute( 'data-lf-methode' ) === slug ) {
					lien.setAttribute( 'aria-current', 'page' );
				} else {
					lien.removeAttribute( 'aria-current' );
				}
			} );
		}

		function majTitre( slug ) {
			if ( ! titre ) {
				return;
			}
			var ancien = titre.textContent.trim();
			var nouveau = slug ? nomMethode( slug ) : textes.toutes;
			if ( ! nouveau || nouveau === ancien ) {
				return;
			}
			titre.textContent = nouveau;
			if ( document.title.indexOf( ancien ) !== -1 ) {
				document.title = document.title.replace( ancien, nouveau );
			}
		}

		function majPlus() {
			if ( etat.page < etat.pages ) {
				if ( ! boutonPlus ) {
					var bloc = document.createElement( 'div' );
					bloc.className = 'lf-recettes__plus';
					boutonPlus = document.createElement( 'button' );
					boutonPlus.type = 'button';
					boutonPlus.className = 'lf-bouton lf-bouton--contour';
					boutonPlus.textContent = textes.plus;
					boutonPlus.addEventListener( 'click', function () {
						charger( etat.methode, etat.page + 1, true );
					} );
					bloc.appendChild( boutonPlus );
					grille.after( bloc );
				}
				boutonPlus.parentElement.hidden = false;
			} else if ( boutonPlus ) {
				boutonPlus.parentElement.hidden = true;
			}
		}

		/**
		 * Charge une méthode (ou une page suivante) depuis l'API.
		 *
		 * @param {string}  slug    Méthode ('' pour toutes).
		 * @param {number}  page    Page demandée.
		 * @param {boolean} ajouter Ajoute à la grille au lieu de la remplacer.
		 * @return {Promise} Promesse résolue quand la grille est à jour.
		 */
		function charger( slug, page, ajouter ) {
			if ( controleur ) {
				controleur.abort();
			}
			controleur = new AbortController();
			grille.setAttribute( 'aria-busy', 'true' );
			if ( ! ajouter ) {
				statut.textContent = textes.chargement;
			}

			return appeler( { methode: slug, par_page: config.parPage, page: page }, controleur.signal ).then( function ( donnees ) {
				var cartes = ( donnees.recettes || [] ).map( function ( recette ) {
					return carte( recette, 2 );
				} );
				if ( ! ajouter ) {
					grille.replaceChildren();
				}
				cartes.forEach( function ( element ) {
					grille.appendChild( element );
				} );
				reveler( cartes );

				etat = { methode: slug, page: page, pages: parseInt( donnees.pages, 10 ) || 1 };
				var total = parseInt( donnees.total, 10 ) || 0;
				statut.textContent = total ? nombre( total ) : textes.aucune;
				if ( pagination ) {
					pagination.hidden = true;
				}
				majPlus();
				grille.removeAttribute( 'aria-busy' );
				if ( ajouter && cartes[ 0 ] ) {
					var premierLien = cartes[ 0 ].querySelector( 'a' );
					if ( premierLien ) {
						premierLien.focus( { preventScroll: true } );
					}
				}
			} );
		}

		filtres.addEventListener( 'click', function ( evenement ) {
			var lien = evenement.target.closest( 'a[data-lf-methode]' );
			if ( ! lien || evenement.metaKey || evenement.ctrlKey || evenement.shiftKey || evenement.altKey || evenement.button > 0 ) {
				return;
			}
			evenement.preventDefault();
			var slug = lien.getAttribute( 'data-lf-methode' );
			var adresse = lien.href;
			activer( slug );
			charger( slug, 1, false ).then( function () {
				majTitre( slug );
				window.history.pushState( { lfMethode: slug }, '', adresse );
			} ).catch( function ( erreur ) {
				if ( 'AbortError' !== erreur.name ) {
					// Repli : la vraie page de la méthode.
					window.location.href = adresse;
				}
			} );
		} );

		window.addEventListener( 'popstate', function ( evenement ) {
			var slug = evenement.state && 'string' === typeof evenement.state.lfMethode ? evenement.state.lfMethode : racine.getAttribute( 'data-lf-methode' ) || '';
			activer( slug );
			charger( slug, 1, false ).then( function () {
				majTitre( slug );
			} ).catch( function ( erreur ) {
				if ( 'AbortError' !== erreur.name ) {
					window.location.reload();
				}
			} );
		} );

		// État initial pour le bouton « retour » du navigateur.
		window.history.replaceState( { lfMethode: etat.methode }, '' );
	}

	/* Fiche produit : « Recettes avec ce café » ----------------------------------------------- */

	function initCafe( section ) {
		var zone = section.querySelector( '[data-lf-recettes-zone]' );
		var cafe = parseInt( section.getAttribute( 'data-lf-recettes-cafe' ), 10 );
		if ( ! zone || ! cafe ) {
			return;
		}

		function enrichir() {
			zone.setAttribute( 'aria-busy', 'true' );
			appeler( { cafe: cafe, par_page: 3 } ).then( function ( donnees ) {
				var recettes = donnees.recettes || [];
				if ( ! recettes.length ) {
					return;
				}
				var grille = document.createElement( 'div' );
				grille.className = 'lf-grille lf-grille--3';
				var cartes = recettes.map( function ( recette ) {
					return carte( recette, 3 );
				} );
				cartes.forEach( function ( element ) {
					grille.appendChild( element );
				} );
				zone.replaceChildren( grille );
				reveler( cartes );
			} ).catch( function () {
				// La liste rendue par le serveur reste en place.
			} ).finally( function () {
				zone.removeAttribute( 'aria-busy' );
			} );
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			enrichir();
			return;
		}
		var observateur = new IntersectionObserver( function ( entrees ) {
			if ( entrees[ 0 ].isIntersecting ) {
				observateur.disconnect();
				enrichir();
			}
		}, { rootMargin: '300px 0px' } );
		observateur.observe( section );
	}

	var archive = document.querySelector( '[data-lf-recettes]' );
	if ( archive ) {
		initArchive( archive );
	}
	document.querySelectorAll( '[data-lf-recettes-cafe]' ).forEach( initCafe );
}() );
