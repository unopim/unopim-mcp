<?php

return [
    'authorize' => [
        'title' => 'अधिकृत करें :client',
        'heading' => 'अधिकृत करें :client',
        'lede' => 'यह एप्लिकेशन आपके UnoPim खाते तक पहुँच का अनुरोध कर रही है।',
        'signed-in-as' => 'इस रूप में साइन इन हैं',
        'redirects-to' => 'यहाँ रीडायरेक्ट करेगा',
        'permissions' => 'यह निम्न कर सकेगा',
        'approve' => 'अधिकृत करें',
        'approving' => 'अधिकृत किया जा रहा है…',
        'deny' => 'रद्द करें',
        'footnote' => 'पहुँच आपकी मौजूदा UnoPim भूमिका और अनुमतियों के अनुसार रहती है।',
    ],

    'tools' => [
        'job-execution' => [
            'not-found' => 'जॉब निष्पादन नहीं मिला।',
        ],
        'upsert-currencies' => [
            'can-not-disable' => 'मुद्रा [:code] किसी चैनल में उपयोग हो रही है, इसलिए इसे निष्क्रिय नहीं किया जा सकता।',
        ],
        'upsert-products' => [
            'invalid-values' => 'उत्पाद [:sku] (products.:index) मान्य नहीं है; बैच में से कुछ भी सहेजा नहीं गया।',
        ],

        'filters' => [
            'missing-field' => 'हर फ़िल्टर के लिए एक फ़ील्ड ज़रूरी है।',
            'invalid-field' => 'फ़ील्ड ":field" पर फ़िल्टर नहीं किया जा सकता। इनमें से किसी एक का उपयोग करें: :fields.',
            'invalid-operator' => 'ऑपरेटर ":operator" समर्थित नहीं है। इनमें से किसी एक का उपयोग करें: :operators.',
            'invalid-value' => '":field" पर ":operator" फ़िल्टर एक ही मान लेता है, सूची नहीं।',
            'invalid-cursor' => 'पेजिनेशन कर्सर अमान्य है या किसी दूसरी खोज का है। कर्सर के बिना खोज फिर से चलाएँ।',
            'invalid-date' => '":field" फ़िल्टर को 2026-01-31 या 2026-01-31 13:45:00 जैसी तारीख चाहिए; ":value" तारीख नहीं है।',
            'invalid-number' => '":field" फ़िल्टर को एक संख्या चाहिए; ":value" संख्या नहीं है।',
        ],

        'catalog-writes' => [
            'attribute-type-immutable' => 'एट्रिब्यूट [:code] बनने के बाद उसका प्रकार नहीं बदला जा सकता।',
            'category-invalid-parent' => 'श्रेणी [:code] को स्वयं के या अपनी किसी उप-श्रेणी के नीचे नहीं रखा जा सकता।',
            'super-attributes-invalid' => 'सुपर एट्रिब्यूट [:attributes] परिवार [:family] के ऐसे सेलेक्ट एट्रिब्यूट होने चाहिए जो लोकेल या चैनल के अनुसार नहीं बदलते।',
            'parent-not-found' => 'पैरेंट उत्पाद [:sku] मौजूद नहीं है या कॉन्फ़िगर करने योग्य उत्पाद नहीं है।',
            'parent-unsupported' => 'पैरेंट उत्पाद [:sku] दो-स्तरीय वेरिएंट संरचना का उपयोग करता है। इसके वेरिएंट UnoPim REST API से बनाएँ।',
            'parent-immutable' => 'उत्पाद [:sku] पहले से मौजूद है; इसका पैरेंट नहीं बदला जा सकता।',
            'variant-axis-invalid' => 'वेरिएंट [:sku] को अपने पैरेंट के हर वेरिएंट एट्रिब्यूट के लिए एक मान्य विकल्प चाहिए: :attributes.',
            'variant-exists' => 'पैरेंट उत्पाद [:parent] में पहले से SKU [:sku] वाला या समान वेरिएंट एट्रिब्यूट मानों वाला वेरिएंट है।',
            'variant-values-taken' => 'वेरिएंट [:sku] ये वेरिएंट एट्रिब्यूट मान नहीं ले सकता: पैरेंट उत्पाद [:parent] में इन मानों वाला वेरिएंट पहले से है।',
            'product-create-incomplete' => 'उत्पाद [:sku] मौजूद नहीं है। इसे बनाने के लिए type और attribute_family_id दोनों आवश्यक हैं।',
            'values-section-misplaced' => ':key कोई एट्रिब्यूट नहीं है। इसे values के शीर्ष स्तर (values.:key) पर भेजें, values.:section के अंदर नहीं।',
            'values-attribute-section' => 'एट्रिब्यूट [:code] :path में जाता है।',
            'values-price-shape' => 'मूल्य एट्रिब्यूट [:code] मुद्रा कोड से राशि वाला ऑब्जेक्ट लेता है, जैसे {"USD": "19.99"}, सूची नहीं।',
            'values-currency-inactive' => 'मुद्रा [:currencies] सक्रिय नहीं है। इनमें से किसी एक का उपयोग करें: :active।',
        ],
    ],

    'call-tool' => [
        'forbidden' => 'निषिद्ध: आपको [:tool] चलाने की अनुमति नहीं है।',
    ],

    'dev' => [
        'database-query' => [
            'read-only' => 'केवल एक SELECT, WITH, SHOW, EXPLAIN या DESCRIBE स्टेटमेंट की अनुमति है।',
            'forbidden' => 'क्वेरी :keyword का उपयोग करती है, जिसकी केवल-पढ़ने वाली क्वेरी में अनुमति नहीं है।',
        ],

        'run-skill' => [
            'not-found' => 'स्किल [:name] नहीं मिली।',
        ],

        'command-runner' => [
            'blocked' => '[:command] कमांड की अनुमति नहीं है क्योंकि यह मनमाना कोड चला सकती है।',
        ],

        'plugin-generator' => [
            'invalid-name' => 'प्लगइन नाम [:name] अमान्य है। केवल अक्षरों और अंकों का उपयोग करें, और बड़े अक्षर से शुरू करें।',
        ],

        'test-generator' => [
            'invalid-package' => 'पैकेज नाम [:name] अमान्य है। MyPlugin या Webkul/MyPlugin जैसा नाम उपयोग करें।',
            'invalid-class' => 'क्लास नाम [:name] अमान्य है। Services/MyService जैसा नाम उपयोग करें।',
        ],
    ],

    'oauth' => [
        'pkce-s256-required' => 'code_challenge_method का मान S256 होना चाहिए; plain विधि समर्थित नहीं है।',

        'registration' => [
            'body-too-large' => 'पंजीकरण अनुरोध बहुत बड़ा है। सीमा :max बाइट है।',
            'too-many-redirect-uris' => 'बहुत अधिक रीडायरेक्ट URI हैं। अधिकतम :max की अनुमति है।',
            'redirect-uri-too-long' => 'प्रत्येक रीडायरेक्ट URI अधिकतम :max वर्णों का हो सकता है।',
            'rate-limited' => 'बहुत अधिक पंजीकरण प्रयास। कृपया बाद में पुनः प्रयास करें।',
        ],
    ],
];
