<?php
$payload = json_encode(['contents'=>[['parts'=>[['text'=>'Hola']]]]]);
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=TU_GEMINI_API_KEY');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
echo "HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Response: " . $res . "\n";
echo "Error: " . curl_error($ch) . "\n";
echo "Response: " . $res . "\n";
echo "Error: " . curl_error($ch) . "\n";
