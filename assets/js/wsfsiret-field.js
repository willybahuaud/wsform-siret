/**
 * Champ SIRET : recherche en combobox, contrôle du numéro, mapping et résumé de l'entreprise.
 *
 * La valeur du champ est le SIRET. Le visiteur peut y taper un nom : le choix
 * d'une suggestion le remplace par le SIRET de l'établissement.
 *
 * @package WSForm_Siret
 */

( function wsfsiretFieldModule( $, siretNumber, apiClient, company, settings ) {
	'use strict';

	const DEBOUNCE_MS = 300;
	const MIN_QUERY_LENGTH = 3;
	const MAX_QUERY_LENGTH = 200;

	/**
	 * Un champ SIRET attaché à un input de formulaire.
	 */
	class SiretField {
		/**
		 * @param {Object} formInstance Instance WS Form qui contient le champ.
		 * @param {jQuery} $input       Input texte du champ.
		 */
		constructor( formInstance, $input ) {
			this.formInstance = formInstance;
			this.$input = $input;
			this.field = formInstance.get_field( $input );
			this.suffix = formInstance.get_section_repeatable_suffix( $input );
			this.entries = [];
			this.activeIndex = -1;
			this.chosenEntry = null;
			this.searchController = null;
			this.debounceTimer = null;

			this.createCombobox();
			this.createSourceMention();
			this.bindEvents();
			this.updateValidity();
		}

		/**
		 * Lit un réglage du champ, variables WS Form résolues.
		 *
		 * @param {string} metaKey      Clé du réglage.
		 * @param {string} defaultValue Valeur si le réglage est vide.
		 * @return {string} Valeur du réglage.
		 */
		getSetting( metaKey, defaultValue ) {
			return window.wsfsiretFields.getSetting( this.formInstance, this.field, metaKey, defaultValue );
		}

		/**
		 * Lit un texte du champ, ou sa traduction par défaut.
		 *
		 * @param {string} textKey Clé du texte dans settings.i18n.
		 * @return {string} Texte.
		 */
		getText( textKey ) {
			const metaKey = 'wsfsiret_text_' + textKey.replace( /[A-Z]/g, '_$&' ).toLowerCase();

			return this.getSetting( metaKey, settings.i18n[ textKey ] );
		}

		/**
		 * Indique si une case à cocher du champ est cochée.
		 *
		 * @param {string} metaKey Clé du réglage.
		 * @return {boolean} Vrai si elle est cochée.
		 */
		isChecked( metaKey ) {
			return 'on' === this.formInstance.get_object_meta_value( this.field, metaKey, '' );
		}

		/**
		 * Pose le balisage de combobox et les zones de message.
		 */
		createCombobox() {
			const listboxId = this.$input.attr( 'id' ) + '-listbox';
			const $anchor = this.$input.nextAll( '.wsfsiret-anchor' ).first();

			this.$listbox = $anchor.find( '.wsfsiret-listbox' );
			this.$listbox.attr( { id: listboxId, 'aria-label': settings.i18n.suggestions } );
			this.$summary = $anchor.nextAll( '.wsfsiret-summary' ).first();

			this.$input.attr( {
				role: 'combobox',
				autocomplete: 'off',
				'aria-autocomplete': 'list',
				'aria-expanded': 'false',
				'aria-controls': listboxId,
			} );

			// Le nombre de suggestions n'est lu qu'aux lecteurs d'écran : la liste ouverte suffit à l'œil.
			this.$status = $( '<div class="wsfsiret-status" role="status"></div>' );
			this.$notice = $( '<div class="wsf-help wsfsiret-notice" role="alert"></div>' );
			this.$summary.after( this.$status, this.$notice );
		}

		/**
		 * Ajoute la mention de la source, que la Licence Ouverte demande.
		 */
		createSourceMention() {
			if ( ! this.isChecked( 'wsfsiret_attribution' ) ) {
				return;
			}

			const $link = $( '<a target="_blank" rel="noopener"></a>' )
				.attr( 'href', settings.sourceUrl )
				.text( this.getText( 'attribution' ) );

			this.$notice.after( $( '<div class="wsf-help wsfsiret-attribution"></div>' ).append( $link ) );
		}

		/**
		 * Branche les événements.
		 */
		bindEvents() {
			this.$input.on( 'input', this.onInput.bind( this ) );
			this.$input.on( 'change', this.onChange.bind( this ) );
			this.$input.on( 'keydown', this.onKeydown.bind( this ) );
			this.$input.on( 'blur', this.closeList.bind( this ) );

			// Garde le focus dans l'input : sinon le blur fermerait la liste avant le clic.
			this.$listbox.on( 'mousedown', this.onListMousedown );
			this.$listbox.on( 'click', '[role="option"]', this.onOptionClick.bind( this ) );
		}

		/**
		 * Oublie l'entreprise choisie si le SIRET change, puis temporise la recherche.
		 */
		onInput() {
			const rawValue = this.$input.val();

			if ( this.chosenEntry && siretNumber.normalize( rawValue ) !== this.chosenEntry.siret ) {
				this.forgetChoice();
			}

			this.updateValidity();
			this.cancelSearch();
			this.$notice.text( '' );

			const search = this.getSearch( rawValue );

			if ( null === search ) {
				this.closeList();
				return;
			}

			this.debounceTimer = window.setTimeout( this.runSearch.bind( this, search ), DEBOUNCE_MS );
		}

		/**
		 * Décide de la recherche à lancer pour une saisie.
		 *
		 * Un numéro n'est cherché qu'une fois complet et valide : un SIREN ou un
		 * SIRET incomplet donnerait des résultats sans rapport.
		 *
		 * @param {string} rawValue Saisie.
		 * @return {Object|null} Requête et SIRET tapé, ou null si rien ne doit partir.
		 */
		getSearch( rawValue ) {
			const query = rawValue.trim();

			if ( siretNumber.isNumberLike( query ) ) {
				const digits = siretNumber.normalize( query );

				if ( siretNumber.isValidSiret( digits ) ) {
					return { query: digits, typedSiret: digits };
				}

				return siretNumber.isValidSiren( digits ) ? { query: digits, typedSiret: '' } : null;
			}

			if ( query.length < MIN_QUERY_LENGTH || query.length > MAX_QUERY_LENGTH ) {
				return null;
			}

			return { query, typedSiret: '' };
		}

		/**
		 * Range un SIRET tapé avec des espaces : la valeur enregistrée reste en chiffres.
		 */
		onChange() {
			const rawValue = this.$input.val();
			const digits = siretNumber.normalize( rawValue );

			if ( siretNumber.isNumberLike( rawValue ) && digits !== rawValue ) {
				this.$input.val( digits );
			}
		}

		/**
		 * Annule la recherche temporisée ou en cours : sa réponse arriverait après une saisie plus récente.
		 */
		cancelSearch() {
			window.clearTimeout( this.debounceTimer );

			if ( this.searchController ) {
				this.searchController.abort();
			}
		}

		/**
		 * Lance une recherche et affiche ses résultats.
		 *
		 * @param {Object} search Requête et SIRET tapé.
		 */
		async runSearch( search ) {
			this.searchController = new AbortController();

			try {
				const companies = await apiClient.search( search.query, this.searchController.signal );
				const mode = this.formInstance.get_object_meta_value( this.field, 'wsfsiret_establishment', 'head_office' );

				this.onResults( company.toEntries( companies, mode, search.typedSiret, settings.i18n ), search.typedSiret );
			} catch ( searchError ) {
				this.onServiceError( searchError );
			}
		}

		/**
		 * Affiche les résultats, ou choisit directement l'établissement d'un SIRET tapé.
		 *
		 * @param {Object[]} entries    Établissements trouvés.
		 * @param {string}   typedSiret SIRET tapé, ou chaîne vide.
		 */
		onResults( entries, typedSiret ) {
			if ( typedSiret && 1 === entries.length ) {
				this.chooseEntry( entries[ 0 ] );
				return;
			}

			this.showEntries( entries );

			if ( 0 === entries.length ) {
				// Un numéro valide absent de l'API peut appartenir à une entreprise non diffusible.
				this.$notice.text( this.getText( typedSiret ? 'notListed' : 'noResults' ) );
			}
		}

		/**
		 * Signale une panne de l'API, sans bloquer la saisie ni l'envoi du formulaire.
		 *
		 * @param {Error} serviceError Erreur reçue.
		 */
		onServiceError( serviceError ) {
			if ( 'AbortError' !== serviceError.name ) {
				this.closeList();
				this.$notice.text( this.getText( 'unavailable' ) );
			}
		}

		/**
		 * Affiche la liste des établissements.
		 *
		 * @param {Object[]} entries Établissements à afficher.
		 */
		showEntries( entries ) {
			this.entries = entries;
			this.setActiveIndex( -1 );
			this.$listbox.empty().append( entries.map( this.createOption, this ) );
			this.$listbox.prop( 'hidden', 0 === entries.length );
			this.$input.attr( 'aria-expanded', String( entries.length > 0 ) );
			this.$status.text( this.getResultsMessage( entries.length ) );
		}

		/**
		 * Crée une option de la liste. Les données de l'API n'entrent que comme du texte.
		 *
		 * @param {Object} entry Établissement.
		 * @param {number} index Position dans la liste.
		 * @return {jQuery} Option.
		 */
		createOption( entry, index ) {
			const $option = $( '<li role="option" class="wsfsiret-option" aria-selected="false"></li>' )
				.attr( { id: this.getOptionId( index ), 'data-index': index } );
			const place = [ entry.postcode, entry.city ].filter( Boolean ).join( ' ' );
			const details = [ settings.i18n.siretLabel.replace( '%s', siretNumber.format( entry.siret ) ) ];

			if ( entry.isHeadOffice ) {
				details.push( settings.i18n.headOffice );
			}

			$option.append(
				$( '<span class="wsfsiret-option-name"></span>' ).text( [ entry.name, place ].filter( Boolean ).join( ', ' ) ),
				$( '<span class="wsfsiret-option-details"></span>' ).text( details.join( ', ' ) )
			);

			if ( entry.isClosed ) {
				$option.append( $( '<span class="wsfsiret-option-closed"></span>' ).text( settings.i18n.closedLabel ) );
			}

			return $option;
		}

		/**
		 * Retourne l'identifiant d'une option.
		 *
		 * @param {number} index Position dans la liste.
		 * @return {string} Identifiant.
		 */
		getOptionId( index ) {
			return this.$input.attr( 'id' ) + '-option-' + index;
		}

		/**
		 * Retourne le message annonçant le nombre de résultats.
		 *
		 * @param {number} count Nombre de résultats.
		 * @return {string} Message.
		 */
		getResultsMessage( count ) {
			if ( 0 === count ) {
				return '';
			}

			return 1 === count ? settings.i18n.oneResult : settings.i18n.manyResults.replace( '%d', count );
		}

		/**
		 * Gère le clavier, selon le modèle combobox de l'ARIA Authoring Practices.
		 *
		 * @param {KeyboardEvent} keyEvent Événement clavier.
		 */
		onKeydown( keyEvent ) {
			const isOpen = ! this.$listbox.prop( 'hidden' );

			if ( 'ArrowDown' === keyEvent.key || 'ArrowUp' === keyEvent.key ) {
				keyEvent.preventDefault();
				this.moveActiveOption( 'ArrowDown' === keyEvent.key ? 1 : -1 );
			} else if ( 'Escape' === keyEvent.key && isOpen ) {
				keyEvent.preventDefault();
				this.closeList();
			} else if ( 'Enter' === keyEvent.key && isOpen && this.activeIndex >= 0 ) {
				// Sans cela, Entrée enverrait aussi le formulaire.
				keyEvent.preventDefault();
				keyEvent.stopPropagation();
				this.chooseEntry( this.entries[ this.activeIndex ] );
			}
		}

		/**
		 * Déplace l'option active, en bouclant, et rouvre la liste au besoin.
		 *
		 * @param {number} step Déplacement (1 ou -1).
		 */
		moveActiveOption( step ) {
			const count = this.entries.length;

			if ( 0 === count ) {
				return;
			}

			if ( this.$listbox.prop( 'hidden' ) ) {
				this.$listbox.prop( 'hidden', false );
				this.$input.attr( 'aria-expanded', 'true' );
			}

			const firstIndex = step > 0 ? 0 : count - 1;

			this.setActiveIndex( this.activeIndex < 0 ? firstIndex : ( this.activeIndex + step + count ) % count );
		}

		/**
		 * Marque l'option active. Le focus reste dans l'input : aria-activedescendant l'indique.
		 *
		 * @param {number} index Position de l'option, ou -1 pour aucune.
		 */
		setActiveIndex( index ) {
			this.activeIndex = index;
			this.$listbox.find( '[role="option"]' ).attr( 'aria-selected', 'false' );

			if ( index < 0 ) {
				this.$input.removeAttr( 'aria-activedescendant' );
				return;
			}

			const activeOption = document.getElementById( this.getOptionId( index ) );

			$( activeOption ).attr( 'aria-selected', 'true' );
			this.$input.attr( 'aria-activedescendant', activeOption.id );
			activeOption.scrollIntoView( { block: 'nearest' } );
		}

		/**
		 * Ferme la liste, sans toucher au texte saisi.
		 */
		closeList() {
			this.$listbox.prop( 'hidden', true );
			this.$input.attr( 'aria-expanded', 'false' );
			this.setActiveIndex( -1 );
		}

		/**
		 * Empêche la liste de prendre le focus au clic.
		 *
		 * @param {MouseEvent} mouseEvent Événement souris.
		 */
		onListMousedown( mouseEvent ) {
			mouseEvent.preventDefault();
		}

		/**
		 * Choisit l'option cliquée.
		 *
		 * @param {MouseEvent} clickEvent Événement du clic.
		 */
		onOptionClick( clickEvent ) {
			const index = parseInt( clickEvent.currentTarget.getAttribute( 'data-index' ), 10 );

			this.chooseEntry( this.entries[ index ] );
		}

		/**
		 * Applique l'établissement choisi au champ, au mapping et au résumé.
		 *
		 * @param {Object} entry Établissement choisi.
		 */
		chooseEntry( entry ) {
			this.cancelSearch();
			this.closeList();
			this.$notice.text( '' );
			this.chosenEntry = entry;
			this.$input.val( entry.siret ).trigger( 'change' );
			this.getMappingRows().forEach( this.fillMappedField.bind( this, entry ) );
			this.showSummary( entry );
			this.updateValidity();
		}

		/**
		 * Oublie l'établissement choisi : ses données ne décrivent plus le SIRET saisi.
		 */
		forgetChoice() {
			this.chosenEntry = null;
			this.$summary.prop( 'hidden', true ).empty();
			this.getMappingRows().forEach( this.fillMappedField.bind( this, {} ) );
		}

		/**
		 * Affiche le nom, l'adresse et l'état de l'établissement sous le champ.
		 *
		 * @param {Object} entry Établissement.
		 */
		showSummary( entry ) {
			const $status = $( '<span class="wsfsiret-summary-status"></span>' )
				.toggleClass( 'wsfsiret-summary-status-closed', entry.isClosed )
				.text( entry.status );

			this.$summary.empty().append(
				$( '<strong class="wsfsiret-summary-name"></strong>' ).text( entry.name ),
				$( '<span class="wsfsiret-summary-address"></span>' ).text( entry.address ),
				$status
			);
			this.$summary.prop( 'hidden', false );
		}

		/**
		 * Met à jour la validité du champ, comme le contrôle du serveur.
		 *
		 * WS Form n'affiche le message qu'à l'envoi, ou pendant la saisie si la
		 * validation en ligne est active.
		 */
		updateValidity() {
			const siret = siretNumber.normalize( this.$input.val() );
			let message = '';

			if ( '' !== siret && ! siretNumber.isValidSiret( siret ) ) {
				message = this.getText( 'invalid' );
			} else if ( this.chosenEntry && this.chosenEntry.isClosed && this.isChecked( 'wsfsiret_reject_closed' ) ) {
				message = this.getText( 'closed' );
			}

			this.formInstance.set_invalid_feedback( this.$input, message );
		}

		/**
		 * Retourne les lignes de mapping configurées.
		 *
		 * @return {Object[]} Lignes de mapping.
		 */
		getMappingRows() {
			const rows = this.formInstance.get_object_meta_value( this.field, 'wsfsiret_field_mapping', [] );

			return Array.isArray( rows ) ? rows : [];
		}

		/**
		 * Écrit une donnée de l'établissement dans le champ visé par une ligne de mapping.
		 *
		 * Passe par l'API du cœur, qui gère les listes et déclenche les conditions.
		 *
		 * @param {Object} entry Établissement, ou objet vide pour vider le champ.
		 * @param {Object} row   Ligne de mapping.
		 */
		fillMappedField( entry, row ) {
			if ( ! row.ws_form_field ) {
				return;
			}

			const prefix = this.formInstance.form_id_prefix;
			const $field = $( document.getElementById( prefix + 'field-' + row.ws_form_field + this.suffix ) );
			const $wrapper = $( document.getElementById( prefix + 'field-wrapper-' + row.ws_form_field + this.suffix ) );
			const value = entry[ row.wsfsiret_component ];

			if ( $field.length && ! $field.is( this.$input ) ) {
				this.formInstance.field_value_set( $wrapper, $field, undefined === value ? '' : String( value ) );
			}
		}
	}

	/**
	 * Initialise un champ SIRET.
	 *
	 * @param {Object} formInstance Instance WS Form.
	 * @param {jQuery} $input       Input du champ.
	 */
	function initSiretField( formInstance, $input ) {
		new SiretField( formInstance, $input );
	}

	window.wsfsiretFields.register( '[data-wsfsiret]', initSiretField );
}( jQuery, window.wsfsiretNumber, window.wsfsiretApiClient, window.wsfsiretCompany, window.wsfsiretSettings ) );
