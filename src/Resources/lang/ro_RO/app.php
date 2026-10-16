<?php

return [
    'authorize' => [
        'title' => 'Autorizează :client',
        'heading' => 'Autorizează :client',
        'lede' => 'Această aplicație solicită acces la contul tău UnoPim.',
        'signed-in-as' => 'Autentificat ca',
        'permissions' => 'Va putea să',
        'approve' => 'Autorizează',
        'approving' => 'Se autorizează…',
        'deny' => 'Anulează',
        'footnote' => 'Accesul respectă rolul și permisiunile tale actuale din UnoPim.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Execuția jobului nu a fost găsită.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Moneda [:code] este folosită de un canal și nu poate fi dezactivată.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produsul [:sku] (products.:index) nu este valid; nu s-a salvat nimic din lot.',
        ],

        'filters' => [
            'missing-field' => 'Fiecare filtru are nevoie de un câmp.',
            'invalid-field' => 'Nu se poate filtra după câmpul ":field". Folosiți unul dintre: :fields.',
            'invalid-operator' => 'Operatorul ":operator" nu este acceptat. Folosiți unul dintre: :operators.',
            'invalid-value' => 'Filtrul ":operator" pe ":field" acceptă o singură valoare, nu o listă.',
            'invalid-cursor' => 'Cursorul de paginare este invalid sau aparține altei căutări. Rulați din nou căutarea fără cursor.',
            'invalid-date' => 'Filtrul ":field" necesită o dată precum 2026-01-31 sau 2026-01-31 13:45:00; ":value" nu este o dată.',
            'invalid-number' => 'Filtrul ":field" necesită un număr; ":value" nu este un număr.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Tipul atributului [:code] nu poate fi schimbat după creare.',
            'category-invalid-parent' => 'Categoria [:code] nu poate fi plasată sub ea însăși sau sub una dintre propriile subcategorii.',
            'super-attributes-invalid' => 'Superatributele [:attributes] trebuie să fie atribute de selecție ale familiei [:family] care nu variază în funcție de limbă sau canal.',
            'parent-not-found' => 'Produsul părinte [:sku] nu există sau nu este un produs configurabil.',
            'parent-unsupported' => 'Produsul părinte [:sku] folosește o structură de variante pe două niveluri. Creați variantele prin API-ul REST UnoPim.',
            'parent-immutable' => 'Produsul [:sku] există deja; produsul său părinte nu poate fi schimbat.',
            'variant-axis-invalid' => 'Varianta [:sku] are nevoie de o opțiune validă pentru fiecare atribut de variantă al produsului părinte: :attributes.',
            'variant-exists' => 'Produsul părinte [:parent] are deja o variantă cu SKU-ul [:sku] sau cu aceleași valori ale atributelor de variantă.',
            'variant-values-taken' => 'Varianta [:sku] nu poate primi aceste valori ale atributelor de variantă: produsul părinte [:parent] are deja o variantă cu ele.',
            'product-create-incomplete' => 'Produsul [:sku] nu există. Pentru a-l crea sunt necesare atât type, cât și attribute_family_id.',
            'values-section-misplaced' => ':key nu este un atribut. Trimiteți-l la nivelul superior din values (values.:key), nu în interiorul values.:section.',
            'values-attribute-section' => 'Atributul [:code] se pune în :path.',
            'values-price-shape' => 'Atributul de preț [:code] primește un obiect cod monedă–sumă, de exemplu {"USD": "19.99"}, nu o listă.',
            'values-currency-inactive' => 'Moneda [:currencies] nu este activă. Folosiți una dintre: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Interzis: nu aveți permisiunea de a rula [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Este permisă o singură instrucțiune SELECT, WITH, SHOW, EXPLAIN sau DESCRIBE.',
            'forbidden' => 'Interogarea folosește :keyword, care nu este permis într-o interogare doar pentru citire.',
        ],

        'run-skill' => [
            'not-found' => 'Abilitatea [:name] nu a fost găsită.',
        ],

        'command-runner' => [
            'blocked' => 'Comanda [:command] nu este permisă deoarece poate rula cod arbitrar.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Numele pluginului [:name] nu este valid. Folosiți doar litere și cifre, începând cu o literă mare.',
        ],

        'test-generator' => [
            'invalid-package' => 'Numele pachetului [:name] nu este valid. Folosiți un nume precum MyPlugin sau Webkul/MyPlugin.',
            'invalid-class' => 'Numele clasei [:name] nu este valid. Folosiți un nume precum Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method trebuie să fie S256; metoda plain nu este acceptată.',

        'registration' => [
            'body-too-large' => 'Cererea de înregistrare este prea mare. Limita este de :max octeți.',
            'too-many-redirect-uris' => 'Prea multe URI-uri de redirecționare. Sunt permise cel mult :max.',
            'redirect-uri-too-long' => 'Fiecare URI de redirecționare poate avea cel mult :max caractere.',
            'rate-limited' => 'Prea multe încercări de înregistrare. Încercați din nou mai târziu.',
        ],
    ],
];
