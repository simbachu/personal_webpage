<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dbPath = 'var/catalog.sqlite';
if (!file_exists($dbPath)) {
    echo "SQLite database not found at $dbPath\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$total = $pdo->query('SELECT COUNT(*) FROM pokemon_species')->fetchColumn();
echo "Total species: $total\n";

$withForms = $pdo->query('SELECT COUNT(*) FROM pokemon_species WHERE forms_json != "[]" AND forms_json != \'[]\'')->fetchColumn();
echo "Species with forms: $withForms\n";

// Sample species with forms
$rows = $pdo->query('SELECT species_name, forms_json FROM pokemon_species WHERE forms_json != "[]" AND forms_json != \'[]\' LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo "\nSample species with forms:\n";
foreach ($rows as $r) {
    $forms = json_decode($r['forms_json'], true) ?: [];
    echo "  " . $r['species_name'] . ": " . count($forms) . " forms\n";
}

