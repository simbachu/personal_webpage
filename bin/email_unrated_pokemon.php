<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;
use App\Type\MonsterIdentifier;

// Load opinions
$opinionsPath = __DIR__ . '/../content/pokemon_opinions.yaml';
$opinionService = new PokemonOpinionService($opinionsPath);
$rated = array_map('strtolower', $opinionService->getAllOpinionNames());

// Choose 3 random unrated Pokémon IDs from 1..1010 (Gen 1-9 range; adjust as needed)
$maxId = 1025;
$ids = range(1, $maxId);
$unratedIds = array_values(array_filter($ids, function (int $id) use ($rated): bool {
    // Only filter by id if name unknown; we'll check service later
    return true;
}));

shuffle($unratedIds);
$chosen = array_slice($unratedIds, 0, 10); // sample more to account for rated-by-name check

$poke = new PokeApiService();
$baseUrl = getenv('BASE_URL') ?: '';

$selected = [];
foreach ($chosen as $id) {
    $result = $poke->fetchMonster(MonsterIdentifier::fromString((string)$id));
    if ($result->isFailure()) {
        continue;
    }
    $m = $result->getValue();
    $nameKey = strtolower($m->name);
    if (in_array($nameKey, $rated, true)) {
        continue;
    }
    $sprite = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/' . $m->id . '.png';
    $link = ($baseUrl !== '' ? rtrim($baseUrl, '/') : '') . '/dex/' . strtolower($nameKey);
    $selected[] = [
        'id' => $m->id,
        'name' => $m->name,
        'sprite' => $sprite,
        'link' => $link,
    ];
    if (count($selected) >= 3) {
        break;
    }
}

// Fallback: if fewer than 3 found, attempt more random picks
if (count($selected) < 3) {
    for ($i = 0; $i < 50 && count($selected) < 3; $i++) {
        $id = random_int(1, $maxId);
        $result = $poke->fetchMonster(MonsterIdentifier::fromString((string)$id));
        if ($result->isFailure()) {
            continue;
        }
        $m = $result->getValue();
        $nameKey = strtolower($m->name);
        if (in_array($nameKey, $rated, true)) {
            continue;
        }
        $sprite = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/' . $m->id . '.png';
        $link = ($baseUrl !== '' ? rtrim($baseUrl, '/') : '') . '/dex/' . strtolower($nameKey);
        $selected[] = [
            'id' => $m->id,
            'name' => $m->name,
            'sprite' => $sprite,
            'link' => $link,
        ];
    }
}

// Render simple HTML email
echo "<html><body>\n";
echo "<h1>Three random unrated Pokémon</h1>\n";
echo "<ul style=\"list-style:none;padding:0;\">\n";
foreach ($selected as $s) {
    $img = htmlspecialchars($s['sprite']);
    $name = htmlspecialchars($s['name']);
    $link = htmlspecialchars($s['link']);
    echo "  <li style=\"margin-bottom:12px;\"><a href=\"$link\" style=\"text-decoration:none;color:#C84C3C;\"><img src=\"$img\" alt=\"$name\" style=\"width:72px;height:72px;object-fit:contain;vertical-align:middle;margin-right:8px;\">$name</a></li>\n";
}
echo "</ul>\n";
echo "</body></html>\n";


