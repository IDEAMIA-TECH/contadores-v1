<?php
require_once __DIR__ . '/setup_test_db.php';
require_once __DIR__ . '/ClientTest.php';

echo "\nEjecutando pruebas...\n\n";

$clientTest = new ClientTest();
$clientTest->testCreateClient();
$clientTest->testCreateClientWithContact();
$clientTest->testParseCsf();

echo "\nPruebas completadas.\n"; 