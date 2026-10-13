<?php

return [
    'authorize' => [
        'title' => 'Разрешить :client',
        'heading' => 'Разрешить :client',
        'lede' => 'Это приложение запрашивает доступ к вашей учётной записи UnoPim.',
        'signed-in-as' => 'Вы вошли как',
        'permissions' => 'Оно сможет',
        'approve' => 'Разрешить',
        'approving' => 'Выполняется авторизация…',
        'deny' => 'Отмена',
        'footnote' => 'Доступ определяется вашей текущей ролью и правами в UnoPim.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'Валюта [:code] используется каналом, её нельзя отключить.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Товар [:sku] (products.:index) недопустим; ничего из пакета не сохранено.',
        ],

        'filters' => [
            'missing-field' => 'Каждому фильтру нужно поле.',
            'invalid-field' => 'По полю ":field" фильтровать нельзя. Используйте одно из: :fields.',
            'invalid-operator' => 'Оператор ":operator" не поддерживается. Используйте один из: :operators.',
            'invalid-value' => 'Фильтр ":operator" по полю ":field" принимает одно значение, а не список.',
            'invalid-cursor' => 'Курсор пагинации недействителен или относится к другому поиску. Повторите поиск без курсора.',
            'invalid-date' => 'Фильтру ":field" нужна дата, например 2026-01-31 или 2026-01-31 13:45:00; ":value" не является датой.',
            'invalid-number' => 'Фильтру ":field" нужно число; ":value" не является числом.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Тип атрибута [:code] нельзя изменить после его создания.',
            'category-invalid-parent' => 'Категорию [:code] нельзя разместить внутри неё самой или одной из её подкатегорий.',
            'super-attributes-invalid' => 'Супер-атрибуты [:attributes] должны быть атрибутами выбора семейства [:family], не зависящими от локали или канала.',
            'parent-not-found' => 'Родительский товар [:sku] не существует или не является конфигурируемым товаром.',
            'parent-unsupported' => 'Родительский товар [:sku] использует двухуровневую структуру вариантов. Создайте его варианты через REST API UnoPim.',
            'parent-immutable' => 'Товар [:sku] уже существует; его родительский товар изменить нельзя.',
            'variant-axis-invalid' => 'Варианту [:sku] нужна допустимая опция для каждого вариантного атрибута родительского товара: :attributes.',
            'variant-exists' => 'У родительского товара [:parent] уже есть вариант с SKU [:sku] или с теми же значениями вариантных атрибутов.',
            'variant-values-taken' => 'Вариант [:sku] не может получить эти значения атрибутов варианта: у родительского товара [:parent] уже есть вариант с ними.',
            'product-create-incomplete' => 'Товар [:sku] не существует. Для его создания нужны и type, и attribute_family_id.',
            'values-section-misplaced' => ':key — это не атрибут. Передайте его на верхнем уровне values (values.:key), а не внутри values.:section.',
            'values-attribute-section' => 'Атрибут [:code] указывается в :path.',
            'values-price-shape' => 'Атрибут цены [:code] принимает объект «код валюты — сумма», например {"USD": "19.99"}, а не список.',
            'values-currency-inactive' => 'Валюта [:currencies] неактивна. Используйте одну из: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Запрещено: у вас нет прав на запуск [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Разрешён только один оператор SELECT, WITH, SHOW, EXPLAIN или DESCRIBE.',
            'forbidden' => 'В запросе используется :keyword, что недопустимо в запросе только для чтения.',
        ],

        'run-skill' => [
            'not-found' => 'Навык [:name] не найден.',
        ],

        'command-runner' => [
            'blocked' => 'Команда [:command] запрещена, так как она может выполнить произвольный код.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Недопустимое имя плагина [:name]. Используйте только буквы и цифры, начиная с заглавной буквы.',
        ],

        'test-generator' => [
            'invalid-package' => 'Недопустимое имя пакета [:name]. Используйте имя вида MyPlugin или Webkul/MyPlugin.',
            'invalid-class' => 'Недопустимое имя класса [:name]. Используйте имя вида Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Значение code_challenge_method должно быть S256; метод plain не поддерживается.',

        'registration' => [
            'body-too-large' => 'Запрос на регистрацию слишком большой. Ограничение — :max байт.',
            'too-many-redirect-uris' => 'Слишком много URI перенаправления. Допускается не более :max.',
            'redirect-uri-too-long' => 'Длина каждого URI перенаправления не должна превышать :max символов.',
            'rate-limited' => 'Слишком много попыток регистрации. Повторите попытку позже.',
        ],
    ],
];
