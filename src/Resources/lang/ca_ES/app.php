<?php

return [
    'authorize' => [
        'title' => 'Autoritza :client',
        'heading' => 'Autoritza :client',
        'lede' => 'Aquesta aplicació sol·licita accés al vostre compte d\'UnoPim.',
        'signed-in-as' => 'Sessió iniciada com a',
        'permissions' => 'Podrà',
        'approve' => 'Autoritza',
        'approving' => 'S\'està autoritzant…',
        'deny' => 'Cancel·la',
        'footnote' => 'L\'accés segueix el vostre rol i els vostres permisos actuals d\'UnoPim.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'La moneda [:code] s\'utilitza en un canal i no es pot desactivar.',
        ],
        'upsert-products' => [
            'invalid-values' => 'El producte [:sku] (products.:index) no és vàlid; no s\'ha desat res del lot.',
        ],

        'filters' => [
            'missing-field' => 'Cada filtre necessita un camp.',
            'invalid-field' => 'No es pot filtrar pel camp ":field". Feu servir un d’aquests: :fields.',
            'invalid-operator' => 'L’operador ":operator" no és compatible. Feu servir un d’aquests: :operators.',
            'invalid-value' => 'El filtre ":operator" sobre ":field" admet un sol valor, no una llista.',
            'invalid-cursor' => 'El cursor de paginació no és vàlid o pertany a una altra cerca. Torneu a fer la cerca sense cursor.',
            'invalid-date' => 'El filtre ":field" necessita una data com 2026-01-31 o 2026-01-31 13:45:00; ":value" no és una data.',
            'invalid-number' => 'El filtre ":field" necessita un número; ":value" no és un número.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'El tipus de l’atribut [:code] no es pot canviar un cop creat.',
            'category-invalid-parent' => 'La categoria [:code] no es pot col·locar sota ella mateixa ni sota cap de les seves pròpies subcategories.',
            'super-attributes-invalid' => 'Els superatributs [:attributes] han de ser atributs de selecció de la família [:family] que no variïn per idioma ni per canal.',
            'parent-not-found' => 'El producte pare [:sku] no existeix o no és un producte configurable.',
            'parent-unsupported' => 'El producte pare [:sku] fa servir una estructura de variants de dos nivells. Creeu-ne les variants mitjançant l’API REST d’UnoPim.',
            'parent-immutable' => 'El producte [:sku] ja existeix; no se’n pot canviar el producte pare.',
            'variant-axis-invalid' => 'La variant [:sku] necessita una opció vàlida per a cada atribut de variant del seu producte pare: :attributes.',
            'variant-exists' => 'El producte pare [:parent] ja té una variant amb l’SKU [:sku] o amb els mateixos valors d’atributs de variant.',
            'variant-values-taken' => 'La variant [:sku] no pot prendre aquests valors d\'atributs de variant: el producte pare [:parent] ja té una variant amb aquests valors.',
            'product-create-incomplete' => 'El producte [:sku] no existeix. Calen type i attribute_family_id per crear-lo.',
            'values-section-misplaced' => ':key no és un atribut. Envia\'l al nivell superior de values (values.:key), no dins de values.:section.',
            'values-attribute-section' => 'L\'atribut [:code] va a :path.',
            'values-price-shape' => 'L\'atribut de preu [:code] accepta un objecte de codi de moneda a import, com {"USD": "19.99"}, no una llista.',
            'values-currency-inactive' => 'La moneda [:currencies] no està activa. Fes servir una d\'aquestes: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Prohibit: no teniu permís per executar [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Només es permet una única sentència SELECT, WITH, SHOW, EXPLAIN o DESCRIBE.',
            'forbidden' => 'La consulta utilitza :keyword, que no es permet en una consulta de només lectura.',
        ],

        'run-skill' => [
            'not-found' => 'No s\'ha trobat l\'habilitat [:name].',
        ],

        'command-runner' => [
            'blocked' => 'L\'ordre [:command] no està permesa perquè pot executar codi arbitrari.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'El nom del connector [:name] no és vàlid. Utilitzeu només lletres i dígits, començant amb una majúscula.',
        ],

        'test-generator' => [
            'invalid-package' => 'El nom del paquet [:name] no és vàlid. Utilitzeu un nom com MyPlugin o Webkul/MyPlugin.',
            'invalid-class' => 'El nom de la classe [:name] no és vàlid. Utilitzeu un nom com Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'El code_challenge_method ha de ser S256; el mètode plain no és compatible.',

        'registration' => [
            'body-too-large' => 'La sol·licitud de registre és massa gran. El límit és de :max bytes.',
            'too-many-redirect-uris' => 'Hi ha massa URI de redirecció. Se\'n permeten com a màxim :max.',
            'redirect-uri-too-long' => 'Cada URI de redirecció pot tenir com a màxim :max caràcters.',
            'rate-limited' => 'Massa intents de registre. Torneu-ho a provar més tard.',
        ],
    ],
];
