<?php

return [
    'authorize' => [
        'title' => 'Autoryzuj :client',
        'heading' => 'Autoryzuj :client',
        'lede' => 'Ta aplikacja prosi o dostęp do Twojego konta UnoPim.',
        'signed-in-as' => 'Zalogowano jako',
        'redirects-to' => 'Przekierowuje do',
        'permissions' => 'Będzie mogła',
        'approve' => 'Autoryzuj',
        'approving' => 'Autoryzowanie…',
        'deny' => 'Anuluj',
        'footnote' => 'Dostęp zależy od Twojej obecnej roli i uprawnień w UnoPim.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Nie znaleziono wykonania zadania.',
        ],
        'get-asset' => [
            'not-found' => 'Nie znaleziono zasobu [:id].',
        ],
        'browse-asset-directories' => [
            'not-found' => 'Nie znaleziono katalogu zasobów [:id].',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Waluta [:code] jest używana przez kanał i nie można jej wyłączyć.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produkt [:sku] (products.:index) jest nieprawidłowy; nic z partii nie zostało zapisane.',
        ],

        'filters' => [
            'missing-field' => 'Każdy filtr wymaga pola.',
            'invalid-field' => 'Nie można filtrować według pola ":field". Użyj jednego z: :fields.',
            'invalid-operator' => 'Operator ":operator" nie jest obsługiwany. Użyj jednego z: :operators.',
            'invalid-value' => 'Filtr ":operator" na polu ":field" przyjmuje jedną wartość, a nie listę.',
            'invalid-cursor' => 'Kursor paginacji jest nieprawidłowy lub należy do innego wyszukiwania. Uruchom wyszukiwanie ponownie bez kursora.',
            'invalid-date' => 'Filtr ":field" wymaga daty, np. 2026-01-31 lub 2026-01-31 13:45:00; ":value" nie jest datą.',
            'invalid-number' => 'Filtr ":field" wymaga liczby; ":value" nie jest liczbą.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Typu atrybutu [:code] nie można zmienić po jego utworzeniu.',
            'category-invalid-parent' => 'Kategorii [:code] nie można umieścić pod nią samą ani pod żadną z jej własnych podkategorii.',
            'super-attributes-invalid' => 'Superatrybuty [:attributes] muszą być atrybutami wyboru z rodziny [:family], które nie różnią się w zależności od języka ani kanału.',
            'parent-not-found' => 'Produkt nadrzędny [:sku] nie istnieje lub nie jest produktem konfigurowalnym.',
            'parent-unsupported' => 'Produkt nadrzędny [:sku] używa dwupoziomowej struktury wariantów. Utwórz jego warianty przez REST API UnoPim.',
            'parent-immutable' => 'Produkt [:sku] już istnieje; nie można zmienić jego produktu nadrzędnego.',
            'variant-axis-invalid' => 'Wariant [:sku] wymaga prawidłowej opcji dla każdego atrybutu wariantu produktu nadrzędnego: :attributes.',
            'variant-exists' => 'Produkt nadrzędny [:parent] ma już wariant z SKU [:sku] lub z tymi samymi wartościami atrybutów wariantu.',
            'variant-values-taken' => 'Wariant [:sku] nie może przyjąć tych wartości atrybutów wariantu: produkt nadrzędny [:parent] ma już wariant z tymi wartościami.',
            'product-create-incomplete' => 'Produkt [:sku] nie istnieje. Do jego utworzenia wymagane są zarówno type, jak i attribute_family_id.',
            'values-section-misplaced' => ':key nie jest atrybutem. Wyślij go na najwyższym poziomie values (values.:key), a nie wewnątrz values.:section.',
            'values-attribute-section' => 'Atrybut [:code] należy umieścić w :path.',
            'values-price-shape' => 'Atrybut ceny [:code] przyjmuje obiekt kod waluty–kwota, np. {"USD": "19.99"}, a nie listę.',
            'values-currency-inactive' => 'Waluta [:currencies] nie jest aktywna. Użyj jednej z: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Zabronione: nie masz uprawnień do uruchomienia [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Dozwolona jest tylko jedna instrukcja SELECT, WITH, SHOW, EXPLAIN lub DESCRIBE.',
            'forbidden' => 'Zapytanie używa :keyword, co nie jest dozwolone w zapytaniu tylko do odczytu.',
        ],

        'run-skill' => [
            'not-found' => 'Nie znaleziono umiejętności [:name].',
        ],

        'command-runner' => [
            'blocked' => 'Polecenie [:command] jest niedozwolone, ponieważ może uruchomić dowolny kod.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Nazwa wtyczki [:name] jest nieprawidłowa. Używaj tylko liter i cyfr, zaczynając od wielkiej litery.',
        ],

        'test-generator' => [
            'invalid-package' => 'Nazwa pakietu [:name] jest nieprawidłowa. Użyj nazwy takiej jak MyPlugin lub Webkul/MyPlugin.',
            'invalid-class' => 'Nazwa klasy [:name] jest nieprawidłowa. Użyj nazwy takiej jak Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method musi mieć wartość S256; metoda plain nie jest obsługiwana.',

        'registration' => [
            'body-too-large' => 'Żądanie rejestracji jest zbyt duże. Limit wynosi :max bajtów.',
            'too-many-redirect-uris' => 'Zbyt wiele adresów URI przekierowania. Dozwolonych jest maksymalnie :max.',
            'redirect-uri-too-long' => 'Każdy adres URI przekierowania może mieć maksymalnie :max znaków.',
            'rate-limited' => 'Zbyt wiele prób rejestracji. Spróbuj ponownie później.',
        ],
    ],
];
