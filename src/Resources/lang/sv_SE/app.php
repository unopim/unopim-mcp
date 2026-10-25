<?php

return [
    'authorize' => [
        'title' => 'Godkänn :client',
        'heading' => 'Godkänn :client',
        'lede' => 'Det här programmet begär åtkomst till ditt UnoPim-konto.',
        'signed-in-as' => 'Inloggad som',
        'redirects-to' => 'Skickar dig vidare till',
        'permissions' => 'Det kommer att kunna',
        'approve' => 'Godkänn',
        'approving' => 'Godkänner…',
        'deny' => 'Avbryt',
        'footnote' => 'Åtkomsten följer din befintliga UnoPim-roll och dina behörigheter.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Jobbkörningen hittades inte.',
        ],
        'get-asset' => [
            'not-found' => 'Tillgången [:id] hittades inte.',
        ],
        'browse-asset-directories' => [
            'not-found' => 'Tillgångsmappen [:id] hittades inte.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Valutan [:code] används av en kanal och kan inte inaktiveras.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produkten [:sku] (products.:index) är ogiltig; inget i batchen sparades.',
        ],

        'filters' => [
            'missing-field' => 'Varje filter behöver ett fält.',
            'invalid-field' => 'Det går inte att filtrera på fältet ":field". Använd något av: :fields.',
            'invalid-operator' => 'Operatorn ":operator" stöds inte. Använd någon av: :operators.',
            'invalid-value' => 'Filtret ":operator" på ":field" tar ett enda värde, inte en lista.',
            'invalid-cursor' => 'Sidindelningsmarkören är ogiltig eller hör till en annan sökning. Kör sökningen igen utan markör.',
            'invalid-date' => 'Filtret ":field" kräver ett datum som 2026-01-31 eller 2026-01-31 13:45:00; ":value" är inte ett datum.',
            'invalid-number' => 'Filtret ":field" kräver ett tal; ":value" är inte ett tal.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Typen för attributet [:code] kan inte ändras efter att det har skapats.',
            'category-invalid-parent' => 'Kategorin [:code] kan inte placeras under sig själv eller under någon av sina egna underkategorier.',
            'super-attributes-invalid' => 'Superattributen [:attributes] måste vara valattribut i familjen [:family] som inte varierar per språk eller kanal.',
            'parent-not-found' => 'Överordnad produkt [:sku] finns inte eller är inte en konfigurerbar produkt.',
            'parent-unsupported' => 'Överordnad produkt [:sku] använder en variantstruktur i två nivåer. Skapa dess varianter via UnoPims REST-API.',
            'parent-immutable' => 'Produkten [:sku] finns redan; dess överordnade produkt kan inte ändras.',
            'variant-axis-invalid' => 'Varianten [:sku] behöver ett giltigt alternativ för varje variantattribut i den överordnade produkten: :attributes.',
            'variant-exists' => 'Överordnad produkt [:parent] har redan en variant med SKU [:sku] eller med samma variantattributvärden.',
            'variant-values-taken' => 'Varianten [:sku] kan inte få dessa variantattributvärden: överordnad produkt [:parent] har redan en variant med dem.',
            'product-create-incomplete' => 'Produkten [:sku] finns inte. Både type och attribute_family_id krävs för att skapa den.',
            'values-section-misplaced' => ':key är inte ett attribut. Skicka det på översta nivån i values (values.:key), inte inuti values.:section.',
            'values-attribute-section' => 'Attributet [:code] hör hemma i :path.',
            'values-price-shape' => 'Prisattributet [:code] tar ett objekt med valutakod och belopp, till exempel {"USD": "19.99"}, inte en lista.',
            'values-currency-inactive' => 'Valutan [:currencies] är inte aktiv. Använd en av: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Förbjudet: du har inte behörighet att köra [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Endast en enda SELECT-, WITH-, SHOW-, EXPLAIN- eller DESCRIBE-sats är tillåten.',
            'forbidden' => 'Frågan använder :keyword, vilket inte är tillåtet i en skrivskyddad fråga.',
        ],

        'run-skill' => [
            'not-found' => 'Färdigheten [:name] hittades inte.',
        ],

        'command-runner' => [
            'blocked' => 'Kommandot [:command] är inte tillåtet eftersom det kan köra godtycklig kod.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Tilläggsnamnet [:name] är ogiltigt. Använd bara bokstäver och siffror, och börja med en versal.',
        ],

        'test-generator' => [
            'invalid-package' => 'Paketnamnet [:name] är ogiltigt. Använd ett namn som MyPlugin eller Webkul/MyPlugin.',
            'invalid-class' => 'Klassnamnet [:name] är ogiltigt. Använd ett namn som Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method måste vara S256; metoden plain stöds inte.',

        'registration' => [
            'body-too-large' => 'Registreringsbegäran är för stor. Gränsen är :max byte.',
            'too-many-redirect-uris' => 'För många omdirigerings-URI:er. Högst :max tillåts.',
            'redirect-uri-too-long' => 'Varje omdirigerings-URI får vara högst :max tecken lång.',
            'rate-limited' => 'För många registreringsförsök. Försök igen senare.',
        ],
    ],
];
