<?php

return [
    'authorize' => [
        'title' => 'Authorise :client',
        'heading' => 'Authorise :client',
        'lede' => 'This application is requesting access to your UnoPim account.',
        'signed-in-as' => 'Signed in as',
        'redirects-to' => 'Redirects to',
        'permissions' => 'It will be able to',
        'approve' => 'Authorise',
        'approving' => 'Authorising…',
        'deny' => 'Cancel',
        'footnote' => 'Access follows your existing UnoPim role and permissions.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Job execution not found.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Currency [:code] is used by a channel and cannot be disabled.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Product [:sku] (products.:index) is not valid; nothing in the batch was written.',
        ],

        'filters' => [
            'missing-field' => 'Every filter needs a field.',
            'invalid-field' => 'The field ":field" cannot be filtered on. Use one of: :fields.',
            'invalid-operator' => 'The operator ":operator" is not supported. Use one of: :operators.',
            'invalid-value' => 'The ":operator" filter on ":field" takes a single value, not a list.',
            'invalid-cursor' => 'The pagination cursor is invalid or belongs to a different search. Run the search again without a cursor.',
            'invalid-date' => 'The ":field" filter needs a date such as 2026-01-31 or 2026-01-31 13:45:00; ":value" is not a date.',
            'invalid-number' => 'The ":field" filter needs a number; ":value" is not a number.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'The type of attribute [:code] cannot be changed after it is created.',
            'category-invalid-parent' => 'Category [:code] cannot be placed under itself or one of its own subcategories.',
            'super-attributes-invalid' => 'Super attributes [:attributes] must be select attributes of family [:family] that do not vary by locale or channel.',
            'parent-not-found' => 'Parent product [:sku] does not exist or is not a configurable product.',
            'parent-unsupported' => 'Parent product [:sku] uses a two-level variant structure. Create its variants through the UnoPim REST API.',
            'parent-immutable' => 'Product [:sku] already exists; its parent cannot be changed.',
            'variant-axis-invalid' => 'Variant [:sku] needs a valid option for each variant attribute of its parent: :attributes.',
            'variant-exists' => 'Parent product [:parent] already has a variant with SKU [:sku] or with the same variant attribute values.',
            'variant-values-taken' => 'Variant [:sku] cannot take these variant attribute values: parent product [:parent] already has a variant with them.',
            'product-create-incomplete' => 'Product [:sku] does not exist. Both type and attribute_family_id are required to create it.',
            'values-section-misplaced' => ':key is not an attribute. Send it at the top level of values (values.:key), not inside values.:section.',
            'values-attribute-section' => 'Attribute [:code] goes in :path.',
            'values-price-shape' => 'Price attribute [:code] takes an object of currency code to amount, such as {"USD": "19.99"}, not a list.',
            'values-currency-inactive' => 'Currency [:currencies] is not active. Use one of: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Forbidden: you do not have permission to run [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Only a single SELECT, WITH, SHOW, EXPLAIN or DESCRIBE statement is allowed.',
            'forbidden' => 'The query uses :keyword, which is not allowed in a read-only query.',
        ],

        'run-skill' => [
            'not-found' => 'Skill [:name] was not found.',
        ],

        'command-runner' => [
            'blocked' => 'The [:command] command is not allowed because it can run arbitrary code.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'The plugin name [:name] is invalid. Use only letters and digits, starting with an uppercase letter.',
        ],

        'test-generator' => [
            'invalid-package' => 'The package name [:name] is invalid. Use a name such as MyPlugin or Webkul/MyPlugin.',
            'invalid-class' => 'The class name [:name] is invalid. Use a name such as Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'The code_challenge_method must be S256; the plain method is not supported.',

        'registration' => [
            'body-too-large' => 'The registration request is too large. The limit is :max bytes.',
            'too-many-redirect-uris' => 'Too many redirect URIs. At most :max are allowed.',
            'redirect-uri-too-long' => 'Each redirect URI must be at most :max characters long.',
            'rate-limited' => 'Too many registration attempts. Please try again later.',
        ],
    ],
];
