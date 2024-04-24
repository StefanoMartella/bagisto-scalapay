<?php

return [
    [
        'key'    => 'sales.payment_methods.scalapay',
        'name'   => 'Scalapay',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'scalapay::app.scalapay.system.description',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name'          => 'description',
                'title'         => 'scalapay::app.scalapay.system.description',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name'          => 'scalapay_api_key',
                'title'         => 'scalapay::app.scalapay.system.client-secret',
                'info'          => 'scalapay::app.scalapay.system.client-secret-info',
                'type'          => 'password',
                'depend'        => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name'          => 'image',
                'title'         => 'scalapay::app.scalapay.system.image',
                'type'          => 'file',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ]
        ]
    ]
];