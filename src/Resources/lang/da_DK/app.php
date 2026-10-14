<?php

return [
    'authorize' => [
        'title' => 'Godkend :client',
        'heading' => 'Godkend :client',
        'lede' => 'Denne applikation anmoder om adgang til din UnoPim-konto.',
        'signed-in-as' => 'Logget ind som',
        'permissions' => 'Den vil kunne',
        'approve' => 'Godkend',
        'approving' => 'Godkender…',
        'deny' => 'Annuller',
        'footnote' => 'Adgangen følger din eksisterende UnoPim-rolle og dine tilladelser.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'Valutaen [:code] bruges af en kanal og kan ikke deaktiveres.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produktet [:sku] (products.:index) er ugyldigt; intet i batchen blev gemt.',
        ],

        'filters' => [
            'missing-field' => 'Hvert filter skal have et felt.',
            'invalid-field' => 'Der kan ikke filtreres på feltet ":field". Brug et af disse: :fields.',
            'invalid-operator' => 'Operatoren ":operator" understøttes ikke. Brug en af disse: :operators.',
            'invalid-value' => 'Filteret ":operator" på ":field" tager én værdi, ikke en liste.',
            'invalid-cursor' => 'Pagineringsmarkøren er ugyldig eller hører til en anden søgning. Kør søgningen igen uden markør.',
            'invalid-date' => 'Filteret ":field" kræver en dato som 2026-01-31 eller 2026-01-31 13:45:00; ":value" er ikke en dato.',
            'invalid-number' => 'Filteret ":field" kræver et tal; ":value" er ikke et tal.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Typen af attributten [:code] kan ikke ændres, efter den er oprettet.',
            'category-invalid-parent' => 'Kategorien [:code] kan ikke placeres under sig selv eller under en af sine egne underkategorier.',
            'super-attributes-invalid' => 'Superattributterne [:attributes] skal være valgattributter i familien [:family], som ikke varierer efter sprog eller kanal.',
            'parent-not-found' => 'Det overordnede produkt [:sku] findes ikke eller er ikke et konfigurerbart produkt.',
            'parent-unsupported' => 'Det overordnede produkt [:sku] bruger en variantstruktur med to niveauer. Opret dets varianter via UnoPims REST API.',
            'parent-immutable' => 'Produktet [:sku] findes allerede; dets overordnede produkt kan ikke ændres.',
            'variant-axis-invalid' => 'Varianten [:sku] skal have en gyldig mulighed for hver variantattribut i det overordnede produkt: :attributes.',
            'variant-exists' => 'Det overordnede produkt [:parent] har allerede en variant med SKU [:sku] eller med de samme variantattributværdier.',
            'variant-values-taken' => 'Varianten [:sku] kan ikke få disse variantattributværdier: overordnet produkt [:parent] har allerede en variant med dem.',
            'product-create-incomplete' => 'Produktet [:sku] findes ikke. Både type og attribute_family_id kræves for at oprette det.',
            'values-section-misplaced' => ':key er ikke en attribut. Send den på øverste niveau af values (values.:key), ikke inde i values.:section.',
            'values-attribute-section' => 'Attributten [:code] hører til i :path.',
            'values-price-shape' => 'Prisattributten [:code] tager et objekt med valutakode og beløb, f.eks. {"USD": "19.99"}, ikke en liste.',
            'values-currency-inactive' => 'Valutaen [:currencies] er ikke aktiv. Brug en af: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Forbudt: Du har ikke tilladelse til at køre [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Kun en enkelt SELECT-, WITH-, SHOW-, EXPLAIN- eller DESCRIBE-sætning er tilladt.',
            'forbidden' => 'Forespørgslen bruger :keyword, hvilket ikke er tilladt i en skrivebeskyttet forespørgsel.',
        ],

        'run-skill' => [
            'not-found' => 'Færdigheden [:name] blev ikke fundet.',
        ],

        'command-runner' => [
            'blocked' => 'Kommandoen [:command] er ikke tilladt, fordi den kan køre vilkårlig kode.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Pluginnavnet [:name] er ugyldigt. Brug kun bogstaver og tal, og start med et stort bogstav.',
        ],

        'test-generator' => [
            'invalid-package' => 'Pakkenavnet [:name] er ugyldigt. Brug et navn som MyPlugin eller Webkul/MyPlugin.',
            'invalid-class' => 'Klassenavnet [:name] er ugyldigt. Brug et navn som Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method skal være S256; plain-metoden understøttes ikke.',

        'registration' => [
            'body-too-large' => 'Registreringsanmodningen er for stor. Grænsen er :max bytes.',
            'too-many-redirect-uris' => 'For mange omdirigerings-URI\'er. Højst :max er tilladt.',
            'redirect-uri-too-long' => 'Hver omdirigerings-URI må højst være :max tegn lang.',
            'rate-limited' => 'For mange registreringsforsøg. Prøv igen senere.',
        ],
    ],
];
