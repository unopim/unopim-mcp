<?php

return [
    'authorize' => [
        'title' => 'Otorisasi :client',
        'heading' => 'Otorisasi :client',
        'lede' => 'Aplikasi ini meminta akses ke akun UnoPim Anda.',
        'signed-in-as' => 'Masuk sebagai',
        'permissions' => 'Aplikasi ini akan dapat',
        'approve' => 'Otorisasi',
        'approving' => 'Mengotorisasi…',
        'deny' => 'Batal',
        'footnote' => 'Akses mengikuti peran dan izin UnoPim Anda yang ada.',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'Eksekusi pekerjaan tidak ditemukan.',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'Mata uang [:code] digunakan oleh sebuah kanal dan tidak dapat dinonaktifkan.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Produk [:sku] (products.:index) tidak valid; tidak ada yang disimpan dari batch ini.',
        ],

        'filters' => [
            'missing-field' => 'Setiap filter memerlukan sebuah field.',
            'invalid-field' => 'Field ":field" tidak dapat difilter. Gunakan salah satu dari: :fields.',
            'invalid-operator' => 'Operator ":operator" tidak didukung. Gunakan salah satu dari: :operators.',
            'invalid-value' => 'Filter ":operator" pada ":field" menerima satu nilai, bukan daftar.',
            'invalid-cursor' => 'Kursor paginasi tidak valid atau milik pencarian lain. Jalankan ulang pencarian tanpa kursor.',
            'invalid-date' => 'Filter ":field" memerlukan tanggal seperti 2026-01-31 atau 2026-01-31 13:45:00; ":value" bukan tanggal.',
            'invalid-number' => 'Filter ":field" memerlukan angka; ":value" bukan angka.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Tipe atribut [:code] tidak dapat diubah setelah dibuat.',
            'category-invalid-parent' => 'Kategori [:code] tidak dapat ditempatkan di bawah dirinya sendiri atau salah satu subkategorinya.',
            'super-attributes-invalid' => 'Atribut super [:attributes] harus berupa atribut pilihan dari keluarga [:family] yang tidak berbeda per lokal atau kanal.',
            'parent-not-found' => 'Produk induk [:sku] tidak ada atau bukan produk yang dapat dikonfigurasi.',
            'parent-unsupported' => 'Produk induk [:sku] menggunakan struktur varian dua tingkat. Buat variannya melalui REST API UnoPim.',
            'parent-immutable' => 'Produk [:sku] sudah ada; produk induknya tidak dapat diubah.',
            'variant-axis-invalid' => 'Varian [:sku] memerlukan opsi yang valid untuk setiap atribut varian dari produk induknya: :attributes.',
            'variant-exists' => 'Produk induk [:parent] sudah memiliki varian dengan SKU [:sku] atau dengan nilai atribut varian yang sama.',
            'variant-values-taken' => 'Varian [:sku] tidak dapat memakai nilai atribut varian ini: produk induk [:parent] sudah memiliki varian dengan nilai tersebut.',
            'product-create-incomplete' => 'Produk [:sku] tidak ada. type dan attribute_family_id wajib diisi untuk membuatnya.',
            'values-section-misplaced' => ':key bukan atribut. Kirim di tingkat teratas values (values.:key), bukan di dalam values.:section.',
            'values-attribute-section' => 'Atribut [:code] ditempatkan di :path.',
            'values-price-shape' => 'Atribut harga [:code] menerima objek kode mata uang ke jumlah, seperti {"USD": "19.99"}, bukan daftar.',
            'values-currency-inactive' => 'Mata uang [:currencies] tidak aktif. Gunakan salah satu dari: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Dilarang: Anda tidak memiliki izin untuk menjalankan [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Hanya satu pernyataan SELECT, WITH, SHOW, EXPLAIN, atau DESCRIBE yang diizinkan.',
            'forbidden' => 'Kueri menggunakan :keyword, yang tidak diizinkan dalam kueri hanya-baca.',
        ],

        'run-skill' => [
            'not-found' => 'Skill [:name] tidak ditemukan.',
        ],

        'command-runner' => [
            'blocked' => 'Perintah [:command] tidak diizinkan karena dapat menjalankan kode sembarang.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Nama plugin [:name] tidak valid. Gunakan hanya huruf dan angka, diawali huruf kapital.',
        ],

        'test-generator' => [
            'invalid-package' => 'Nama paket [:name] tidak valid. Gunakan nama seperti MyPlugin atau Webkul/MyPlugin.',
            'invalid-class' => 'Nama kelas [:name] tidak valid. Gunakan nama seperti Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method harus S256; metode plain tidak didukung.',

        'registration' => [
            'body-too-large' => 'Permintaan pendaftaran terlalu besar. Batasnya :max byte.',
            'too-many-redirect-uris' => 'Terlalu banyak URI pengalihan. Maksimal :max yang diizinkan.',
            'redirect-uri-too-long' => 'Setiap URI pengalihan maksimal :max karakter.',
            'rate-limited' => 'Terlalu banyak percobaan pendaftaran. Silakan coba lagi nanti.',
        ],
    ],
];
