<?php

return [
    'authorize' => [
        'title' => 'Autorizar :client',
        'heading' => 'Autorizar :client',
        'lede' => 'Esta aplicação está a pedir acesso à sua conta UnoPim.',
        'signed-in-as' => 'Sessão iniciada como',
        'permissions' => 'Poderá',
        'approve' => 'Autorizar',
        'approving' => 'A autorizar…',
        'deny' => 'Cancelar',
        'footnote' => 'O acesso segue a sua função e as suas permissões atuais no UnoPim.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Execução da tarefa não encontrada.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'A moeda [:code] é utilizada por um canal e não pode ser desativada.',
        ],
        'upsert-products' => [
            'invalid-values' => 'O produto [:sku] (products.:index) não é válido; nada do lote foi guardado.',
        ],

        'filters' => [
            'missing-field' => 'Cada filtro precisa de um campo.',
            'invalid-field' => 'Não é possível filtrar pelo campo ":field". Utilize um dos seguintes: :fields.',
            'invalid-operator' => 'O operador ":operator" não é suportado. Utilize um dos seguintes: :operators.',
            'invalid-value' => 'O filtro ":operator" em ":field" aceita um único valor, não uma lista.',
            'invalid-cursor' => 'O cursor de paginação é inválido ou pertence a outra pesquisa. Execute novamente a pesquisa sem cursor.',
            'invalid-date' => 'O filtro ":field" precisa de uma data como 2026-01-31 ou 2026-01-31 13:45:00; ":value" não é uma data.',
            'invalid-number' => 'O filtro ":field" precisa de um número; ":value" não é um número.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'O tipo do atributo [:code] não pode ser alterado depois de criado.',
            'category-invalid-parent' => 'A categoria [:code] não pode ser colocada sob si própria nem sob uma das suas subcategorias.',
            'super-attributes-invalid' => 'Os superatributos [:attributes] têm de ser atributos de seleção da família [:family] que não variem por idioma nem por canal.',
            'parent-not-found' => 'O produto principal [:sku] não existe ou não é um produto configurável.',
            'parent-unsupported' => 'O produto principal [:sku] usa uma estrutura de variantes de dois níveis. Crie as suas variantes através da API REST do UnoPim.',
            'parent-immutable' => 'O produto [:sku] já existe; o seu produto principal não pode ser alterado.',
            'variant-axis-invalid' => 'A variante [:sku] precisa de uma opção válida para cada atributo de variante do produto principal: :attributes.',
            'variant-exists' => 'O produto principal [:parent] já tem uma variante com o SKU [:sku] ou com os mesmos valores de atributos de variante.',
            'variant-values-taken' => 'A variante [:sku] não pode receber estes valores de atributos de variante: o produto principal [:parent] já tem uma variante com eles.',
            'product-create-incomplete' => 'O produto [:sku] não existe. type e attribute_family_id são obrigatórios para o criar.',
            'values-section-misplaced' => ':key não é um atributo. Envie-o no nível superior de values (values.:key), não dentro de values.:section.',
            'values-attribute-section' => 'O atributo [:code] pertence a :path.',
            'values-price-shape' => 'O atributo de preço [:code] aceita um objeto de código de moeda para montante, como {"USD": "19.99"}, não uma lista.',
            'values-currency-inactive' => 'A moeda [:currencies] não está ativa. Utilize uma destas: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Proibido: não tem permissão para executar [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Só é permitida uma única instrução SELECT, WITH, SHOW, EXPLAIN ou DESCRIBE.',
            'forbidden' => 'A consulta utiliza :keyword, o que não é permitido numa consulta só de leitura.',
        ],

        'run-skill' => [
            'not-found' => 'A skill [:name] não foi encontrada.',
        ],

        'command-runner' => [
            'blocked' => 'O comando [:command] não é permitido porque pode executar código arbitrário.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'O nome do plugin [:name] é inválido. Utilize apenas letras e algarismos, começando por uma letra maiúscula.',
        ],

        'test-generator' => [
            'invalid-package' => 'O nome do pacote [:name] é inválido. Utilize um nome como MyPlugin ou Webkul/MyPlugin.',
            'invalid-class' => 'O nome da classe [:name] é inválido. Utilize um nome como Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'O code_challenge_method tem de ser S256; o método plain não é suportado.',

        'registration' => [
            'body-too-large' => 'O pedido de registo é demasiado grande. O limite é de :max bytes.',
            'too-many-redirect-uris' => 'Demasiados URI de redirecionamento. São permitidos no máximo :max.',
            'redirect-uri-too-long' => 'Cada URI de redirecionamento pode ter no máximo :max caracteres.',
            'rate-limited' => 'Demasiadas tentativas de registo. Tente novamente mais tarde.',
        ],
    ],
];
