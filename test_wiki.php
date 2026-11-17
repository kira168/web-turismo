<?php
$options = [
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]
];
$context = stream_context_create($options);
$data = file_get_contents('https://es.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=Trujillo%20Peru%20ciudad&gsrlimit=5&prop=pageimages&pithumbsize=1280&format=json&origin=*', false, $context);
echo $data;
