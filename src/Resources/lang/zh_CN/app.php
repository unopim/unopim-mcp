<?php

return [
    'authorize' => [
        'title' => '授权 :client',
        'heading' => '授权 :client',
        'lede' => '此应用程序正在请求访问您的 UnoPim 账户。',
        'signed-in-as' => '当前登录账户',
        'redirects-to' => '重定向至',
        'permissions' => '它将能够',
        'approve' => '授权',
        'approving' => '正在授权…',
        'deny' => '取消',
        'footnote' => '访问权限遵循您现有的 UnoPim 角色和权限。',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => '未找到作业执行记录。',
        ],
        'get-asset' => [
            'not-found' => '未找到资产 [:id]。',
        ],
        'browse-asset-directories' => [
            'not-found' => '未找到资产目录 [:id]。',
        ],
        'upsert-currencies' => [
            'can-not-disable' => '货币 [:code] 正被渠道使用，无法停用。',
        ],
        'upsert-products' => [
            'invalid-values' => '商品 [:sku] (products.:index) 无效；批次中的内容均未写入。',
        ],

        'filters' => [
            'missing-field' => '每个筛选条件都需要一个字段。',
            'invalid-field' => '无法按字段 ":field" 筛选。请使用以下之一：:fields。',
            'invalid-operator' => '不支持运算符 ":operator"。请使用以下之一：:operators。',
            'invalid-value' => '":field" 上的 ":operator" 筛选只接受单个值，不接受列表。',
            'invalid-cursor' => '分页游标无效或属于其他搜索。请不带游标重新执行搜索。',
            'invalid-date' => '筛选条件 ":field" 需要日期，例如 2026-01-31 或 2026-01-31 13:45:00；":value" 不是日期。',
            'invalid-number' => '筛选条件 ":field" 需要数字；":value" 不是数字。',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => '属性 [:code] 创建后不能更改其类型。',
            'category-invalid-parent' => '分类 [:code] 不能放在其自身或其任何子分类之下。',
            'super-attributes-invalid' => '超级属性 [:attributes] 必须是属性族 [:family] 中不随语言环境或渠道变化的选择型属性。',
            'parent-not-found' => '父产品 [:sku] 不存在或不是可配置产品。',
            'parent-unsupported' => '父产品 [:sku] 使用两级变体结构。请通过 UnoPim REST API 创建其变体。',
            'parent-immutable' => '产品 [:sku] 已存在；不能更改其父产品。',
            'variant-axis-invalid' => '变体 [:sku] 需要为其父产品的每个变体属性提供有效选项：:attributes。',
            'variant-exists' => '父产品 [:parent] 已有 SKU 为 [:sku] 或变体属性值相同的变体。',
            'variant-values-taken' => '变体 [:sku] 不能使用这些变体属性值：父商品 [:parent] 已有具有这些值的变体。',
            'product-create-incomplete' => '商品 [:sku] 不存在。创建它需要同时提供 type 和 attribute_family_id。',
            'values-section-misplaced' => ':key 不是属性。请将其放在 values 的顶层 (values.:key)，而不是 values.:section 中。',
            'values-attribute-section' => '属性 [:code] 应放在 :path 中。',
            'values-price-shape' => '价格属性 [:code] 接受货币代码到金额的对象，例如 {"USD": "19.99"}，而不是列表。',
            'values-currency-inactive' => '货币 [:currencies] 未启用。请使用以下之一：:active。',
        ],
    ],

    'call-tool' => [
        'forbidden' => '禁止：您无权运行 [:tool]。',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => '只允许单条 SELECT、WITH、SHOW、EXPLAIN 或 DESCRIBE 语句。',
            'forbidden' => '查询使用了 :keyword，只读查询中不允许使用。',
        ],

        'run-skill' => [
            'not-found' => '未找到技能 [:name]。',
        ],

        'command-runner' => [
            'blocked' => '不允许使用 [:command] 命令，因为它可以运行任意代码。',
        ],

        'plugin-generator' => [
            'invalid-name' => '插件名称 [:name] 无效。只能使用字母和数字，并以大写字母开头。',
        ],

        'test-generator' => [
            'invalid-package' => '包名称 [:name] 无效。请使用类似 MyPlugin 或 Webkul/MyPlugin 的名称。',
            'invalid-class' => '类名 [:name] 无效。请使用类似 Services/MyService 的名称。',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method 必须为 S256；不支持 plain 方法。',

        'registration' => [
            'body-too-large' => '注册请求过大。上限为 :max 字节。',
            'too-many-redirect-uris' => '重定向 URI 过多。最多允许 :max 个。',
            'redirect-uri-too-long' => '每个重定向 URI 的长度不得超过 :max 个字符。',
            'rate-limited' => '注册尝试次数过多，请稍后再试。',
        ],
    ],
];
