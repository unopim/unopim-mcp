<?php

return [
    'authorize' => [
        'title' => 'Godkjenn :client',
        'heading' => 'Godkjenn :client',
        'lede' => 'Denne applikasjonen ber om tilgang til UnoPim-kontoen din.',
        'signed-in-as' => 'Logget inn som',
        'redirects-to' => 'Sender deg videre til',
        'permissions' => 'Den vil kunne',
        'approve' => 'Godkjenn',
        'approving' => 'Godkjenner…',
        'deny' => 'Avbryt',
        'footnote' => 'Tilgangen følger din eksisterende UnoPim-rolle og dine tillatelser.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Jobbkjøringen ble ikke funnet.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Valutaen [:code] brukes av en kanal og kan ikke deaktiveres.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produktet [:sku] (products.:index) er ugyldig; ingenting i bunken ble lagret.',
        ],

        'filters' => [
            'missing-field' => 'Hvert filter må ha et felt.',
            'invalid-field' => 'Det kan ikke filtreres på feltet ":field". Bruk ett av disse: :fields.',
            'invalid-operator' => 'Operatoren ":operator" støttes ikke. Bruk en av disse: :operators.',
            'invalid-value' => 'Filteret ":operator" på ":field" tar én verdi, ikke en liste.',
            'invalid-cursor' => 'Pagineringsmarkøren er ugyldig eller tilhører et annet søk. Kjør søket på nytt uten markør.',
            'invalid-date' => 'Filteret ":field" krever en dato som 2026-01-31 eller 2026-01-31 13:45:00; ":value" er ikke en dato.',
            'invalid-number' => 'Filteret ":field" krever et tall; ":value" er ikke et tall.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Typen til attributtet [:code] kan ikke endres etter at det er opprettet.',
            'category-invalid-parent' => 'Kategorien [:code] kan ikke plasseres under seg selv eller under en av sine egne underkategorier.',
            'super-attributes-invalid' => 'Superattributtene [:attributes] må være valgattributter i familien [:family] som ikke varierer etter språk eller kanal.',
            'parent-not-found' => 'Overordnet produkt [:sku] finnes ikke eller er ikke et konfigurerbart produkt.',
            'parent-unsupported' => 'Overordnet produkt [:sku] bruker en variantstruktur med to nivåer. Opprett variantene via UnoPims REST-API.',
            'parent-immutable' => 'Produktet [:sku] finnes allerede; det overordnede produktet kan ikke endres.',
            'variant-axis-invalid' => 'Varianten [:sku] trenger et gyldig alternativ for hvert variantattributt i det overordnede produktet: :attributes.',
            'variant-exists' => 'Overordnet produkt [:parent] har allerede en variant med SKU [:sku] eller med de samme variantattributtverdiene.',
            'variant-values-taken' => 'Varianten [:sku] kan ikke få disse variantattributtverdiene: overordnet produkt [:parent] har allerede en variant med dem.',
            'product-create-incomplete' => 'Produktet [:sku] finnes ikke. Både type og attribute_family_id kreves for å opprette det.',
            'values-section-misplaced' => ':key er ikke et attributt. Send det på øverste nivå i values (values.:key), ikke inne i values.:section.',
            'values-attribute-section' => 'Attributtet [:code] hører hjemme i :path.',
            'values-price-shape' => 'Prisattributtet [:code] tar et objekt med valutakode og beløp, for eksempel {"USD": "19.99"}, ikke en liste.',
            'values-currency-inactive' => 'Valutaen [:currencies] er ikke aktiv. Bruk en av: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Forbudt: Du har ikke tillatelse til å kjøre [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Bare én enkelt SELECT-, WITH-, SHOW-, EXPLAIN- eller DESCRIBE-setning er tillatt.',
            'forbidden' => 'Spørringen bruker :keyword, som ikke er tillatt i en skrivebeskyttet spørring.',
        ],

        'run-skill' => [
            'not-found' => 'Ferdigheten [:name] ble ikke funnet.',
        ],

        'command-runner' => [
            'blocked' => 'Kommandoen [:command] er ikke tillatt fordi den kan kjøre vilkårlig kode.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Programtilleggsnavnet [:name] er ugyldig. Bruk bare bokstaver og sifre, og start med stor bokstav.',
        ],

        'test-generator' => [
            'invalid-package' => 'Pakkenavnet [:name] er ugyldig. Bruk et navn som MyPlugin eller Webkul/MyPlugin.',
            'invalid-class' => 'Klassenavnet [:name] er ugyldig. Bruk et navn som Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method må være S256; plain-metoden støttes ikke.',

        'registration' => [
            'body-too-large' => 'Registreringsforespørselen er for stor. Grensen er :max byte.',
            'too-many-redirect-uris' => 'For mange omdirigerings-URI-er. Maksimalt :max er tillatt.',
            'redirect-uri-too-long' => 'Hver omdirigerings-URI kan være på maksimalt :max tegn.',
            'rate-limited' => 'For mange registreringsforsøk. Prøv igjen senere.',
        ],
    ],
];
