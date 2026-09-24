<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Issuer
    |--------------------------------------------------------------------------
    |
    | Your own company details, printed on invoices, credit notes and
    | customer statements.
    |
    */

    'issuer' => [
        'name' => env('INVOICE_ISSUER_NAME', 'Acme Trading LLC'),
        'tax_number' => env('INVOICE_ISSUER_TAX_NUMBER', '100000000000003'),
        'address' => env('INVOICE_ISSUER_ADDRESS', '1 Example Street, Springfield'),
        'email' => env('INVOICE_ISSUER_EMAIL', 'billing@example.com'),
        'website' => env('INVOICE_ISSUER_WEBSITE', 'example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code shown next to amounts. The application works in a single
    | currency; amounts are stored as integer minor units (cents).
    |
    */

    'currency' => env('INVOICE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'payment_terms_days' => (int) env('INVOICE_PAYMENT_TERMS_DAYS', 30),

    'default_vat_rate' => env('INVOICE_DEFAULT_VAT_RATE', '5.00'),

    'number_prefixes' => [
        'invoice' => 'INV',
        'credit_note' => 'CN',
    ],

];
