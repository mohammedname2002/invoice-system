<?php

return [
    /*
    | Issuer (your company) details printed on invoices and credit notes.
    */
    'issuer' => [
        'name' => env('INVOICE_ISSUER_NAME', 'Acme Trading LLC'),
        'tax_number' => env('INVOICE_ISSUER_TAX_NUMBER', '100000000000003'),
        'address' => env('INVOICE_ISSUER_ADDRESS', '1 Example Street, Springfield'),
        'website' => env('INVOICE_ISSUER_WEBSITE', 'example.com'),
    ],
];
