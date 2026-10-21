<?php

return [
    'authorize' => [
        'title' => 'Pahintulutan :client',
        'heading' => 'Pahintulutan :client',
        'lede' => 'Humihiling ang application na ito ng access sa iyong UnoPim account.',
        'signed-in-as' => 'Naka-sign in bilang',
        'redirects-to' => 'Ire-redirect ka sa',
        'permissions' => 'Magagawa nitong',
        'approve' => 'Pahintulutan',
        'approving' => 'Pinahihintulutan…',
        'deny' => 'Kanselahin',
        'footnote' => 'Sumusunod ang access sa iyong kasalukuyang UnoPim role at mga pahintulot.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Hindi nahanap ang pagpapatakbo ng job.',
        ],
        'get-asset' => [
            'not-found' => 'Hindi nahanap ang asset [:id].',
        ],
        'browse-asset-directories' => [
            'not-found' => 'Hindi nahanap ang direktoryo ng asset [:id].',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Ginagamit ng isang channel ang currency na [:code] kaya hindi ito maaaring i-disable.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Hindi wasto ang produktong [:sku] (products.:index); walang naisulat mula sa batch.',
        ],

        'filters' => [
            'missing-field' => 'Kailangan ng bawat filter ng field.',
            'invalid-field' => 'Hindi maaaring i-filter ang field na ":field". Gamitin ang isa sa: :fields.',
            'invalid-operator' => 'Hindi suportado ang operator na ":operator". Gamitin ang isa sa: :operators.',
            'invalid-value' => 'Iisang value lang ang tinatanggap ng ":operator" na filter sa ":field", hindi listahan.',
            'invalid-cursor' => 'Hindi valid ang pagination cursor o para ito sa ibang paghahanap. Patakbuhin muli ang paghahanap nang walang cursor.',
            'invalid-date' => 'Kailangan ng filter na ":field" ng petsa gaya ng 2026-01-31 o 2026-01-31 13:45:00; hindi petsa ang ":value".',
            'invalid-number' => 'Kailangan ng filter na ":field" ng numero; hindi numero ang ":value".',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Hindi na mababago ang uri ng attribute na [:code] matapos itong malikha.',
            'category-invalid-parent' => 'Hindi maaaring ilagay ang kategoryang [:code] sa ilalim ng sarili nito o ng isa sa sarili nitong mga subcategory.',
            'super-attributes-invalid' => 'Ang mga super attribute na [:attributes] ay dapat na mga select attribute ng family na [:family] na hindi nag-iiba ayon sa locale o channel.',
            'parent-not-found' => 'Wala ang parent product na [:sku] o hindi ito configurable na produkto.',
            'parent-unsupported' => 'Gumagamit ang parent product na [:sku] ng dalawang-antas na variant structure. Likhain ang mga variant nito sa pamamagitan ng UnoPim REST API.',
            'parent-immutable' => 'Umiiral na ang produktong [:sku]; hindi na mababago ang parent nito.',
            'variant-axis-invalid' => 'Kailangan ng variant na [:sku] ng valid na opsyon para sa bawat variant attribute ng parent nito: :attributes.',
            'variant-exists' => 'May variant na ang parent product na [:parent] na may SKU na [:sku] o may parehong mga value ng variant attribute.',
            'variant-values-taken' => 'Hindi maaaring kunin ng variant na [:sku] ang mga value ng variant attribute na ito: may variant na nito ang parent na produktong [:parent].',
            'product-create-incomplete' => 'Hindi umiiral ang produktong [:sku]. Kailangan ang type at attribute_family_id para malikha ito.',
            'values-section-misplaced' => 'Hindi attribute ang :key. Ipadala ito sa pinakamataas na antas ng values (values.:key), hindi sa loob ng values.:section.',
            'values-attribute-section' => 'Ang attribute na [:code] ay nasa :path.',
            'values-price-shape' => 'Tumatanggap ang price attribute na [:code] ng object ng currency code papunta sa halaga, gaya ng {"USD": "19.99"}, hindi listahan.',
            'values-currency-inactive' => 'Hindi aktibo ang currency na [:currencies]. Gamitin ang isa sa: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Ipinagbabawal: wala kang pahintulot na patakbuhin ang [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Isang SELECT, WITH, SHOW, EXPLAIN, o DESCRIBE na statement lamang ang pinapayagan.',
            'forbidden' => 'Gumagamit ang query ng :keyword, na hindi pinapayagan sa read-only na query.',
        ],

        'run-skill' => [
            'not-found' => 'Hindi nahanap ang skill na [:name].',
        ],

        'command-runner' => [
            'blocked' => 'Hindi pinapayagan ang command na [:command] dahil maaari itong magpatakbo ng arbitraryong code.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Hindi wasto ang pangalan ng plugin na [:name]. Gumamit lamang ng mga titik at numero, na nagsisimula sa malaking titik.',
        ],

        'test-generator' => [
            'invalid-package' => 'Hindi wasto ang pangalan ng package na [:name]. Gumamit ng pangalang tulad ng MyPlugin o Webkul/MyPlugin.',
            'invalid-class' => 'Hindi wasto ang pangalan ng class na [:name]. Gumamit ng pangalang tulad ng Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Ang code_challenge_method ay dapat S256; hindi suportado ang plain na paraan.',

        'registration' => [
            'body-too-large' => 'Masyadong malaki ang kahilingan sa pagpaparehistro. Ang limitasyon ay :max byte.',
            'too-many-redirect-uris' => 'Masyadong maraming redirect URI. Hanggang :max lamang ang pinapayagan.',
            'redirect-uri-too-long' => 'Ang bawat redirect URI ay hindi dapat lumampas sa :max na karakter.',
            'rate-limited' => 'Masyadong maraming pagtatangkang magparehistro. Pakisubukang muli mamaya.',
        ],
    ],
];
