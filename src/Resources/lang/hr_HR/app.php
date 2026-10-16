<?php

return [
    'authorize' => [
        'title' => 'Autoriziraj :client',
        'heading' => 'Autoriziraj :client',
        'lede' => 'Ova aplikacija traži pristup vašem UnoPim računu.',
        'signed-in-as' => 'Prijavljeni ste kao',
        'permissions' => 'Moći će',
        'approve' => 'Autoriziraj',
        'approving' => 'Autorizacija u tijeku…',
        'deny' => 'Odustani',
        'footnote' => 'Pristup slijedi vašu postojeću UnoPim ulogu i dopuštenja.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Izvršavanje posla nije pronađeno.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Valuta [:code] koristi se u kanalu i ne može se onemogućiti.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Proizvod [:sku] (products.:index) nije valjan; ništa iz skupa nije spremljeno.',
        ],

        'filters' => [
            'missing-field' => 'Svaki filtar treba polje.',
            'invalid-field' => 'Po polju ":field" nije moguće filtrirati. Koristite jedno od: :fields.',
            'invalid-operator' => 'Operator ":operator" nije podržan. Koristite jedan od: :operators.',
            'invalid-value' => 'Filtar ":operator" na polju ":field" prima jednu vrijednost, a ne popis.',
            'invalid-cursor' => 'Pokazivač stranica nije valjan ili pripada drugoj pretrazi. Ponovno pokrenite pretragu bez pokazivača.',
            'invalid-date' => 'Filtar ":field" treba datum poput 2026-01-31 ili 2026-01-31 13:45:00; ":value" nije datum.',
            'invalid-number' => 'Filtar ":field" treba broj; ":value" nije broj.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Vrsta atributa [:code] ne može se promijeniti nakon što je stvoren.',
            'category-invalid-parent' => 'Kategorija [:code] ne može se smjestiti pod samu sebe ni pod neku od svojih potkategorija.',
            'super-attributes-invalid' => 'Superatributi [:attributes] moraju biti atributi odabira obitelji [:family] koji se ne razlikuju po jeziku ili kanalu.',
            'parent-not-found' => 'Nadređeni proizvod [:sku] ne postoji ili nije konfigurabilni proizvod.',
            'parent-unsupported' => 'Nadređeni proizvod [:sku] koristi dvorazinsku strukturu varijanti. Njegove varijante stvorite putem UnoPim REST API-ja.',
            'parent-immutable' => 'Proizvod [:sku] već postoji; njegov nadređeni proizvod ne može se promijeniti.',
            'variant-axis-invalid' => 'Varijanta [:sku] treba valjanu opciju za svaki atribut varijante nadređenog proizvoda: :attributes.',
            'variant-exists' => 'Nadređeni proizvod [:parent] već ima varijantu sa SKU-om [:sku] ili s istim vrijednostima atributa varijante.',
            'variant-values-taken' => 'Varijanta [:sku] ne može preuzeti ove vrijednosti atributa varijante: nadređeni proizvod [:parent] već ima varijantu s njima.',
            'product-create-incomplete' => 'Proizvod [:sku] ne postoji. Za njegovo stvaranje potrebni su i type i attribute_family_id.',
            'values-section-misplaced' => ':key nije atribut. Pošaljite ga na najvišoj razini values (values.:key), a ne unutar values.:section.',
            'values-attribute-section' => 'Atribut [:code] ide u :path.',
            'values-price-shape' => 'Atribut cijene [:code] prima objekt s kodom valute i iznosom, npr. {"USD": "19.99"}, a ne popis.',
            'values-currency-inactive' => 'Valuta [:currencies] nije aktivna. Koristite jednu od: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Zabranjeno: nemate dopuštenje za pokretanje [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Dopuštena je samo jedna naredba SELECT, WITH, SHOW, EXPLAIN ili DESCRIBE.',
            'forbidden' => 'Upit koristi :keyword, što nije dopušteno u upitu samo za čitanje.',
        ],

        'run-skill' => [
            'not-found' => 'Vještina [:name] nije pronađena.',
        ],

        'command-runner' => [
            'blocked' => 'Naredba [:command] nije dopuštena jer može izvršiti proizvoljan kod.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Naziv dodatka [:name] nije valjan. Koristite samo slova i znamenke, počevši velikim slovom.',
        ],

        'test-generator' => [
            'invalid-package' => 'Naziv paketa [:name] nije valjan. Koristite naziv poput MyPlugin ili Webkul/MyPlugin.',
            'invalid-class' => 'Naziv klase [:name] nije valjan. Koristite naziv poput Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method mora biti S256; metoda plain nije podržana.',

        'registration' => [
            'body-too-large' => 'Zahtjev za registraciju je prevelik. Ograničenje je :max bajtova.',
            'too-many-redirect-uris' => 'Previše URI-ja za preusmjeravanje. Dopušteno je najviše :max.',
            'redirect-uri-too-long' => 'Svaki URI za preusmjeravanje smije imati najviše :max znakova.',
            'rate-limited' => 'Previše pokušaja registracije. Pokušajte ponovno kasnije.',
        ],
    ],
];
