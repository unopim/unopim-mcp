<?php

return [
    'authorize' => [
        'title' => '承認 :client',
        'heading' => '承認 :client',
        'lede' => 'このアプリケーションがあなたのUnoPimアカウントへのアクセスを求めています。',
        'signed-in-as' => 'ログイン中のアカウント',
        'permissions' => '次の操作が可能になります',
        'approve' => '承認',
        'approving' => '承認しています…',
        'deny' => 'キャンセル',
        'footnote' => 'アクセス権は、現在のUnoPimのロールと権限に従います。',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'ジョブの実行が見つかりません。',
        ],
        'upsert-currencies' => [
            'can-not-disable' => '通貨 [:code] はチャネルで使用されているため、無効にできません。',
        ],
        'upsert-products' => [
            'invalid-values' => '商品 [:sku] (products.:index) が無効です。バッチ内のデータは何も書き込まれていません。',
        ],

        'filters' => [
            'missing-field' => '各フィルターにはフィールドが必要です。',
            'invalid-field' => 'フィールド ":field" ではフィルターできません。次のいずれかを使用してください: :fields。',
            'invalid-operator' => '演算子 ":operator" はサポートされていません。次のいずれかを使用してください: :operators。',
            'invalid-value' => '":field" に対する ":operator" フィルターは、リストではなく単一の値を受け付けます。',
            'invalid-cursor' => 'ページネーションカーソルが無効か、別の検索のものです。カーソルなしで検索をやり直してください。',
            'invalid-date' => 'フィルター ":field" には 2026-01-31 や 2026-01-31 13:45:00 のような日付が必要です。":value" は日付ではありません。',
            'invalid-number' => 'フィルター ":field" には数値が必要です。":value" は数値ではありません。',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => '属性 [:code] のタイプは作成後に変更できません。',
            'category-invalid-parent' => 'カテゴリー [:code] を自分自身または自分のサブカテゴリーの下に配置することはできません。',
            'super-attributes-invalid' => 'スーパー属性 [:attributes] は、ファミリー [:family] の選択属性で、ロケールやチャネルによって変わらないものである必要があります。',
            'parent-not-found' => '親商品 [:sku] が存在しないか、設定可能商品ではありません。',
            'parent-unsupported' => '親商品 [:sku] は 2 階層のバリアント構造を使用しています。バリアントは UnoPim REST API で作成してください。',
            'parent-immutable' => '商品 [:sku] は既に存在します。親商品は変更できません。',
            'variant-axis-invalid' => 'バリアント [:sku] には、親商品の各バリアント属性に対する有効なオプションが必要です: :attributes。',
            'variant-exists' => '親商品 [:parent] には、SKU [:sku] または同じバリアント属性値を持つバリアントが既に存在します。',
            'variant-values-taken' => 'バリアント [:sku] にこのバリアント属性値は設定できません。親商品 [:parent] には同じ値のバリアントが既に存在します。',
            'product-create-incomplete' => '商品 [:sku] は存在しません。作成するには type と attribute_family_id の両方が必要です。',
            'values-section-misplaced' => ':key は属性ではありません。values.:section の中ではなく、values の最上位 (values.:key) に指定してください。',
            'values-attribute-section' => '属性 [:code] は :path に指定します。',
            'values-price-shape' => '価格属性 [:code] には、{"USD": "19.99"} のような通貨コードと金額のオブジェクトを指定します。リストは使えません。',
            'values-currency-inactive' => '通貨 [:currencies] は有効ではありません。次のいずれかを使用してください: :active。',
        ],
    ],

    'call-tool' => [
        'forbidden' => '禁止: [:tool] を実行する権限がありません。',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'SELECT、WITH、SHOW、EXPLAIN、DESCRIBE のいずれか 1 つのステートメントのみ使用できます。',
            'forbidden' => 'クエリで :keyword が使用されていますが、読み取り専用クエリでは使用できません。',
        ],

        'run-skill' => [
            'not-found' => 'スキル [:name] が見つかりません。',
        ],

        'command-runner' => [
            'blocked' => '[:command] コマンドは任意のコードを実行できるため許可されていません。',
        ],

        'plugin-generator' => [
            'invalid-name' => 'プラグイン名 [:name] は無効です。英字と数字のみを使用し、大文字で始めてください。',
        ],

        'test-generator' => [
            'invalid-package' => 'パッケージ名 [:name] は無効です。MyPlugin や Webkul/MyPlugin のような名前を使用してください。',
            'invalid-class' => 'クラス名 [:name] は無効です。Services/MyService のような名前を使用してください。',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method は S256 である必要があります。plain メソッドはサポートされていません。',

        'registration' => [
            'body-too-large' => '登録リクエストが大きすぎます。上限は :max バイトです。',
            'too-many-redirect-uris' => 'リダイレクト URI が多すぎます。最大 :max 個まで指定できます。',
            'redirect-uri-too-long' => '各リダイレクト URI は :max 文字以内にしてください。',
            'rate-limited' => '登録の試行回数が多すぎます。しばらくしてから再度お試しください。',
        ],
    ],
];
