<?php
header('Content-Type: text/plain');
echo "RAFIA  : " . password_hash('DrRafia@123',  PASSWORD_DEFAULT) . "\n";
echo "CHONG  : " . password_hash('DrChong@123',  PASSWORD_DEFAULT) . "\n";
echo "SARA   : " . password_hash('DrSara@123',   PASSWORD_DEFAULT) . "\n";
