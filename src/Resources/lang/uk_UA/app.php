<?php

return [
    'authorize' => [
        'title' => 'Дозволити :client',
        'heading' => 'Дозволити :client',
        'lede' => 'Цей застосунок запитує доступ до вашого облікового запису UnoPim.',
        'signed-in-as' => 'Ви увійшли як',
        'redirects-to' => 'Перенаправить на',
        'permissions' => 'Він зможе',
        'approve' => 'Дозволити',
        'approving' => 'Виконується авторизація…',
        'deny' => 'Скасувати',
        'footnote' => 'Доступ визначається вашою поточною роллю та дозволами в UnoPim.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Виконання завдання не знайдено.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Валюта [:code] використовується каналом, тому її не можна вимкнути.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Товар [:sku] (products.:index) недійсний; нічого з пакета не збережено.',
        ],

        'filters' => [
            'missing-field' => 'Кожному фільтру потрібне поле.',
            'invalid-field' => 'За полем ":field" фільтрувати не можна. Використайте одне з: :fields.',
            'invalid-operator' => 'Оператор ":operator" не підтримується. Використайте один із: :operators.',
            'invalid-value' => 'Фільтр ":operator" за полем ":field" приймає одне значення, а не список.',
            'invalid-cursor' => 'Курсор пагінації недійсний або належить до іншого пошуку. Повторіть пошук без курсора.',
            'invalid-date' => 'Фільтру ":field" потрібна дата, наприклад 2026-01-31 або 2026-01-31 13:45:00; ":value" не є датою.',
            'invalid-number' => 'Фільтру ":field" потрібне число; ":value" не є числом.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Тип атрибута [:code] не можна змінити після його створення.',
            'category-invalid-parent' => 'Категорію [:code] не можна розмістити в ній самій або в одній з її підкатегорій.',
            'super-attributes-invalid' => 'Супер-атрибути [:attributes] мають бути атрибутами вибору сімейства [:family], що не залежать від локалі чи каналу.',
            'parent-not-found' => 'Батьківський товар [:sku] не існує або не є конфігурованим товаром.',
            'parent-unsupported' => 'Батьківський товар [:sku] використовує дворівневу структуру варіантів. Створіть його варіанти через REST API UnoPim.',
            'parent-immutable' => 'Товар [:sku] уже існує; його батьківський товар змінити не можна.',
            'variant-axis-invalid' => 'Варіанту [:sku] потрібна дійсна опція для кожного варіантного атрибута батьківського товару: :attributes.',
            'variant-exists' => 'Батьківський товар [:parent] уже має варіант з SKU [:sku] або з тими самими значеннями варіантних атрибутів.',
            'variant-values-taken' => 'Варіант [:sku] не може отримати ці значення атрибутів варіанта: батьківський товар [:parent] уже має варіант із ними.',
            'product-create-incomplete' => 'Товар [:sku] не існує. Для його створення потрібні і type, і attribute_family_id.',
            'values-section-misplaced' => ':key не є атрибутом. Передайте його на верхньому рівні values (values.:key), а не всередині values.:section.',
            'values-attribute-section' => 'Атрибут [:code] вказується в :path.',
            'values-price-shape' => 'Атрибут ціни [:code] приймає об\'єкт «код валюти — сума», наприклад {"USD": "19.99"}, а не список.',
            'values-currency-inactive' => 'Валюта [:currencies] неактивна. Використайте одну з: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Заборонено: у вас немає дозволу на запуск [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Дозволено лише один оператор SELECT, WITH, SHOW, EXPLAIN або DESCRIBE.',
            'forbidden' => 'Запит використовує :keyword, що недопустимо в запиті лише для читання.',
        ],

        'run-skill' => [
            'not-found' => 'Навичку [:name] не знайдено.',
        ],

        'command-runner' => [
            'blocked' => 'Команда [:command] заборонена, оскільки вона може виконати довільний код.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Недійсна назва плагіна [:name]. Використовуйте лише літери та цифри, починаючи з великої літери.',
        ],

        'test-generator' => [
            'invalid-package' => 'Недійсна назва пакета [:name]. Використовуйте назву на кшталт MyPlugin або Webkul/MyPlugin.',
            'invalid-class' => 'Недійсна назва класу [:name]. Використовуйте назву на кшталт Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'Значення code_challenge_method має бути S256; метод plain не підтримується.',

        'registration' => [
            'body-too-large' => 'Запит на реєстрацію завеликий. Обмеження — :max байт.',
            'too-many-redirect-uris' => 'Забагато URI перенаправлення. Дозволено не більше :max.',
            'redirect-uri-too-long' => 'Довжина кожного URI перенаправлення не може перевищувати :max символів.',
            'rate-limited' => 'Забагато спроб реєстрації. Спробуйте пізніше.',
        ],
    ],
];
