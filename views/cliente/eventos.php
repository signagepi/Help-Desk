<?php
header('Content-Type: application/json');

$eventos = [
    [
        'title' => 'Solicitação Técnica',
        'start' => '2025-10-25',
        'end' => '2025-10-26'
    ],
    [
        'title' => 'Reunião com suporte',
        'start' => '2025-10-28T14:00:00'
    ]
];

echo json_encode($eventos);
