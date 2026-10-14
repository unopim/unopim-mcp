<?php

return [
    'authorize' => [
        'title' => '授權 :client',
        'heading' => '授權 :client',
        'lede' => '此應用程式正在要求存取您的 UnoPim 帳號。',
        'signed-in-as' => '目前登入帳號',
        'permissions' => '它將能夠',
        'approve' => '授權',
        'approving' => '正在授權…',
        'deny' => '取消',
        'footnote' => '存取權限會依照您現有的 UnoPim 角色與權限。',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => '貨幣 [:code] 正被通路使用，無法停用。',
        ],
        'upsert-products' => [
            'invalid-values' => '商品 [:sku] (products.:index) 無效；批次中的內容均未寫入。',
        ],

        'filters' => [
            'missing-field' => '每個篩選條件都需要一個欄位。',
            'invalid-field' => '無法依欄位 ":field" 篩選。請使用下列其中之一：:fields。',
            'invalid-operator' => '不支援運算子 ":operator"。請使用下列其中之一：:operators。',
            'invalid-value' => '":field" 上的 ":operator" 篩選只接受單一值，不接受清單。',
            'invalid-cursor' => '分頁游標無效或屬於其他搜尋。請不帶游標重新執行搜尋。',
            'invalid-date' => '篩選條件 ":field" 需要日期，例如 2026-01-31 或 2026-01-31 13:45:00；":value" 不是日期。',
            'invalid-number' => '篩選條件 ":field" 需要數字；":value" 不是數字。',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => '屬性 [:code] 建立後無法變更其類型。',
            'category-invalid-parent' => '分類 [:code] 不能放在其自身或其任何子分類之下。',
            'super-attributes-invalid' => '超級屬性 [:attributes] 必須是屬性族 [:family] 中不隨語系或通路變化的選擇型屬性。',
            'parent-not-found' => '父產品 [:sku] 不存在或不是可設定產品。',
            'parent-unsupported' => '父產品 [:sku] 使用兩層變體結構。請透過 UnoPim REST API 建立其變體。',
            'parent-immutable' => '產品 [:sku] 已存在；無法變更其父產品。',
            'variant-axis-invalid' => '變體 [:sku] 需要為其父產品的每個變體屬性提供有效選項：:attributes。',
            'variant-exists' => '父產品 [:parent] 已有 SKU 為 [:sku] 或變體屬性值相同的變體。',
            'variant-values-taken' => '變體 [:sku] 不能使用這些變體屬性值：父商品 [:parent] 已有具有這些值的變體。',
            'product-create-incomplete' => '商品 [:sku] 不存在。建立它需要同時提供 type 和 attribute_family_id。',
            'values-section-misplaced' => ':key 不是屬性。請將其放在 values 的最上層 (values.:key)，而不是 values.:section 中。',
            'values-attribute-section' => '屬性 [:code] 應放在 :path 中。',
            'values-price-shape' => '價格屬性 [:code] 接受貨幣代碼對應金額的物件，例如 {"USD": "19.99"}，而不是清單。',
            'values-currency-inactive' => '貨幣 [:currencies] 未啟用。請使用以下其中之一：:active。',
        ],
    ],

    'call-tool' => [
        'forbidden' => '禁止：您沒有執行 [:tool] 的權限。',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => '只允許單一 SELECT、WITH、SHOW、EXPLAIN 或 DESCRIBE 陳述式。',
            'forbidden' => '查詢使用了 :keyword，唯讀查詢中不允許使用。',
        ],

        'run-skill' => [
            'not-found' => '找不到技能 [:name]。',
        ],

        'command-runner' => [
            'blocked' => '不允許使用 [:command] 指令，因為它可以執行任意程式碼。',
        ],

        'plugin-generator' => [
            'invalid-name' => '外掛名稱 [:name] 無效。只能使用字母和數字，並以大寫字母開頭。',
        ],

        'test-generator' => [
            'invalid-package' => '套件名稱 [:name] 無效。請使用類似 MyPlugin 或 Webkul/MyPlugin 的名稱。',
            'invalid-class' => '類別名稱 [:name] 無效。請使用類似 Services/MyService 的名稱。',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method 必須為 S256；不支援 plain 方法。',

        'registration' => [
            'body-too-large' => '註冊請求過大。上限為 :max 位元組。',
            'too-many-redirect-uris' => '重新導向 URI 過多。最多允許 :max 個。',
            'redirect-uri-too-long' => '每個重新導向 URI 的長度不得超過 :max 個字元。',
            'rate-limited' => '註冊嘗試次數過多，請稍後再試。',
        ],
    ],
];
