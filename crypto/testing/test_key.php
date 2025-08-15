<?php
require_once __DIR__ . '/../core/crypto_helper.php';
$cipher = "/mQNEnbYz9kaUqGAIevR1AqUjI7M/ircjTPUE7x0hFuRyfn7zbklCaCyYYGHG2PVIvb1rU6lTLnsPESM7hcty47RbjbDCZxmYAMDHFD5lhs=";

try {
    $plain = decryptWithAES($cipher);
    echo "Hasil decrypt: " . $plain . "\n";
} catch (Throwable $e) {
    echo "Gagal decrypt: " . $e->getMessage() . "\n";
}
