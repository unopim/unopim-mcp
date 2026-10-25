<?php

return [
    'authorize' => [
        'title' => 'Valtuuta :client',
        'heading' => 'Valtuuta :client',
        'lede' => 'Tämä sovellus pyytää pääsyä UnoPim-tiliisi.',
        'signed-in-as' => 'Kirjautuneena käyttäjänä',
        'redirects-to' => 'Ohjaa osoitteeseen',
        'permissions' => 'Se pystyy',
        'approve' => 'Valtuuta',
        'approving' => 'Valtuutetaan…',
        'deny' => 'Peruuta',
        'footnote' => 'Käyttöoikeudet noudattavat nykyistä UnoPim-rooliasi ja käyttöoikeuksiasi.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Työn suoritusta ei löytynyt.',
        ],
        'get-asset' => [
            'not-found' => 'Mediatiedostoa [:id] ei löytynyt.',
        ],
        'browse-asset-directories' => [
            'not-found' => 'Mediatiedostojen kansiota [:id] ei löytynyt.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Valuutta [:code] on kanavan käytössä, eikä sitä voi poistaa käytöstä.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Tuote [:sku] (products.:index) on virheellinen; mitään erästä ei tallennettu.',
        ],

        'filters' => [
            'missing-field' => 'Jokainen suodatin tarvitsee kentän.',
            'invalid-field' => 'Kentän ":field" mukaan ei voi suodattaa. Käytä jotakin näistä: :fields.',
            'invalid-operator' => 'Operaattoria ":operator" ei tueta. Käytä jotakin näistä: :operators.',
            'invalid-value' => 'Suodatin ":operator" kentälle ":field" hyväksyy yhden arvon, ei luetteloa.',
            'invalid-cursor' => 'Sivutuskursori on virheellinen tai kuuluu toiseen hakuun. Suorita haku uudelleen ilman kursoria.',
            'invalid-date' => 'Suodatin ":field" tarvitsee päivämäärän, kuten 2026-01-31 tai 2026-01-31 13:45:00; ":value" ei ole päivämäärä.',
            'invalid-number' => 'Suodatin ":field" tarvitsee numeron; ":value" ei ole numero.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Attribuutin [:code] tyyppiä ei voi muuttaa sen luomisen jälkeen.',
            'category-invalid-parent' => 'Kategoriaa [:code] ei voi sijoittaa itsensä tai minkään oman alakategoriansa alle.',
            'super-attributes-invalid' => 'Superattribuuttien [:attributes] on oltava perheen [:family] valinta-attribuutteja, jotka eivät vaihtele kielen tai kanavan mukaan.',
            'parent-not-found' => 'Päätuotetta [:sku] ei ole olemassa tai se ei ole konfiguroitava tuote.',
            'parent-unsupported' => 'Päätuote [:sku] käyttää kaksitasoista varianttirakennetta. Luo sen variantit UnoPimin REST-rajapinnan kautta.',
            'parent-immutable' => 'Tuote [:sku] on jo olemassa; sen päätuotetta ei voi muuttaa.',
            'variant-axis-invalid' => 'Variantti [:sku] tarvitsee kelvollisen vaihtoehdon jokaiselle päätuotteensa varianttiattribuutille: :attributes.',
            'variant-exists' => 'Päätuotteella [:parent] on jo variantti, jonka SKU on [:sku] tai jolla on samat varianttiattribuuttien arvot.',
            'variant-values-taken' => 'Variantti [:sku] ei voi saada näitä varianttiattribuuttien arvoja: päätuotteella [:parent] on jo niillä variantti.',
            'product-create-incomplete' => 'Tuotetta [:sku] ei ole olemassa. Sen luomiseen tarvitaan sekä type että attribute_family_id.',
            'values-section-misplaced' => ':key ei ole attribuutti. Lähetä se values-kentän ylimmällä tasolla (values.:key), ei values.:section-osion sisällä.',
            'values-attribute-section' => 'Attribuutti [:code] kuuluu kohtaan :path.',
            'values-price-shape' => 'Hinta-attribuutti [:code] ottaa objektin, jossa valuuttakoodi ja summa, kuten {"USD": "19.99"}, ei listaa.',
            'values-currency-inactive' => 'Valuutta [:currencies] ei ole aktiivinen. Käytä jotakin näistä: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Kielletty: sinulla ei ole oikeutta suorittaa työkalua [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Vain yksi SELECT-, WITH-, SHOW-, EXPLAIN- tai DESCRIBE-lause on sallittu.',
            'forbidden' => 'Kysely käyttää avainsanaa :keyword, joka ei ole sallittu vain luku -kyselyssä.',
        ],

        'run-skill' => [
            'not-found' => 'Taitoa [:name] ei löytynyt.',
        ],

        'command-runner' => [
            'blocked' => 'Komento [:command] ei ole sallittu, koska se voi suorittaa mielivaltaista koodia.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Laajennuksen nimi [:name] on virheellinen. Käytä vain kirjaimia ja numeroita, ja aloita isolla kirjaimella.',
        ],

        'test-generator' => [
            'invalid-package' => 'Paketin nimi [:name] on virheellinen. Käytä nimeä kuten MyPlugin tai Webkul/MyPlugin.',
            'invalid-class' => 'Luokan nimi [:name] on virheellinen. Käytä nimeä kuten Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method-arvon on oltava S256; plain-menetelmää ei tueta.',

        'registration' => [
            'body-too-large' => 'Rekisteröintipyyntö on liian suuri. Raja on :max tavua.',
            'too-many-redirect-uris' => 'Liian monta uudelleenohjaus-URI:a. Enintään :max on sallittu.',
            'redirect-uri-too-long' => 'Kunkin uudelleenohjaus-URI:n enimmäispituus on :max merkkiä.',
            'rate-limited' => 'Liian monta rekisteröintiyritystä. Yritä myöhemmin uudelleen.',
        ],
    ],
];
