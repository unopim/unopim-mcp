<?php

return [
    'authorize' => [
        'title' => 'Autoriseren :client',
        'heading' => 'Autoriseren :client',
        'lede' => 'Deze applicatie vraagt toegang tot je UnoPim-account.',
        'signed-in-as' => 'Ingelogd als',
        'permissions' => 'Het kan',
        'approve' => 'Autoriseren',
        'approving' => 'Autoriseren…',
        'deny' => 'Annuleren',
        'footnote' => 'De toegang volgt je bestaande UnoPim-rol en rechten.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Taakuitvoering niet gevonden.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Valuta [:code] wordt door een kanaal gebruikt en kan niet worden uitgeschakeld.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Product [:sku] (products.:index) is ongeldig; er is niets uit de batch opgeslagen.',
        ],

        'filters' => [
            'missing-field' => 'Elk filter heeft een veld nodig.',
            'invalid-field' => 'Op het veld ":field" kan niet worden gefilterd. Gebruik een van: :fields.',
            'invalid-operator' => 'De operator ":operator" wordt niet ondersteund. Gebruik een van: :operators.',
            'invalid-value' => 'Het filter ":operator" op ":field" accepteert één waarde, geen lijst.',
            'invalid-cursor' => 'De pagineringscursor is ongeldig of hoort bij een andere zoekopdracht. Voer de zoekopdracht opnieuw uit zonder cursor.',
            'invalid-date' => 'Het filter ":field" heeft een datum nodig zoals 2026-01-31 of 2026-01-31 13:45:00; ":value" is geen datum.',
            'invalid-number' => 'Het filter ":field" heeft een getal nodig; ":value" is geen getal.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Het type van attribuut [:code] kan na het aanmaken niet meer worden gewijzigd.',
            'category-invalid-parent' => 'Categorie [:code] kan niet onder zichzelf of onder een van haar eigen subcategorieën worden geplaatst.',
            'super-attributes-invalid' => 'Superattributen [:attributes] moeten selectieattributen van familie [:family] zijn die niet per taal of kanaal verschillen.',
            'parent-not-found' => 'Bovenliggend product [:sku] bestaat niet of is geen configureerbaar product.',
            'parent-unsupported' => 'Bovenliggend product [:sku] gebruikt een variantstructuur met twee niveaus. Maak de varianten aan via de UnoPim REST API.',
            'parent-immutable' => 'Product [:sku] bestaat al; het bovenliggende product kan niet worden gewijzigd.',
            'variant-axis-invalid' => 'Variant [:sku] heeft een geldige optie nodig voor elk variantattribuut van het bovenliggende product: :attributes.',
            'variant-exists' => 'Bovenliggend product [:parent] heeft al een variant met SKU [:sku] of met dezelfde variantattribuutwaarden.',
            'variant-values-taken' => 'Variant [:sku] kan deze variantattribuutwaarden niet krijgen: hoofdproduct [:parent] heeft al een variant met deze waarden.',
            'product-create-incomplete' => 'Product [:sku] bestaat niet. Zowel type als attribute_family_id zijn vereist om het aan te maken.',
            'values-section-misplaced' => ':key is geen attribuut. Stuur het op het hoogste niveau van values (values.:key), niet binnen values.:section.',
            'values-attribute-section' => 'Attribuut [:code] hoort in :path.',
            'values-price-shape' => 'Prijsattribuut [:code] verwacht een object van valutacode naar bedrag, zoals {"USD": "19.99"}, geen lijst.',
            'values-currency-inactive' => 'Valuta [:currencies] is niet actief. Gebruik een van: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Verboden: je hebt geen toestemming om [:tool] uit te voeren.',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Alleen één SELECT-, WITH-, SHOW-, EXPLAIN- of DESCRIBE-instructie is toegestaan.',
            'forbidden' => 'De query gebruikt :keyword, wat niet is toegestaan in een alleen-lezen query.',
        ],

        'run-skill' => [
            'not-found' => 'De skill [:name] is niet gevonden.',
        ],

        'command-runner' => [
            'blocked' => 'De opdracht [:command] is niet toegestaan omdat deze willekeurige code kan uitvoeren.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'De pluginnaam [:name] is ongeldig. Gebruik alleen letters en cijfers en begin met een hoofdletter.',
        ],

        'test-generator' => [
            'invalid-package' => 'De pakketnaam [:name] is ongeldig. Gebruik een naam zoals MyPlugin of Webkul/MyPlugin.',
            'invalid-class' => 'De klassenaam [:name] is ongeldig. Gebruik een naam zoals Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'De code_challenge_method moet S256 zijn; de methode plain wordt niet ondersteund.',

        'registration' => [
            'body-too-large' => 'Het registratieverzoek is te groot. De limiet is :max bytes.',
            'too-many-redirect-uris' => 'Te veel omleidings-URI\'s. Er zijn er maximaal :max toegestaan.',
            'redirect-uri-too-long' => 'Elke omleidings-URI mag maximaal :max tekens lang zijn.',
            'rate-limited' => 'Te veel registratiepogingen. Probeer het later opnieuw.',
        ],
    ],
];
