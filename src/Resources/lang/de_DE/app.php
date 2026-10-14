<?php

return [
    'authorize' => [
        'title' => 'Autorisieren :client',
        'heading' => 'Autorisieren :client',
        'lede' => 'Diese Anwendung fordert Zugriff auf Ihr UnoPim-Konto an.',
        'signed-in-as' => 'Angemeldet als',
        'permissions' => 'Sie kann',
        'approve' => 'Autorisieren',
        'approving' => 'Wird autorisiert…',
        'deny' => 'Abbrechen',
        'footnote' => 'Der Zugriff richtet sich nach Ihrer bestehenden UnoPim-Rolle und Ihren Berechtigungen.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'Die Währung [:code] wird von einem Kanal verwendet und kann nicht deaktiviert werden.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Das Produkt [:sku] (products.:index) ist ungültig; aus dem Stapel wurde nichts gespeichert.',
        ],

        'filters' => [
            'missing-field' => 'Jeder Filter benötigt ein Feld.',
            'invalid-field' => 'Nach dem Feld ":field" kann nicht gefiltert werden. Verwenden Sie eines der folgenden: :fields.',
            'invalid-operator' => 'Der Operator ":operator" wird nicht unterstützt. Verwenden Sie einen der folgenden: :operators.',
            'invalid-value' => 'Der Filter ":operator" auf ":field" erwartet einen einzelnen Wert, keine Liste.',
            'invalid-cursor' => 'Der Paginierungs-Cursor ist ungültig oder gehört zu einer anderen Suche. Führen Sie die Suche ohne Cursor erneut aus.',
            'invalid-date' => 'Der Filter ":field" benötigt ein Datum wie 2026-01-31 oder 2026-01-31 13:45:00; ":value" ist kein Datum.',
            'invalid-number' => 'Der Filter ":field" benötigt eine Zahl; ":value" ist keine Zahl.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Der Typ des Attributs [:code] kann nach dem Anlegen nicht mehr geändert werden.',
            'category-invalid-parent' => 'Die Kategorie [:code] kann nicht unter sich selbst oder einer ihrer eigenen Unterkategorien eingeordnet werden.',
            'super-attributes-invalid' => 'Die Superattribute [:attributes] müssen Auswahlattribute der Familie [:family] sein, die nicht je Gebietsschema oder Kanal variieren.',
            'parent-not-found' => 'Das übergeordnete Produkt [:sku] existiert nicht oder ist kein konfigurierbares Produkt.',
            'parent-unsupported' => 'Das übergeordnete Produkt [:sku] verwendet eine zweistufige Variantenstruktur. Legen Sie seine Varianten über die UnoPim-REST-API an.',
            'parent-immutable' => 'Das Produkt [:sku] existiert bereits; sein übergeordnetes Produkt kann nicht geändert werden.',
            'variant-axis-invalid' => 'Die Variante [:sku] benötigt eine gültige Option für jedes Variantenattribut ihres übergeordneten Produkts: :attributes.',
            'variant-exists' => 'Das übergeordnete Produkt [:parent] hat bereits eine Variante mit der SKU [:sku] oder mit denselben Variantenattributwerten.',
            'variant-values-taken' => 'Die Variante [:sku] kann diese Variantenattributwerte nicht erhalten: Das übergeordnete Produkt [:parent] hat bereits eine Variante mit diesen Werten.',
            'product-create-incomplete' => 'Das Produkt [:sku] existiert nicht. Zum Anlegen sind sowohl type als auch attribute_family_id erforderlich.',
            'values-section-misplaced' => ':key ist kein Attribut. Senden Sie es auf der obersten Ebene von values (values.:key), nicht innerhalb von values.:section.',
            'values-attribute-section' => 'Das Attribut [:code] gehört nach :path.',
            'values-price-shape' => 'Das Preisattribut [:code] erwartet ein Objekt aus Währungscode und Betrag, etwa {"USD": "19.99"}, keine Liste.',
            'values-currency-inactive' => 'Die Währung [:currencies] ist nicht aktiv. Verwenden Sie eine von: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Verboten: Sie haben keine Berechtigung, [:tool] auszuführen.',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Es ist nur eine einzelne SELECT-, WITH-, SHOW-, EXPLAIN- oder DESCRIBE-Anweisung erlaubt.',
            'forbidden' => 'Die Abfrage verwendet :keyword, was in einer schreibgeschützten Abfrage nicht erlaubt ist.',
        ],

        'run-skill' => [
            'not-found' => 'Der Skill [:name] wurde nicht gefunden.',
        ],

        'command-runner' => [
            'blocked' => 'Der Befehl [:command] ist nicht erlaubt, da er beliebigen Code ausführen kann.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Der Plugin-Name [:name] ist ungültig. Verwenden Sie nur Buchstaben und Ziffern und beginnen Sie mit einem Großbuchstaben.',
        ],

        'test-generator' => [
            'invalid-package' => 'Der Paketname [:name] ist ungültig. Verwenden Sie einen Namen wie MyPlugin oder Webkul/MyPlugin.',
            'invalid-class' => 'Der Klassenname [:name] ist ungültig. Verwenden Sie einen Namen wie Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Die code_challenge_method muss S256 sein; die Methode plain wird nicht unterstützt.',

        'registration' => [
            'body-too-large' => 'Die Registrierungsanfrage ist zu groß. Das Limit beträgt :max Bytes.',
            'too-many-redirect-uris' => 'Zu viele Weiterleitungs-URIs. Höchstens :max sind erlaubt.',
            'redirect-uri-too-long' => 'Jede Weiterleitungs-URI darf höchstens :max Zeichen lang sein.',
            'rate-limited' => 'Zu viele Registrierungsversuche. Bitte versuchen Sie es später erneut.',
        ],
    ],
];
