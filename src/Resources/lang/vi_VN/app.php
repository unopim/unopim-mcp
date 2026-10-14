<?php

return [
    'authorize' => [
        'title' => 'Cho phép :client',
        'heading' => 'Cho phép :client',
        'lede' => 'Ứng dụng này đang yêu cầu quyền truy cập vào tài khoản UnoPim của bạn.',
        'signed-in-as' => 'Đã đăng nhập với tên',
        'permissions' => 'Ứng dụng sẽ có thể',
        'approve' => 'Cho phép',
        'approving' => 'Đang cho phép…',
        'deny' => 'Hủy',
        'footnote' => 'Quyền truy cập tuân theo vai trò và quyền hạn UnoPim hiện có của bạn.',
    ],

    'tools' => [
        'upsert-currencies' => [
            'can-not-disable' => 'Tiền tệ [:code] đang được một kênh sử dụng nên không thể tắt.',
        ],
        'upsert-products' => [
            'invalid-values' => 'Sản phẩm [:sku] (products.:index) không hợp lệ; không có gì trong lô được lưu.',
        ],

        'filters' => [
            'missing-field' => 'Mỗi bộ lọc cần có một trường.',
            'invalid-field' => 'Không thể lọc theo trường ":field". Hãy dùng một trong các trường: :fields.',
            'invalid-operator' => 'Toán tử ":operator" không được hỗ trợ. Hãy dùng một trong các toán tử: :operators.',
            'invalid-value' => 'Bộ lọc ":operator" trên ":field" chỉ nhận một giá trị, không nhận danh sách.',
            'invalid-cursor' => 'Con trỏ phân trang không hợp lệ hoặc thuộc về một lượt tìm kiếm khác. Hãy chạy lại tìm kiếm mà không dùng con trỏ.',
            'invalid-date' => 'Bộ lọc ":field" cần một ngày như 2026-01-31 hoặc 2026-01-31 13:45:00; ":value" không phải là ngày.',
            'invalid-number' => 'Bộ lọc ":field" cần một số; ":value" không phải là số.',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'Không thể thay đổi loại của thuộc tính [:code] sau khi đã tạo.',
            'category-invalid-parent' => 'Không thể đặt danh mục [:code] dưới chính nó hoặc dưới một danh mục con của nó.',
            'super-attributes-invalid' => 'Các thuộc tính siêu [:attributes] phải là thuộc tính lựa chọn của họ [:family] và không thay đổi theo ngôn ngữ hay kênh.',
            'parent-not-found' => 'Sản phẩm cha [:sku] không tồn tại hoặc không phải là sản phẩm có thể cấu hình.',
            'parent-unsupported' => 'Sản phẩm cha [:sku] dùng cấu trúc biến thể hai cấp. Hãy tạo các biến thể của nó qua UnoPim REST API.',
            'parent-immutable' => 'Sản phẩm [:sku] đã tồn tại; không thể thay đổi sản phẩm cha của nó.',
            'variant-axis-invalid' => 'Biến thể [:sku] cần một tùy chọn hợp lệ cho mỗi thuộc tính biến thể của sản phẩm cha: :attributes.',
            'variant-exists' => 'Sản phẩm cha [:parent] đã có một biến thể với SKU [:sku] hoặc với cùng các giá trị thuộc tính biến thể.',
            'variant-values-taken' => 'Biến thể [:sku] không thể nhận các giá trị thuộc tính biến thể này: sản phẩm cha [:parent] đã có một biến thể với các giá trị đó.',
            'product-create-incomplete' => 'Sản phẩm [:sku] không tồn tại. Cần cả type và attribute_family_id để tạo sản phẩm.',
            'values-section-misplaced' => ':key không phải là thuộc tính. Hãy gửi nó ở cấp cao nhất của values (values.:key), không phải bên trong values.:section.',
            'values-attribute-section' => 'Thuộc tính [:code] thuộc về :path.',
            'values-price-shape' => 'Thuộc tính giá [:code] nhận một đối tượng mã tiền tệ và số tiền, như {"USD": "19.99"}, không phải danh sách.',
            'values-currency-inactive' => 'Tiền tệ [:currencies] không hoạt động. Hãy dùng một trong: :active.',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'Bị cấm: bạn không có quyền chạy [:tool].',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'Chỉ cho phép một câu lệnh SELECT, WITH, SHOW, EXPLAIN hoặc DESCRIBE duy nhất.',
            'forbidden' => 'Truy vấn sử dụng :keyword, điều này không được phép trong truy vấn chỉ đọc.',
        ],

        'run-skill' => [
            'not-found' => 'Không tìm thấy kỹ năng [:name].',
        ],

        'command-runner' => [
            'blocked' => 'Lệnh [:command] không được phép vì nó có thể chạy mã tùy ý.',
        ],

        'plugin-generator' => [
            'invalid-name' => 'Tên plugin [:name] không hợp lệ. Chỉ dùng chữ cái và chữ số, bắt đầu bằng chữ in hoa.',
        ],

        'test-generator' => [
            'invalid-package' => 'Tên gói [:name] không hợp lệ. Hãy dùng tên như MyPlugin hoặc Webkul/MyPlugin.',
            'invalid-class' => 'Tên lớp [:name] không hợp lệ. Hãy dùng tên như Services/MyService.',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method phải là S256; phương thức plain không được hỗ trợ.',

        'registration' => [
            'body-too-large' => 'Yêu cầu đăng ký quá lớn. Giới hạn là :max byte.',
            'too-many-redirect-uris' => 'Có quá nhiều URI chuyển hướng. Chỉ cho phép tối đa :max.',
            'redirect-uri-too-long' => 'Mỗi URI chuyển hướng chỉ được dài tối đa :max ký tự.',
            'rate-limited' => 'Quá nhiều lần thử đăng ký. Vui lòng thử lại sau.',
        ],
    ],
];
