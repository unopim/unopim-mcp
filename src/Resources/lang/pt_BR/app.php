<?php

return [
    'authorize' => [
        'title' => 'Autorizar :client',
        'heading' => 'Autorizar :client',
        'lede' => 'Este aplicativo está solicitando acesso à sua conta do UnoPim.',
        'signed-in-as' => 'Conectado como',
        'permissions' => 'Ele poderá',
        'approve' => 'Autorizar',
        'approving' => 'Autorizando…',
        'deny' => 'Cancelar',
        'footnote' => 'O acesso segue sua função e suas permissões atuais no UnoPim.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'A moeda [:code] é usada por um canal e não pode ser desativada.',
        ],
        'upsert-products' => [
            'invalid-values' => 'O produto [:sku] (products.:index) não é válido; nada do lote foi salvo.',
        ],

        'filters' => [
            'missing-field' => 'Cada filtro precisa de um campo.',
            'invalid-field' => 'Não é possível filtrar pelo campo ":field". Use um destes: :fields.',
            'invalid-operator' => 'O operador ":operator" não é compatível. Use um destes: :operators.',
            'invalid-value' => 'O filtro ":operator" em ":field" aceita um único valor, não uma lista.',
            'invalid-cursor' => 'O cursor de paginação é inválido ou pertence a outra busca. Execute a busca novamente sem cursor.',
            'invalid-date' => 'O filtro ":field" precisa de uma data como 2026-01-31 ou 2026-01-31 13:45:00; ":value" não é uma data.',
            'invalid-number' => 'O filtro ":field" precisa de um número; ":value" não é um número.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'O tipo do atributo [:code] não pode ser alterado depois de criado.',
            'category-invalid-parent' => 'A categoria [:code] não pode ser colocada sob ela mesma nem sob uma de suas próprias subcategorias.',
            'super-attributes-invalid' => 'Os superatributos [:attributes] devem ser atributos de seleção da família [:family] que não variem por idioma nem por canal.',
            'parent-not-found' => 'O produto pai [:sku] não existe ou não é um produto configurável.',
            'parent-unsupported' => 'O produto pai [:sku] usa uma estrutura de variantes de dois níveis. Crie as variantes dele pela API REST do UnoPim.',
            'parent-immutable' => 'O produto [:sku] já existe; o produto pai dele não pode ser alterado.',
            'variant-axis-invalid' => 'A variante [:sku] precisa de uma opção válida para cada atributo de variante do produto pai: :attributes.',
            'variant-exists' => 'O produto pai [:parent] já tem uma variante com o SKU [:sku] ou com os mesmos valores de atributos de variante.',
            'variant-values-taken' => 'A variante [:sku] não pode receber esses valores de atributos de variante: o produto pai [:parent] já tem uma variante com eles.',
            'product-create-incomplete' => 'O produto [:sku] não existe. type e attribute_family_id são obrigatórios para criá-lo.',
            'values-section-misplaced' => ':key não é um atributo. Envie-o no nível superior de values (values.:key), não dentro de values.:section.',
            'values-attribute-section' => 'O atributo [:code] fica em :path.',
            'values-price-shape' => 'O atributo de preço [:code] recebe um objeto de código de moeda para valor, como {"USD": "19.99"}, não uma lista.',
            'values-currency-inactive' => 'A moeda [:currencies] não está ativa. Use uma destas: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Proibido: você não tem permissão para executar [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Apenas uma única instrução SELECT, WITH, SHOW, EXPLAIN ou DESCRIBE é permitida.',
            'forbidden' => 'A consulta usa :keyword, o que não é permitido em uma consulta somente leitura.',
        ],

        'run-skill' => [
            'not-found' => 'A skill [:name] não foi encontrada.',
        ],

        'command-runner' => [
            'blocked' => 'O comando [:command] não é permitido porque pode executar código arbitrário.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'O nome do plugin [:name] é inválido. Use apenas letras e números, começando com uma letra maiúscula.',
        ],

        'test-generator' => [
            'invalid-package' => 'O nome do pacote [:name] é inválido. Use um nome como MyPlugin ou Webkul/MyPlugin.',
            'invalid-class' => 'O nome da classe [:name] é inválido. Use um nome como Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'O code_challenge_method deve ser S256; o método plain não é suportado.',

        'registration' => [
            'body-too-large' => 'A solicitação de registro é grande demais. O limite é de :max bytes.',
            'too-many-redirect-uris' => 'URIs de redirecionamento em excesso. São permitidos no máximo :max.',
            'redirect-uri-too-long' => 'Cada URI de redirecionamento deve ter no máximo :max caracteres.',
            'rate-limited' => 'Muitas tentativas de registro. Tente novamente mais tarde.',
        ],
    ],
];
