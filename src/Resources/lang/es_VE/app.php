<?php

return [
    'authorize' => [
        'title' => 'Autorizar :client',
        'heading' => 'Autorizar :client',
        'lede' => 'Esta aplicación solicita acceso a tu cuenta de UnoPim.',
        'signed-in-as' => 'Sesión iniciada como',
        'permissions' => 'Podrá',
        'approve' => 'Autorizar',
        'approving' => 'Autorizando…',
        'deny' => 'Cancelar',
        'footnote' => 'El acceso sigue tu rol y permisos actuales de UnoPim.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'La moneda [:code] se usa en un canal y no se puede desactivar.',
        ],
        'upsert-products' => [
            'invalid-values' => 'El producto [:sku] (products.:index) no es válido; no se guardó nada del lote.',
        ],

        'filters' => [
            'missing-field' => 'Cada filtro necesita un campo.',
            'invalid-field' => 'No se puede filtrar por el campo ":field". Usa uno de estos: :fields.',
            'invalid-operator' => 'El operador ":operator" no es compatible. Usa uno de estos: :operators.',
            'invalid-value' => 'El filtro ":operator" sobre ":field" admite un solo valor, no una lista.',
            'invalid-cursor' => 'El cursor de paginación no es válido o pertenece a otra búsqueda. Vuelve a ejecutar la búsqueda sin cursor.',
            'invalid-date' => 'El filtro ":field" necesita una fecha como 2026-01-31 o 2026-01-31 13:45:00; ":value" no es una fecha.',
            'invalid-number' => 'El filtro ":field" necesita un número; ":value" no es un número.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'El tipo del atributo [:code] no se puede cambiar después de crearlo.',
            'category-invalid-parent' => 'La categoría [:code] no se puede ubicar debajo de sí misma ni de una de sus propias subcategorías.',
            'super-attributes-invalid' => 'Los superatributos [:attributes] deben ser atributos de selección de la familia [:family] que no varíen por idioma ni por canal.',
            'parent-not-found' => 'El producto padre [:sku] no existe o no es un producto configurable.',
            'parent-unsupported' => 'El producto padre [:sku] usa una estructura de variantes de dos niveles. Crea sus variantes mediante la API REST de UnoPim.',
            'parent-immutable' => 'El producto [:sku] ya existe; no se puede cambiar su producto padre.',
            'variant-axis-invalid' => 'La variante [:sku] necesita una opción válida para cada atributo de variante de su producto padre: :attributes.',
            'variant-exists' => 'El producto padre [:parent] ya tiene una variante con el SKU [:sku] o con los mismos valores de atributos de variante.',
            'variant-values-taken' => 'La variante [:sku] no puede tomar estos valores de atributos de variante: el producto padre [:parent] ya tiene una variante con ellos.',
            'product-create-incomplete' => 'El producto [:sku] no existe. Se requieren type y attribute_family_id para crearlo.',
            'values-section-misplaced' => ':key no es un atributo. Envíelo en el nivel superior de values (values.:key), no dentro de values.:section.',
            'values-attribute-section' => 'El atributo [:code] va en :path.',
            'values-price-shape' => 'El atributo de precio [:code] recibe un objeto de código de moneda a monto, como {"USD": "19.99"}, no una lista.',
            'values-currency-inactive' => 'La moneda [:currencies] no está activa. Use una de: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Prohibido: no tiene permiso para ejecutar [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Solo se permite una única sentencia SELECT, WITH, SHOW, EXPLAIN o DESCRIBE.',
            'forbidden' => 'La consulta usa :keyword, que no está permitido en una consulta de solo lectura.',
        ],

        'run-skill' => [
            'not-found' => 'No se encontró la habilidad [:name].',
        ],

        'command-runner' => [
            'blocked' => 'El comando [:command] no está permitido porque puede ejecutar código arbitrario.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'El nombre del plugin [:name] no es válido. Use solo letras y dígitos, comenzando con una mayúscula.',
        ],

        'test-generator' => [
            'invalid-package' => 'El nombre del paquete [:name] no es válido. Use un nombre como MyPlugin o Webkul/MyPlugin.',
            'invalid-class' => 'El nombre de la clase [:name] no es válido. Use un nombre como Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'El code_challenge_method debe ser S256; el método plain no es compatible.',

        'registration' => [
            'body-too-large' => 'La solicitud de registro es demasiado grande. El límite es de :max bytes.',
            'too-many-redirect-uris' => 'Demasiados URI de redirección. Se permiten como máximo :max.',
            'redirect-uri-too-long' => 'Cada URI de redirección debe tener como máximo :max caracteres.',
            'rate-limited' => 'Demasiados intentos de registro. Por favor, inténtelo más tarde.',
        ],
    ],
];
