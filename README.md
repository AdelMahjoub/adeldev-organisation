# Adeldev Organisation

Brique universelle (méthode : `~/dev/adeldev-wp/METHODE.md`, §3.2). Les informations de
l'organisation — nom, contact, adresse, horaires, réseaux sociaux, avis Google, mentions
légales, hébergeur — saisies **une fois**, dans **chaque langue** du site, et réutilisées
partout sans jamais être écrites en dur.

Aucune dépendance. Fonctionne avec ou sans Polylang.

## Saisie

Menu **Organisation** de l'administration (`manage_options`).

- Un onglet par langue installée dans WordPress (et par langue Polylang). Chaque onglet
  porte **tous** les champs : chaque information peut avoir sa valeur par langue
  (adresse, horaires, mais aussi un WhatsApp ou un compte Instagram propre à une langue).
  Rien n'est traduit automatiquement ; un champ vide reprend la valeur de la langue
  principale (affichée en gris dans le champ).
- Le nom et le slogan sont le titre et le slogan du site (`blogname`, `blogdescription`).
- Un seul **numéro d'immatriculation**, générique : SIRET, matricule fiscal, ICE…
  Le libellé qui le précède s'écrit sur le site.
- Stockage : option `adeldev_organisation` = `{ "default": { "contact.phone": "…" }, "ar": { … } }`,
  exposée en REST (`/wp/v2/settings`).

## Affichage

Une seule fonction de lecture, `adeldev_org_value( 'contact.phone' )`, dans la langue de la
page affichée (paramètre `$locale` optionnel). Clés `groupe.champ` ; valeurs calculées :
`address.full`, `address.short`, `identity.years`, `reviews.google_text`, `link.tel`,
`link.tel_2`, `link.whatsapp` (avec le message par défaut), `link.mailto`, `link.maps`,
`site.copyright`, `site.year`.

| Moyen | Usage |
|---|---|
| Source de bindings `adeldev/organisation` | Paragraphe, titre, bouton (texte et URL), icône de réseau social (URL). Un bloc lié à une valeur vide n'est pas rendu. |
| Bouton « immeuble » de la barre d'outils | Insère une valeur au milieu d'une phrase, en texte ou en lien (`<span data-adeldev-org>`), réécrite au rendu. |
| Shortcode `[organisation key="contact.email" link="1" text="…"]` | Dans n'importe quel bloc, gabarits et parts compris. |
| Icône « immeuble » en haut de l'éditeur | Catalogue de toutes les valeurs, recherche, aide, copie des shortcodes. |

Le thème n'utilise **aucun** de ces moyens (séparation stricte, D-02) : ce sont les
contenus, les patterns d'`adeldev-patterns` et les plugins des sites qui s'en servent.

## Étendre

```php
add_filter( 'adeldev_org_groups', function ( $groups ) {
	$groups['membership'] = array(
		'title'  => __( 'Adhésion', 'mon-site' ),
		'fields' => array(
			'fee' => array( 'label' => __( 'Cotisation annuelle', 'mon-site' ), 'type' => 'number' ),
		),
	);
	return $groups;
} );
```

Types : `text`, `textarea`, `email`, `tel`, `url`, `number` ; `help` et `default`
facultatifs. Comme les autres, ces champs ont une valeur par langue. Filtre `adeldev_org_locales` pour
changer les langues proposées.

## WP-CLI

```bash
ddev wp organisation list [--locale=ar]
```

```bash
ddev wp organisation set contact.phone "+216 71 000 000"
```

```bash
ddev wp organisation set address.city "تونس" --locale=ar
```

```bash
ddev wp organisation import donnees.json
```

`import` lit `{ "default": { "clé": "valeur" }, "ar": { … } }` (ou un objet à plat pour la
langue principale) et fusionne avec l'existant.

## Développement

- Pas d'étape de build : JavaScript en ES simple sur les globales `wp.*`.
- Chaînes de code en anglais avec gettext (domaine `adeldev-organisation`), traduction
  française dans `languages/`. Après modification des chaînes, depuis un projet DDEV où
  le plugin est monté :

```bash
ddev wp i18n make-pot wp-content/plugins/adeldev-organisation wp-content/plugins/adeldev-organisation/languages/adeldev-organisation.pot --exclude=.github
```

  puis mettre à jour `adeldev-organisation-fr_FR.po`, et :

```bash
ddev wp i18n make-mo wp-content/plugins/adeldev-organisation/languages
```

```bash
ddev wp i18n make-php wp-content/plugins/adeldev-organisation/languages
```

```bash
ddev wp i18n make-json wp-content/plugins/adeldev-organisation/languages --no-purge
```

- En local, le dépôt est monté dans chaque projet DDEV
  (`.ddev/docker-compose.adeldev.yaml`) ; les mises à jour y sont coupées
  (`WP_ENVIRONMENT_TYPE=local`).

## Publier une version

Changer `Version` dans l'en-tête **et** `ADELDEV_ORG_VERSION`, committer sur `main`, pousser :
`.github/workflows/release.yml` crée la release `vX.Y.Z` avec le zip et `release.json`.
Les sites la reçoivent comme une mise à jour normale (`includes/updater.php`, cache 6 h).
