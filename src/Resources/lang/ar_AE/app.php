<?php

return [
    'authorize' => [
        'title' => 'تفويض :client',
        'heading' => 'تفويض :client',
        'lede' => 'يطلب هذا التطبيق الوصول إلى حسابك في UnoPim.',
        'signed-in-as' => 'تم تسجيل الدخول باسم',
        'redirects-to' => 'سيعيد توجيهك إلى',
        'permissions' => 'سيتمكن من',
        'approve' => 'تفويض',
        'approving' => 'جارٍ التفويض…',
        'deny' => 'إلغاء',
        'footnote' => 'يتبع الوصول دورك وأذوناتك الحالية في UnoPim.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'لم يتم العثور على تنفيذ المهمة.',
        ],
        'get-asset' => [
            'not-found' => 'لم يتم العثور على الأصل [:id].',
        ],
        'browse-asset-directories' => [
            'not-found' => 'لم يتم العثور على مجلد الأصول [:id].',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'العملة [:code] مستخدمة في قناة ولا يمكن تعطيلها.',
        ],
        'upsert-products' => [
            'invalid-values' => 'المنتج [:sku] (products.:index) غير صالح؛ لم يُكتب أي شيء من الدفعة.',
        ],

        'filters' => [
            'missing-field' => 'يحتاج كل عامل تصفية إلى حقل.',
            'invalid-field' => 'لا يمكن التصفية حسب الحقل ":field". استخدم أحد الحقول التالية: :fields.',
            'invalid-operator' => 'العامل ":operator" غير مدعوم. استخدم أحد العوامل التالية: :operators.',
            'invalid-value' => 'يقبل عامل التصفية ":operator" على ":field" قيمة واحدة وليس قائمة.',
            'invalid-cursor' => 'مؤشر الترقيم غير صالح أو يخص بحثًا آخر. أعد تشغيل البحث بدون مؤشر.',
            'invalid-date' => 'يحتاج عامل التصفية ":field" إلى تاريخ مثل 2026-01-31 أو 2026-01-31 13:45:00؛ والقيمة ":value" ليست تاريخًا.',
            'invalid-number' => 'يحتاج عامل التصفية ":field" إلى رقم؛ والقيمة ":value" ليست رقمًا.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'لا يمكن تغيير نوع السمة [:code] بعد إنشائها.',
            'category-invalid-parent' => 'لا يمكن وضع الفئة [:code] تحت نفسها أو تحت إحدى فئاتها الفرعية.',
            'super-attributes-invalid' => 'يجب أن تكون السمات الرئيسية [:attributes] سمات اختيار من العائلة [:family] لا تختلف حسب اللغة أو القناة.',
            'parent-not-found' => 'المنتج الأصلي [:sku] غير موجود أو ليس منتجًا قابلًا للتكوين.',
            'parent-unsupported' => 'يستخدم المنتج الأصلي [:sku] بنية متغيرات من مستويين. أنشئ متغيراته عبر واجهة UnoPim REST API.',
            'parent-immutable' => 'المنتج [:sku] موجود بالفعل؛ لا يمكن تغيير منتجه الأصلي.',
            'variant-axis-invalid' => 'يحتاج المتغير [:sku] إلى خيار صالح لكل سمة متغير في منتجه الأصلي: :attributes.',
            'variant-exists' => 'يحتوي المنتج الأصلي [:parent] بالفعل على متغير برمز SKU [:sku] أو بنفس قيم سمات المتغير.',
            'variant-values-taken' => 'لا يمكن للمتغير [:sku] أخذ قيم سمات المتغير هذه: المنتج الأصلي [:parent] لديه بالفعل متغير بها.',
            'product-create-incomplete' => 'المنتج [:sku] غير موجود. يلزم كل من type و attribute_family_id لإنشائه.',
            'values-section-misplaced' => ':key ليست سمة. أرسلها في المستوى الأعلى من values (values.:key)، وليس داخل values.:section.',
            'values-attribute-section' => 'السمة [:code] مكانها :path.',
            'values-price-shape' => 'سمة السعر [:code] تأخذ كائنًا من رمز العملة إلى المبلغ، مثل {"USD": "19.99"}، وليس قائمة.',
            'values-currency-inactive' => 'العملة [:currencies] غير نشطة. استخدم إحدى: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'محظور: ليس لديك إذن لتشغيل [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'يُسمح فقط بعبارة واحدة من نوع SELECT أو WITH أو SHOW أو EXPLAIN أو DESCRIBE.',
            'forbidden' => 'يستخدم الاستعلام :keyword، وهو غير مسموح به في استعلام للقراءة فقط.',
        ],

        'run-skill' => [
            'not-found' => 'لم يتم العثور على المهارة [:name].',
        ],

        'command-runner' => [
            'blocked' => 'الأمر [:command] غير مسموح به لأنه قد يشغّل تعليمات برمجية عشوائية.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'اسم الإضافة [:name] غير صالح. استخدم الأحرف والأرقام فقط، على أن يبدأ بحرف كبير.',
        ],

        'test-generator' => [
            'invalid-package' => 'اسم الحزمة [:name] غير صالح. استخدم اسمًا مثل MyPlugin أو Webkul/MyPlugin.',
            'invalid-class' => 'اسم الصنف [:name] غير صالح. استخدم اسمًا مثل Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'يجب أن تكون قيمة code_challenge_method هي S256؛ الطريقة plain غير مدعومة.',

        'registration' => [
            'body-too-large' => 'طلب التسجيل كبير جدًا. الحد الأقصى هو :max بايت.',
            'too-many-redirect-uris' => 'عدد عناوين إعادة التوجيه كبير جدًا. الحد الأقصى المسموح به هو :max.',
            'redirect-uri-too-long' => 'يجب ألا يتجاوز طول كل عنوان إعادة توجيه :max حرفًا.',
            'rate-limited' => 'محاولات تسجيل كثيرة جدًا. يرجى المحاولة مرة أخرى لاحقًا.',
        ],
    ],
];
