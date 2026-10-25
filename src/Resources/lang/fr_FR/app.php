<?php

return [
    'authorize' => [
        'title' => 'Autoriser :client',
        'heading' => 'Autoriser :client',
        'lede' => 'Cette application demande l\'accès à votre compte UnoPim.',
        'signed-in-as' => 'Connecté en tant que',
        'redirects-to' => 'Vous redirigera vers',
        'permissions' => 'Elle pourra',
        'approve' => 'Autoriser',
        'approving' => 'Autorisation en cours…',
        'deny' => 'Annuler',
        'footnote' => 'L\'accès respecte votre rôle et vos autorisations UnoPim actuels.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'L\'exécution de la tâche est introuvable.',
        ],
        'get-asset' => [
            'not-found' => 'La ressource [:id] est introuvable.',
        ],
        'browse-asset-directories' => [
            'not-found' => 'Le répertoire de ressources [:id] est introuvable.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'La devise [:code] est utilisée par un canal et ne peut pas être désactivée.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Le produit [:sku] (products.:index) n\'est pas valide ; rien du lot n\'a été enregistré.',
        ],

        'filters' => [
            'missing-field' => 'Chaque filtre doit comporter un champ.',
            'invalid-field' => 'Le champ ":field" ne peut pas être filtré. Utilisez l’un des champs suivants : :fields.',
            'invalid-operator' => 'L’opérateur ":operator" n’est pas pris en charge. Utilisez l’un des opérateurs suivants : :operators.',
            'invalid-value' => 'Le filtre ":operator" sur ":field" accepte une seule valeur, pas une liste.',
            'invalid-cursor' => 'Le curseur de pagination est invalide ou appartient à une autre recherche. Relancez la recherche sans curseur.',
            'invalid-date' => 'Le filtre ":field" attend une date comme 2026-01-31 ou 2026-01-31 13:45:00 ; ":value" n\'est pas une date.',
            'invalid-number' => 'Le filtre ":field" attend un nombre ; ":value" n\'est pas un nombre.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Le type de l’attribut [:code] ne peut plus être modifié après sa création.',
            'category-invalid-parent' => 'La catégorie [:code] ne peut pas être placée sous elle-même ni sous l’une de ses propres sous-catégories.',
            'super-attributes-invalid' => 'Les super-attributs [:attributes] doivent être des attributs de sélection de la famille [:family] qui ne varient ni selon la langue ni selon le canal.',
            'parent-not-found' => 'Le produit parent [:sku] n’existe pas ou n’est pas un produit configurable.',
            'parent-unsupported' => 'Le produit parent [:sku] utilise une structure de variantes à deux niveaux. Créez ses variantes via l’API REST d’UnoPim.',
            'parent-immutable' => 'Le produit [:sku] existe déjà ; son parent ne peut pas être modifié.',
            'variant-axis-invalid' => 'La variante [:sku] doit avoir une option valide pour chaque attribut de variante de son parent : :attributes.',
            'variant-exists' => 'Le produit parent [:parent] possède déjà une variante avec le SKU [:sku] ou avec les mêmes valeurs d’attributs de variante.',
            'variant-values-taken' => 'La variante [:sku] ne peut pas prendre ces valeurs d\'attributs de variante : le produit parent [:parent] a déjà une variante avec ces valeurs.',
            'product-create-incomplete' => 'Le produit [:sku] n\'existe pas. type et attribute_family_id sont tous deux requis pour le créer.',
            'values-section-misplaced' => ':key n\'est pas un attribut. Envoyez-le au premier niveau de values (values.:key), et non dans values.:section.',
            'values-attribute-section' => 'L\'attribut [:code] se place dans :path.',
            'values-price-shape' => 'L\'attribut de prix [:code] attend un objet code de devise vers montant, par exemple {"USD": "19.99"}, et non une liste.',
            'values-currency-inactive' => 'La devise [:currencies] n\'est pas active. Utilisez l\'une de : :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Interdit : vous n\'avez pas l\'autorisation d\'exécuter [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Seule une instruction SELECT, WITH, SHOW, EXPLAIN ou DESCRIBE unique est autorisée.',
            'forbidden' => 'La requête utilise :keyword, ce qui n\'est pas autorisé dans une requête en lecture seule.',
        ],

        'run-skill' => [
            'not-found' => 'La compétence [:name] est introuvable.',
        ],

        'command-runner' => [
            'blocked' => 'La commande [:command] n\'est pas autorisée car elle peut exécuter du code arbitraire.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Le nom du plugin [:name] n\'est pas valide. Utilisez uniquement des lettres et des chiffres, en commençant par une majuscule.',
        ],

        'test-generator' => [
            'invalid-package' => 'Le nom du paquet [:name] n\'est pas valide. Utilisez un nom comme MyPlugin ou Webkul/MyPlugin.',
            'invalid-class' => 'Le nom de la classe [:name] n\'est pas valide. Utilisez un nom comme Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Le code_challenge_method doit être S256 ; la méthode plain n\'est pas prise en charge.',

        'registration' => [
            'body-too-large' => 'La demande d\'enregistrement est trop volumineuse. La limite est de :max octets.',
            'too-many-redirect-uris' => 'Trop d\'URI de redirection. :max au maximum sont autorisées.',
            'redirect-uri-too-long' => 'Chaque URI de redirection ne doit pas dépasser :max caractères.',
            'rate-limited' => 'Trop de tentatives d\'enregistrement. Veuillez réessayer plus tard.',
        ],
    ],
];
