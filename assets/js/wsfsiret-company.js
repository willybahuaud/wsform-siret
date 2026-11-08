/**
 * Traduit les entreprises renvoyées par l'API en établissements proposés au visiteur.
 *
 * Chaque établissement porte les données du mapping, sous les clés déclarées
 * dans Field_Settings::get_component_options().
 *
 * @package WSForm_Siret
 */

( function wsfsiretCompanyModule() {
	'use strict';

	const MAX_ENTRIES = 10;
	const COMPANY_CEASED = 'C';
	const ESTABLISHMENT_CLOSED = 'F';

	/**
	 * Lit une valeur texte, vide si l'API ne la donne pas.
	 *
	 * @param {*} value Valeur.
	 * @return {string} Texte.
	 */
	function toText( value ) {
		return null === value || undefined === value ? '' : String( value );
	}

	/**
	 * Retire le code postal et la ville de la fin de l'adresse.
	 *
	 * Seul le siège détaille son adresse : pour les autres établissements, l'API
	 * ne donne que la chaîne complète.
	 *
	 * @param {string} address  Adresse complète.
	 * @param {string} postcode Code postal.
	 * @return {string} Numéro et voie.
	 */
	function getStreet( address, postcode ) {
		const postcodeIndex = postcode ? address.lastIndexOf( ' ' + postcode + ' ' ) : -1;

		return postcodeIndex > 0 ? address.slice( 0, postcodeIndex ) : '';
	}

	/**
	 * Construit un établissement à proposer.
	 *
	 * @param {Object} company       Entreprise renvoyée par l'API.
	 * @param {Object} establishment Siège ou établissement correspondant.
	 * @param {Object} i18n          Textes traduits.
	 * @return {Object} Établissement, dont les clés sont les données du mapping.
	 */
	function toEntry( company, establishment, i18n ) {
		const address = toText( establishment.adresse );
		const postcode = toText( establishment.code_postal );
		const isClosed = COMPANY_CEASED === company.etat_administratif || ESTABLISHMENT_CLOSED === establishment.etat_administratif;
		const vatNumbers = Array.isArray( company.tva ) ? company.tva : [];
		const signs = Array.isArray( establishment.liste_enseignes ) ? establishment.liste_enseignes : [];

		return {
			name: toText( company.nom_complet ),
			legal_name: toText( company.nom_raison_sociale || company.nom_complet ),
			trade_name: toText( establishment.nom_commercial || signs[ 0 ] ),
			siret: toText( establishment.siret ),
			siren: toText( company.siren ),
			address,
			street: getStreet( address, postcode ),
			postcode,
			city: toText( establishment.libelle_commune ),
			naf: toText( company.activite_principale ),
			vat: toText( vatNumbers[ 0 ] ),
			status: isClosed ? i18n.closedLabel : i18n.active,
			// Coordonnées de la base Sirene géocodée : absentes pour quelques établissements.
			lat: toText( establishment.latitude ),
			lng: toText( establishment.longitude ),
			isClosed,
			isHeadOffice: true === establishment.est_siege,
		};
	}

	/**
	 * Indique si un établissement porte ce SIRET.
	 *
	 * @param {string} siret         SIRET cherché.
	 * @param {Object} establishment Établissement au format de l'API.
	 * @return {boolean} Vrai s'il correspond.
	 */
	function hasSiret( siret, establishment ) {
		return siret === establishment.siret;
	}

	/**
	 * Choisit les établissements à proposer pour une entreprise.
	 *
	 * Un SIRET tapé désigne un établissement précis : on garde celui-là, siège ou non.
	 * Sinon, le siège, ou les établissements qui correspondent à la recherche.
	 *
	 * @param {Object} company    Entreprise renvoyée par l'API.
	 * @param {string} mode       head_office ou any.
	 * @param {string} typedSiret SIRET tapé par le visiteur, ou chaîne vide.
	 * @return {Object[]} Établissements au format de l'API.
	 */
	function pickEstablishments( company, mode, typedSiret ) {
		const matching = Array.isArray( company.matching_etablissements ) ? company.matching_etablissements : [];
		const candidates = company.siege ? [ company.siege ].concat( matching ) : matching;

		if ( typedSiret ) {
			return candidates.filter( hasSiret.bind( null, typedSiret ) ).slice( 0, 1 );
		}

		if ( 'any' === mode && matching.length > 0 ) {
			return matching;
		}

		return company.siege ? [ company.siege ] : [];
	}

	/**
	 * Construit la liste des établissements à proposer.
	 *
	 * @param {Object[]} companies  Entreprises renvoyées par l'API.
	 * @param {string}   mode       head_office ou any.
	 * @param {string}   typedSiret SIRET tapé par le visiteur, ou chaîne vide.
	 * @param {Object}   i18n       Textes traduits.
	 * @return {Object[]} Établissements, dix au plus.
	 */
	function toEntries( companies, mode, typedSiret, i18n ) {
		const entries = [];

		for ( const company of companies ) {
			for ( const establishment of pickEstablishments( company, mode, typedSiret ) ) {
				entries.push( toEntry( company, establishment, i18n ) );
			}
		}

		return entries.slice( 0, MAX_ENTRIES );
	}

	window.wsfsiretCompany = { toEntries };
}() );
