# aleph-connector-pigeonpost

A deliberately odd sample connector proving the Aleph plugin boundary (MME-850 / A38).

It models a pigeon courier network — lofts that receive ring-numbered dispatches — precisely
because nothing in Aleph or Funes could accidentally already support it.

It implements two capabilities, `sources.discover` and `history.backfill`, ships one
package-local migration for its own operational state (`pigeonpost_loft_cursors`), and submits
provider-neutral versioned envelopes carrying a `pigeonpost.dispatch` v2 extension.

Installing it requires no edit to Aleph or Funes:

```json
{
    "repositories": [{ "type": "path", "url": "../Packages/aleph-connector-pigeonpost" }],
    "require": { "sifrious/aleph-connector-pigeonpost": "@dev" }
}
```

```sh
composer require sifrious/aleph-connector-pigeonpost:@dev
php artisan migrate
```

Laravel package discovery finds `PigeonPostServiceProvider`, which registers the connector with
`ConnectorRegistry` and loads its migration. Aleph then knows its capabilities.
