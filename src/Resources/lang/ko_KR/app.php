<?php

return [
    'authorize' => [
        'title' => '승인 :client',
        'heading' => '승인 :client',
        'lede' => '이 애플리케이션이 UnoPim 계정에 대한 액세스를 요청하고 있습니다.',
        'signed-in-as' => '로그인한 계정',
        'redirects-to' => '리디렉션 대상',
        'permissions' => '다음 작업을 수행할 수 있습니다',
        'approve' => '승인',
        'approving' => '승인 중…',
        'deny' => '취소',
        'footnote' => '액세스는 현재 UnoPim 역할과 권한을 따릅니다.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => '작업 실행을 찾을 수 없습니다.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => '통화 [:code]은(는) 채널에서 사용 중이므로 비활성화할 수 없습니다.',
        ],
        'upsert-products' => [
            'invalid-values' => '상품 [:sku] (products.:index)이(가) 유효하지 않습니다. 배치의 어떤 항목도 저장되지 않았습니다.',
        ],

        'filters' => [
            'missing-field' => '모든 필터에는 필드가 필요합니다.',
            'invalid-field' => '":field" 필드로는 필터링할 수 없습니다. 다음 중 하나를 사용하세요: :fields.',
            'invalid-operator' => '":operator" 연산자는 지원되지 않습니다. 다음 중 하나를 사용하세요: :operators.',
            'invalid-value' => '":field"에 대한 ":operator" 필터는 목록이 아닌 단일 값을 받습니다.',
            'invalid-cursor' => '페이지네이션 커서가 유효하지 않거나 다른 검색의 커서입니다. 커서 없이 검색을 다시 실행하세요.',
            'invalid-date' => '":field" 필터에는 2026-01-31 또는 2026-01-31 13:45:00 같은 날짜가 필요합니다. ":value"은(는) 날짜가 아닙니다.',
            'invalid-number' => '":field" 필터에는 숫자가 필요합니다. ":value"은(는) 숫자가 아닙니다.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => '속성 [:code]의 유형은 생성 후 변경할 수 없습니다.',
            'category-invalid-parent' => '카테고리 [:code]은(는) 자기 자신이나 자신의 하위 카테고리 아래에 둘 수 없습니다.',
            'super-attributes-invalid' => '슈퍼 속성 [:attributes]은(는) 로케일이나 채널에 따라 달라지지 않는 패밀리 [:family]의 선택 속성이어야 합니다.',
            'parent-not-found' => '상위 상품 [:sku]이(가) 존재하지 않거나 구성 가능한 상품이 아닙니다.',
            'parent-unsupported' => '상위 상품 [:sku]은(는) 2단계 변형 구조를 사용합니다. 변형은 UnoPim REST API로 생성하세요.',
            'parent-immutable' => '상품 [:sku]이(가) 이미 존재합니다. 상위 상품은 변경할 수 없습니다.',
            'variant-axis-invalid' => '변형 [:sku]에는 상위 상품의 각 변형 속성에 대한 유효한 옵션이 필요합니다: :attributes.',
            'variant-exists' => '상위 상품 [:parent]에 SKU [:sku] 또는 동일한 변형 속성 값을 가진 변형이 이미 있습니다.',
            'variant-values-taken' => '변형 [:sku]에 이 변형 속성 값을 지정할 수 없습니다. 상위 상품 [:parent]에 이미 같은 값의 변형이 있습니다.',
            'product-create-incomplete' => '상품 [:sku]이(가) 존재하지 않습니다. 생성하려면 type과 attribute_family_id가 모두 필요합니다.',
            'values-section-misplaced' => ':key은(는) 속성이 아닙니다. values.:section 안이 아니라 values의 최상위(values.:key)로 보내세요.',
            'values-attribute-section' => '속성 [:code]은(는) :path에 넣어야 합니다.',
            'values-price-shape' => '가격 속성 [:code]은(는) {"USD": "19.99"}처럼 통화 코드와 금액의 객체를 받습니다. 목록은 사용할 수 없습니다.',
            'values-currency-inactive' => '통화 [:currencies]이(가) 활성화되어 있지 않습니다. 다음 중 하나를 사용하세요: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => '금지됨: [:tool]을(를) 실행할 권한이 없습니다.',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'SELECT, WITH, SHOW, EXPLAIN 또는 DESCRIBE 문 하나만 허용됩니다.',
            'forbidden' => '쿼리에 :keyword이(가) 사용되었으며, 읽기 전용 쿼리에서는 허용되지 않습니다.',
        ],

        'run-skill' => [
            'not-found' => '스킬 [:name]을(를) 찾을 수 없습니다.',
        ],

        'command-runner' => [
            'blocked' => '[:command] 명령은 임의의 코드를 실행할 수 있으므로 허용되지 않습니다.',
        ],

        'plugin-generator' => [
            'invalid-name' => '플러그인 이름 [:name]이(가) 올바르지 않습니다. 대문자로 시작하고 영문자와 숫자만 사용하세요.',
        ],

        'test-generator' => [
            'invalid-package' => '패키지 이름 [:name]이(가) 올바르지 않습니다. MyPlugin 또는 Webkul/MyPlugin 같은 이름을 사용하세요.',
            'invalid-class' => '클래스 이름 [:name]이(가) 올바르지 않습니다. Services/MyService 같은 이름을 사용하세요.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method는 S256이어야 합니다. plain 방식은 지원되지 않습니다.',

        'registration' => [
            'body-too-large' => '등록 요청이 너무 큽니다. 최대 :max바이트까지 허용됩니다.',
            'too-many-redirect-uris' => '리디렉션 URI가 너무 많습니다. 최대 :max개까지 허용됩니다.',
            'redirect-uri-too-long' => '각 리디렉션 URI는 최대 :max자까지 가능합니다.',
            'rate-limited' => '등록 시도가 너무 많습니다. 잠시 후 다시 시도해 주세요.',
        ],
    ],
];
