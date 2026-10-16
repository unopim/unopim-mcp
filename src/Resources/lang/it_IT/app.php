<?php

return [
    'authorize' => [
        'title' => 'Autorizza :client',
        'heading' => 'Autorizza :client',
        'lede' => 'Questa applicazione richiede l\'accesso al tuo account UnoPim.',
        'signed-in-as' => 'Accesso effettuato come',
        'permissions' => 'Potrà',
        'approve' => 'Autorizza',
        'approving' => 'Autorizzazione in corso…',
        'deny' => 'Annulla',
        'footnote' => 'L\'accesso segue il tuo ruolo e i tuoi permessi UnoPim esistenti.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Esecuzione del job non trovata.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'La valuta [:code] è usata da un canale e non può essere disattivata.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Il prodotto [:sku] (products.:index) non è valido; non è stato salvato nulla del lotto.',
        ],

        'filters' => [
            'missing-field' => 'Ogni filtro richiede un campo.',
            'invalid-field' => 'Non è possibile filtrare per il campo ":field". Usa uno dei seguenti: :fields.',
            'invalid-operator' => 'L’operatore ":operator" non è supportato. Usa uno dei seguenti: :operators.',
            'invalid-value' => 'Il filtro ":operator" su ":field" accetta un solo valore, non un elenco.',
            'invalid-cursor' => 'Il cursore di paginazione non è valido o appartiene a un’altra ricerca. Esegui di nuovo la ricerca senza cursore.',
            'invalid-date' => 'Il filtro ":field" richiede una data come 2026-01-31 o 2026-01-31 13:45:00; ":value" non è una data.',
            'invalid-number' => 'Il filtro ":field" richiede un numero; ":value" non è un numero.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Il tipo dell’attributo [:code] non può essere modificato dopo la creazione.',
            'category-invalid-parent' => 'La categoria [:code] non può essere collocata sotto sé stessa o sotto una delle sue sottocategorie.',
            'super-attributes-invalid' => 'I super attributi [:attributes] devono essere attributi di selezione della famiglia [:family] che non variano per lingua o canale.',
            'parent-not-found' => 'Il prodotto padre [:sku] non esiste o non è un prodotto configurabile.',
            'parent-unsupported' => 'Il prodotto padre [:sku] usa una struttura di varianti a due livelli. Crea le sue varianti tramite la REST API di UnoPim.',
            'parent-immutable' => 'Il prodotto [:sku] esiste già; il suo prodotto padre non può essere modificato.',
            'variant-axis-invalid' => 'La variante [:sku] richiede un’opzione valida per ogni attributo di variante del prodotto padre: :attributes.',
            'variant-exists' => 'Il prodotto padre [:parent] ha già una variante con lo SKU [:sku] o con gli stessi valori degli attributi di variante.',
            'variant-values-taken' => 'La variante [:sku] non può assumere questi valori degli attributi di variante: il prodotto padre [:parent] ha già una variante con questi valori.',
            'product-create-incomplete' => 'Il prodotto [:sku] non esiste. Per crearlo servono sia type sia attribute_family_id.',
            'values-section-misplaced' => ':key non è un attributo. Invialo al livello principale di values (values.:key), non dentro values.:section.',
            'values-attribute-section' => 'L\'attributo [:code] va in :path.',
            'values-price-shape' => 'L\'attributo prezzo [:code] accetta un oggetto da codice valuta a importo, come {"USD": "19.99"}, non un elenco.',
            'values-currency-inactive' => 'La valuta [:currencies] non è attiva. Usane una tra: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Vietato: non hai l\'autorizzazione per eseguire [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'È consentita una sola istruzione SELECT, WITH, SHOW, EXPLAIN o DESCRIBE.',
            'forbidden' => 'La query usa :keyword, che non è consentito in una query di sola lettura.',
        ],

        'run-skill' => [
            'not-found' => 'La skill [:name] non è stata trovata.',
        ],

        'command-runner' => [
            'blocked' => 'Il comando [:command] non è consentito perché può eseguire codice arbitrario.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Il nome del plugin [:name] non è valido. Usa solo lettere e cifre, iniziando con una lettera maiuscola.',
        ],

        'test-generator' => [
            'invalid-package' => 'Il nome del pacchetto [:name] non è valido. Usa un nome come MyPlugin o Webkul/MyPlugin.',
            'invalid-class' => 'Il nome della classe [:name] non è valido. Usa un nome come Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Il code_challenge_method deve essere S256; il metodo plain non è supportato.',

        'registration' => [
            'body-too-large' => 'La richiesta di registrazione è troppo grande. Il limite è di :max byte.',
            'too-many-redirect-uris' => 'Troppi URI di reindirizzamento. Ne sono consentiti al massimo :max.',
            'redirect-uri-too-long' => 'Ogni URI di reindirizzamento può contenere al massimo :max caratteri.',
            'rate-limited' => 'Troppi tentativi di registrazione. Riprova più tardi.',
        ],
    ],
];
