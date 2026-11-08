/**
 * Client de l'API Recherche d'entreprises, partagé par tous les champs de la page.
 *
 * L'API accepte 7 requêtes par seconde par IP. Les requêtes de la page sont
 * espacées d'au moins 250 ms, quel que soit le nombre de champs et la vitesse
 * de frappe : 4 par seconde en théorie, 5 au pire avec la gigue des minuteries.
 *
 * @package WSForm_Siret
 */

( function wsfsiretApiClientModule() {
	'use strict';

	const SEARCH_URL = 'https://recherche-entreprises.api.gouv.fr/search';
	const MIN_INTERVAL_MS = 250;
	const DEFAULT_PAUSE_SECONDS = 10;
	const MAX_PAUSE_SECONDS = 300;
	const STATUS_TOO_MANY_REQUESTS = 429;
	const COMPANIES_PER_PAGE = 5;
	const ESTABLISHMENTS_PER_COMPANY = 5;

	// Heure à partir de laquelle la prochaine requête peut partir.
	let nextSlot = 0;

	// Après un 429, l'API refuse tout pendant quelques secondes : inutile d'insister.
	let pausedUntil = 0;

	/**
	 * Crée l'erreur rendue par une attente annulée, comme le ferait fetch().
	 *
	 * @return {DOMException} Erreur d'annulation.
	 */
	function createAbortError() {
		return new DOMException( 'Search aborted', 'AbortError' );
	}

	/**
	 * Attend un délai, ou rejette si la recherche est annulée entre-temps.
	 *
	 * @param {number}      delay  Délai en millisecondes.
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<void>} Résolue à la fin du délai.
	 */
	function wait( delay, signal ) {
		return new Promise( function waitExecutor( resolve, reject ) {
			const timer = window.setTimeout( resolve, delay );

			signal.addEventListener( 'abort', function onAbort() {
				window.clearTimeout( timer );
				reject( createAbortError() );
			}, { once: true } );
		} );
	}

	/**
	 * Réserve le prochain créneau de requête et attend son heure.
	 *
	 * Le créneau reste pris même si la recherche est annulée : c'est plus prudent.
	 *
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<void>} Résolue quand la requête peut partir.
	 */
	async function waitForSlot( signal ) {
		const now = Date.now();
		const slot = Math.max( now, nextSlot );

		nextSlot = slot + MIN_INTERVAL_MS;

		if ( slot > now ) {
			await wait( slot - now, signal );
		}
	}

	/**
	 * Suspend les requêtes le temps demandé par l'API.
	 *
	 * @param {string|null} retryAfter Valeur de l'en-tête Retry-After.
	 */
	function pause( retryAfter ) {
		const seconds = parseInt( retryAfter, 10 ) || DEFAULT_PAUSE_SECONDS;

		pausedUntil = Date.now() + Math.min( seconds, MAX_PAUSE_SECONDS ) * 1000;
	}

	/**
	 * Construit l'adresse de la recherche.
	 *
	 * Aucun en-tête personnalisé n'est envoyé : il déclencherait une requête
	 * préalable OPTIONS, que l'API refuse.
	 *
	 * @param {string} query Nom, SIREN ou SIRET.
	 * @return {string} Adresse.
	 */
	function buildUrl( query ) {
		const params = new URLSearchParams( {
			q: query,
			minimal: 'true',
			include: 'siege,matching_etablissements,tva',
			per_page: COMPANIES_PER_PAGE,
			limite_matching_etablissements: ESTABLISHMENTS_PER_COMPANY,
		} );

		return SEARCH_URL + '?' + params;
	}

	/**
	 * Cherche des entreprises.
	 *
	 * @param {string}      query  Nom, SIREN ou SIRET.
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<Object[]>} Entreprises, au format de l'API. Rejetée si l'API est indisponible.
	 */
	async function search( query, signal ) {
		if ( Date.now() < pausedUntil ) {
			throw new Error( 'Search paused after HTTP 429' );
		}

		await waitForSlot( signal );

		const response = await fetch( buildUrl( query ), { signal } );

		if ( STATUS_TOO_MANY_REQUESTS === response.status ) {
			pause( response.headers.get( 'retry-after' ) );
		}

		if ( ! response.ok ) {
			throw new Error( 'Search failed with HTTP ' + response.status );
		}

		const payload = await response.json();

		return Array.isArray( payload.results ) ? payload.results : [];
	}

	window.wsfsiretApiClient = { search };
}() );
