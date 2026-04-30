<?php

return [
    'domain' => env('APP_DOMAIN', '.ubtsu.ac.id'),
    'auto_create_user' => env('SSO_AUTO_CREATE_USER', false),
    'allowed_ip' => env('ALLOWED_IP', '*'),
    'prefix_number' => [
        'procurement' => 'PRC',
        'inventory_out' => 'INV-OUT',
        'inventory_mutation' => 'INV-MUT',
        'asset_disposal' => 'AST-DSP',
        'asset_change' => 'AST-CHG',
    ],
    'pemanfaatan' => [
        '01' =>    'Digunakan sendiri untuk dinas jabatan',
        '02' =>    'Digunakan sendiri untuk operasional',
        '03' =>    'Digunakan oleh satker lain dalam satu Kementerian/ Lembaga (K/L)',
        '04' =>    'Digunakan oleh satker lain diluar  Kementerian/ Lembaga (K/L)',
        '05' =>    'Digunakan oleh pihak lain tidak sesuai ketentuan',
        '06' =>    'Dimanfaatkan : Sewa',
        '07' =>    'Dimanfaatkan : Pinjam Pakai',
        '08' =>    'Dimanfaatkan : Kerjasama Pemanfaatan (KSP)',
        '09' =>    'Dimanfaatkan : Bangun Guna Serah (BGS)/ Bangun Serah Guna (BSG)',
        '10' =>    'Pemanfaatan Lainnya Sesuai Ketentuan'
    ]
];
