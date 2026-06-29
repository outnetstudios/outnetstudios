<?php
$key = random_bytes(32);
echo 'ENCRYPTION_KEY_BASE64=' . base64_encode($key) . PHP_EOL;
