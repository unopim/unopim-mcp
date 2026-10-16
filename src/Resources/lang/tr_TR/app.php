<?php

return [
    'authorize' => [
        'title' => 'Yetkilendir :client',
        'heading' => 'Yetkilendir :client',
        'lede' => 'Bu uygulama UnoPim hesabınıza erişim istiyor.',
        'signed-in-as' => 'Oturum açan kullanıcı',
        'permissions' => 'Şunları yapabilecek',
        'approve' => 'Yetkilendir',
        'approving' => 'Yetkilendiriliyor…',
        'deny' => 'İptal',
        'footnote' => 'Erişim, mevcut UnoPim rolünüze ve izinlerinize göre belirlenir.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'İş yürütmesi bulunamadı.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => '[:code] para birimi bir kanal tarafından kullanıldığı için devre dışı bırakılamaz.',
        ],
        'upsert-products' => [
            'invalid-values' => '[:sku] ürünü (products.:index) geçersiz; toplu işlemden hiçbir şey kaydedilmedi.',
        ],

        'filters' => [
            'missing-field' => 'Her filtre bir alan gerektirir.',
            'invalid-field' => '":field" alanına göre filtreleme yapılamaz. Şunlardan birini kullanın: :fields.',
            'invalid-operator' => '":operator" operatörü desteklenmiyor. Şunlardan birini kullanın: :operators.',
            'invalid-value' => '":field" üzerindeki ":operator" filtresi liste değil, tek bir değer alır.',
            'invalid-cursor' => 'Sayfalama imleci geçersiz veya başka bir aramaya ait. Aramayı imleç olmadan yeniden çalıştırın.',
            'invalid-date' => '":field" filtresi 2026-01-31 veya 2026-01-31 13:45:00 gibi bir tarih gerektirir; ":value" bir tarih değil.',
            'invalid-number' => '":field" filtresi bir sayı gerektirir; ":value" bir sayı değil.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => '[:code] özelliğinin türü oluşturulduktan sonra değiştirilemez.',
            'category-invalid-parent' => '[:code] kategorisi kendisinin veya kendi alt kategorilerinden birinin altına yerleştirilemez.',
            'super-attributes-invalid' => '[:attributes] süper özellikleri, [:family] ailesinin yerel ayara veya kanala göre değişmeyen seçim özellikleri olmalıdır.',
            'parent-not-found' => '[:sku] üst ürünü mevcut değil veya yapılandırılabilir bir ürün değil.',
            'parent-unsupported' => '[:sku] üst ürünü iki seviyeli bir varyant yapısı kullanıyor. Varyantlarını UnoPim REST API üzerinden oluşturun.',
            'parent-immutable' => '[:sku] ürünü zaten mevcut; üst ürünü değiştirilemez.',
            'variant-axis-invalid' => '[:sku] varyantı, üst ürününün her varyant özelliği için geçerli bir seçenek gerektirir: :attributes.',
            'variant-exists' => '[:parent] üst ürününde [:sku] SKU\'suna veya aynı varyant özellik değerlerine sahip bir varyant zaten var.',
            'variant-values-taken' => '[:sku] varyantı bu varyant nitelik değerlerini alamaz: üst ürün [:parent] bu değerlere sahip bir varyanta zaten sahip.',
            'product-create-incomplete' => '[:sku] ürünü mevcut değil. Oluşturmak için hem type hem de attribute_family_id gereklidir.',
            'values-section-misplaced' => ':key bir nitelik değil. Onu values.:section içinde değil, values üst düzeyinde (values.:key) gönderin.',
            'values-attribute-section' => '[:code] niteliği :path içine yazılır.',
            'values-price-shape' => '[:code] fiyat niteliği, {"USD": "19.99"} gibi para birimi kodundan tutara bir nesne alır, liste değil.',
            'values-currency-inactive' => '[:currencies] para birimi aktif değil. Şunlardan birini kullanın: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Yasak: [:tool] aracını çalıştırma izniniz yok.',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Yalnızca tek bir SELECT, WITH, SHOW, EXPLAIN veya DESCRIBE ifadesine izin verilir.',
            'forbidden' => 'Sorgu :keyword kullanıyor; bu, salt okunur bir sorguda izin verilmez.',
        ],

        'run-skill' => [
            'not-found' => '[:name] becerisi bulunamadı.',
        ],

        'command-runner' => [
            'blocked' => '[:command] komutuna izin verilmiyor çünkü rastgele kod çalıştırabilir.',
        ],

        'plugin-generator' => [
            'invalid-name' => '[:name] eklenti adı geçersiz. Yalnızca harf ve rakam kullanın ve büyük harfle başlayın.',
        ],

        'test-generator' => [
            'invalid-package' => '[:name] paket adı geçersiz. MyPlugin veya Webkul/MyPlugin gibi bir ad kullanın.',
            'invalid-class' => '[:name] sınıf adı geçersiz. Services/MyService gibi bir ad kullanın.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method değeri S256 olmalıdır; plain yöntemi desteklenmez.',

        'registration' => [
            'body-too-large' => 'Kayıt isteği çok büyük. Sınır :max bayttır.',
            'too-many-redirect-uris' => 'Çok fazla yönlendirme URI\'si var. En fazla :max adede izin verilir.',
            'redirect-uri-too-long' => 'Her yönlendirme URI\'si en fazla :max karakter uzunluğunda olabilir.',
            'rate-limited' => 'Çok fazla kayıt denemesi. Lütfen daha sonra tekrar deneyin.',
        ],
    ],
];
