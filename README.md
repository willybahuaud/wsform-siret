# Recherche SIRET pour WS Form

Un champ [WS Form](https://wsform.com/) qui trouve une entreprise française par son nom ou son SIRET, la vérifie et remplit le reste du formulaire. Données publiques de l'État, sans clé d'API.

## Pourquoi ce plugin

Dans un formulaire B2B, on demande presque toujours le SIRET. Le visiteur le cherche sur un Kbis, se trompe d'un chiffre, puis retape la raison sociale et l'adresse à la main. Côté site, on reçoit des numéros faux et des adresses approximatives.

L'État publie l'annuaire des entreprises dans l'API Recherche d'entreprises : pas de compte, pas de clé, accès ouvert. Ce plugin s'en sert. Le visiteur tape le nom de son entreprise, la choisit dans la liste, et le formulaire se remplit tout seul. Il marche avec WS Form LITE et PRO.

## Installation

1. Télécharge `wsform-siret-X.Y.Z.zip` depuis la [dernière release](https://github.com/willybahuaud/wsform-siret/releases/latest). Pas l'archive du code source : elle contient tout le dépôt.
2. Installe-le comme n'importe quelle extension, à côté de WS Form.
3. Le champ « Entreprise (SIRET) » apparaît dans le groupe « Services publics français » du constructeur. Si tu as aussi [Carte IGN pour WS Form](https://github.com/willybahuaud/wsform-ign), ses champs sont dans le même groupe.

Les mises à jour arrivent ensuite dans l'admin, depuis les releases GitHub.

## Ce que ça ajoute

**Recherche**

- Le visiteur tape un nom, un SIREN ou un SIRET, et choisit dans une liste : nom, code postal, ville, SIRET, siège ou non, fermé ou non
- Liste utilisable entièrement au clavier, et annoncée aux lecteurs d'écran
- Un SIRET complet et valide est retrouvé et choisi directement, sans passer par la liste
- Par défaut, la liste propose le siège. Un réglage permet de proposer plutôt les établissements qui correspondent à la recherche : « boulangerie angers » liste alors les établissements angevins

**Contrôle**

- Un SIREN ou un SIRET mal formé est refusé avant tout appel à l'API, par sa clé de contrôle (Luhn). La Poste a sa propre règle, elle est gérée
- Le serveur refait le contrôle à l'envoi : un navigateur trafiqué ne fait pas passer un faux numéro
- Réglage au choix : refuser un établissement fermé ou une entreprise cessée
- Réglage au choix : refuser un SIRET absent de l'annuaire. Désactivé par défaut, voir les limites

**Remplissage**

- Le champ associe des champs du formulaire aux données de l'entreprise : nom, raison sociale, enseigne, SIRET, SIREN, adresse complète, numéro et voie, code postal, ville, code NAF, numéro de TVA, état administratif, latitude, longitude
- Sous le champ, un résumé rappelle le nom, l'adresse et l'état de l'entreprise choisie
- Si le visiteur change ensuite le SIRET, les champs remplis se vident : ils ne décrivent plus le bon établissement

**Avec Carte IGN pour WS Form**

Si [Carte IGN pour WS Form](https://github.com/willybahuaud/wsform-ign) est actif, deux choses en plus :

- Un groupe « Carte IGN » apparaît dans les réglages du champ. Choisis une carte du formulaire : l'établissement choisi s'y place, au zoom de ton choix (17 par défaut).
- Un champ Adresse BAN peut recevoir l'adresse de l'entreprise par la correspondance des champs.

Le point est placé avec les coordonnées de la base Sirene, sans appel de plus à la Géoplateforme. Sur un échantillon de 40 établissements, elles tombaient à 14 m de la BAN en médiane, et étaient plus justes quand les deux divergeaient.

**Le reste**

- La valeur enregistrée est le SIRET, en 14 chiffres. Elle se lit telle quelle dans la liste des soumissions, l'export CSV, les e-mails et `#field(ID)`, et part proprement vers une méta ou un champ ACF
- Les messages se changent champ par champ, dans Avancé > Libellés
- Si l'API tombe ou limite le débit, un message s'affiche et le formulaire reste envoyable
- Rien ne se charge sur une page sans ce champ

## Les réglages du champ

Dans l'onglet Général du champ :

| Réglage | Par défaut | Effet |
|---|---|---|
| Correspondance des champs | Nom, voie, code postal, ville, sans champ choisi | Les champs à remplir, et avec quelle donnée |
| Établissement | Siège | Siège, ou établissements correspondant à la recherche |
| Carte IGN, champ et zoom | Aucune carte, zoom 17 | La carte où placer l'établissement. Visible seulement avec Carte IGN pour WS Form |
| Refuser un établissement fermé | Non | Vérifié auprès de l'API à l'envoi |
| Refuser un SIRET absent de l'annuaire | Non | Vérifié auprès de l'API à l'envoi |
| Afficher la source des données | Oui | Mention sous le champ, avec un lien vers la base Sirene |

## Les limites

- **Entreprises non diffusibles :** une entreprise peut refuser la diffusion de ses données. Elle n'est alors pas dans l'API, même avec un SIRET parfaitement valide. Le champ l'accepte et le dit au visiteur. Si tu actives « Refuser un SIRET absent de l'annuaire », ces entreprises ne pourront plus envoyer le formulaire.
- **API indisponible à l'envoi :** si l'API ne répond pas en 3 secondes, ou limite le débit, le SIRET est accepté sans vérification de l'état. Seule la clé de contrôle a été vérifiée. L'action `wsform_siret_lookup_unavailable` le signale, et une ligne part dans le journal si `WP_DEBUG_LOG` est actif.
- **Numéro de TVA :** l'API ne le donne pas pour toutes les entreprises. Le champ associé reste alors vide. Le plugin ne le calcule pas depuis le SIREN : un numéro calculé ne prouve pas que l'entreprise est assujettie.
- **Établissements d'un SIREN :** taper un SIREN propose le siège. Pour un autre établissement, cherche par nom et ville, ou tape directement son SIRET.
- **Adresse des établissements secondaires :** l'API ne détaille l'adresse que pour le siège. Pour les autres, « Numéro et voie » est déduit de l'adresse complète, en retirant le code postal et la ville.
- **Sections répétables :** testé avec PRO, y compris les lignes ajoutées par le visiteur. Chaque ligne a sa recherche, ses champs remplis et sa carte. LITE n'a pas de sections répétables.
- **Coordonnées :** quelques établissements n'en ont pas dans la base Sirene. La carte reste alors où elle est, et les champs latitude et longitude restent vides. Pour les recevoir, prends un champ texte ou masqué : un champ Nombre de WS Form refuse les décimales par défaut.
- **Adresse en majuscules :** la base Sirene écrit les adresses en majuscules, avec ses abréviations (« AV », « CHE »). Le champ Adresse BAN les reçoit telles quelles.
- **Débit :** le navigateur n'envoie jamais plus de 5 requêtes par seconde, tous champs de la page confondus. L'API en accepte 7 par IP, mais plusieurs onglets ouverts sur le même réseau partagent cette limite.

## Confidentialité

Le plugin n'ajoute ni traceur ni télémétrie. Par contre, deux appels partent vers l'API Recherche d'entreprises (`recherche-entreprises.api.gouv.fr`), un service de l'État :

- **Depuis le navigateur du visiteur**, pendant la saisie : l'API reçoit son adresse IP et le texte tapé dans le champ.
- **Depuis ton serveur**, à l'envoi, seulement si un contrôle d'état ou d'existence est actif : l'API reçoit le SIRET et l'IP du serveur. La réponse est gardée une heure en cache.

Mentionne ce service dans ta politique de confidentialité.

## Développer

```bash
composer install
composer lint
composer test
composer build
```

Les tests unitaires tournent sans WordPress, API simulée. Les scénarios d'intégration (déclaration du champ, mises à jour) se lancent sur un site local avec WS Form :

```bash
wp eval-file wp-content/plugins/wsform-siret/tests/integration/run.php --user=admin
```

Pour publier, passe la version dans `wsform-siret.php`, dans `includes/class-plugin.php` et dans `readme.txt`, puis pousse un tag `vX.Y.Z`. Le workflow refuse un tag qui ne correspond pas.

Le plugin se met à jour depuis GitHub, et les règles de WordPress.org l'interdisent. C'est pour ça qu'il n'est pas sur le répertoire.

## Licence

GPL-2.0-or-later. Les données viennent de la base Sirene de l'Insee, publiée sous [Licence Ouverte 2.0](https://www.data.gouv.fr/datasets/base-sirene-des-entreprises-et-de-leurs-etablissements-siren-siret), qui demande de citer la source : c'est le rôle de la mention sous le champ. Ce plugin n'est affilié ni à l'Insee, ni à la DINUM, ni à WS Form.

Fait par [Willy Bahuaud](https://wabeo.fr).
