<?php
ini_set("display_errors", 1);

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pokemonId = isset($_GET['id']) ? $_GET['id'] : null;
$evolutions = [];

$statsLabels = [
    "HP" => "PV",
    "attack" => "Attaque",
    "defense" => "Défense",
    "special_attack" => "Attaque Spéciale",
    "special_defense" => "Défense Spéciale",
    "speed" => "Vitesse"
];

function getPokemonInfos($pokemonId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT pokemons.*, GROUP_CONCAT(types.name) as types FROM pokemons LEFT JOIN pokemonsTypes ON pokemons.id = pokemonsTypes.pokemonId LEFT JOIN types ON pokemonsTypes.typeName = types.name WHERE pokemons.id = ? GROUP BY pokemons.id");
    $stmt->bind_param("s", $pokemonId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pokemonData = $result->fetch_object();
        $pokemonData->apiTypes = [];
        $types = explode(",", $pokemonData->types);

        foreach ($types as $type) {
            $pokemonData->apiTypes[] = (object) [
                "name" => $type,
                "image" => "./types/$type.png"
            ];
        }

        return $pokemonData;
    } else {
        $stmt->close();
        $url = "https://pokebuildapi.fr/api/v1/pokemon/$pokemonId";
        $response = file_get_contents($url);
        $pokemonData = json_decode($response);
        addPokemonToDatabase($pokemonData);
        return $pokemonData;
    }
}

function addPokemonToDatabase($pokemonData)
{
    global $conn;
    $imageUrl = $pokemonData->image;
    $imagePath = "./images/$pokemonData->id.png";
    $spriteUrl = $pokemonData->sprite;
    $spritePath = "./sprites/$pokemonData->id.png";
    $preEvolutionId = $pokemonData->apiPreEvolution->pokedexIdd ?? null;
    if ($preEvolutionId) $sql = "INSERT INTO pokemons (id, name, image, apiGeneration, sprite, preEvolutionId) VALUES ('$pokemonData->id', '$pokemonData->name', './images/$pokemonData->id.png', '$pokemonData->apiGeneration', './sprites/$pokemonData->id.png', '$preEvolutionId' )";
    else $sql = "INSERT INTO pokemons (id, name, image, apiGeneration, sprite) VALUES ('$pokemonData->id', '$pokemonData->name', './images/$pokemonData->id.png', '$pokemonData->apiGeneration', './sprites/$pokemonData->id.png' )";
    if ($conn->query($sql) === TRUE) {
        saveImage($imageUrl, $imagePath);
        saveImage($spriteUrl, $spritePath);
        if (isset($pokemonData->apiTypes)) {
            foreach ($pokemonData->apiTypes as $type) {
                if (!getTypeInfos($type->name)) addTypeToDatabase($type);
                associatePokemonWithType($pokemonData->id, $type->name);
            }
        }
        if ($pokemonData->apiEvolutions != []) {
            foreach ($pokemonData->apiEvolutions as $evolution) {
                if (!getEvolution($evolution->pokedexId)) addEvolutionToDatabase($pokemonData->id, $evolution->pokedexId);
            }
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

function getTypeInfos($typeName)
{
    global $conn;
    $typeName = $conn->real_escape_string($typeName);
    $sql = "SELECT * FROM types WHERE name = '$typeName'";
    $result = $conn->query($sql);
    return $result->fetch_object();
}

function getEvolution($evolutionId)
{
    global $conn;
    $sql = "SELECT * FROM pokemonsEvolutions WHERE evolutionId = '$evolutionId'";
    $result = $conn->query($sql);
    $evolutionData = $result->fetch_object();
    return $evolutionData->evolution->id ?? null;
}

function getPreEvolution($pokemonData)
{
    if (isset($pokemonData->preEvolutionId) || isset($pokemonData->apiPreEvolution->pokedexIdd)) {
        return getPokemonInfos($pokemonData->preEvolutionId ?? $pokemonData->apiPreEvolution->pokedexIdd);
    }
}

function getEvolutions($pokemonId)
{
    global $conn;

    $stmt = $conn->prepare("SELECT evolutionId FROM pokemonsEvolutions WHERE pokemonId = ?");
    $stmt->bind_param("i", $pokemonId);
    $stmt->execute();
    $result = $stmt->get_result();

    $evolutions = [];

    while ($row = $result->fetch_assoc()) {
        $evolutionId = $row['evolutionId'];
        $evolutionData = getEvolutionData($evolutionId);
        $evolutions[] = $evolutionData;
    }

    $stmt->close();

    return $evolutions;
}

function getEvolutionData($evolutionId)
{
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM pokemons WHERE id = ?");
    $stmt->bind_param("i", $evolutionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $evolutionData = $result->fetch_object();
    } else {
        $url = "https://pokebuildapi.fr/api/v1/pokemon/$evolutionId";
        $response = file_get_contents($url);
        $evolutionData = json_decode($response);
    }

    $stmt->close();

    return $evolutionData;
}

function addTypeToDatabase($type)
{
    global $conn;
    $typeName = $type->name;
    $imageUrl = $type->image;
    $imagePath = "./types/$typeName.png";
    $sql = "INSERT INTO types (name, image) VALUES ('$typeName', './types/$typeName.png')";
    if ($conn->query($sql) === TRUE) {
        saveImage($imageUrl, $imagePath);
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

function associatePokemonWithType($pokemonId, $typeName)
{
    global $conn;
    $sql = "INSERT INTO pokemonsTypes (pokemonId, typeName) VALUES ('$pokemonId', '$typeName')";
    return $conn->query($sql);
}

function addEvolutionToDatabase($pokemonId, $evolutionId)
{
    global $conn;
    $sql = "INSERT INTO pokemonsEvolutions (pokemonId, evolutionId) VALUES ('$pokemonId', '$evolutionId')";
    return $conn->query($sql);
}

function getFormattedId($id)
{
    if ($id < 10) {
        return "00$id";
    } else if ($id < 100) {
        return "0$id";
    } else {
        return "$id";
    }
}

function getFormattedStat($statName, $statValue)
{
    global $statsLabels;
    return "<strong>$statsLabels[$statName]</strong> : $statValue";
}

function saveImage($url, $path)
{
    $image = file_get_contents($url);
    file_put_contents($path, $image);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pokedex</title>
    <link rel="stylesheet" href="./css/reset.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/pokemon.css">
</head>

<body>
    <?php include './components/header.php'; ?>
    <main class="container">
        <?php
        if ($pokemonId) {
            $pokemonData = getPokemonInfos($pokemonId);
            if ($pokemonData) {
        ?>
                <div class="pokemon">
                    <div class="head">
                        <p class="number">
                            No. <?= getFormattedId($pokemonData->id) ?>
                        <p>
                        <h1>
                            <?= $pokemonData->name ?>
                        </h1>
                        <ul class="types">
                            <?php
                            foreach ($pokemonData->apiTypes as $type) {
                            ?>
                                <li>
                                    <img src="<?= $type->image ?>" alt="<?= $type->name ?>">
                                    <p><?= $type->name ?></p>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="image">
                        <?php
                        $previousId = $pokemonData->id - 1;
                        $nextId = $pokemonData->id + 1;
                        ?>
                        <a class="button grey square" href="pokemon.php?id=<?= $previousId ?>">
                            <img src="./icons/arrow-left-solid.svg" alt="Left Arrow">
                        </a>
                        <img src="<?= "$pokemonData->image"; ?>" alt="<?= $pokemonData->name ?>">
                        <a class="button grey square" href="pokemon.php?id=<?= $nextId ?>">
                            <img src="./icons/arrow-right-solid.svg" alt="Right Arrow">
                        </a>
                    </div>
                    <div class="stats">
                        <h2>Statistiques</h2>
                        <ul>
                            <li>
                                <strong>Génération</strong> : <?= $pokemonData->apiGeneration ?>
                            </li>
                            <?php
                            if (isset($pokemonData->stats)) {

                                foreach ($pokemonData->stats as $statName => $statValue) {
                            ?>
                                    <li>
                                        <?= getFormattedStat($statName, $statValue) ?>
                                    </li>
                            <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <div class="evolutions">
                    <h2>Évolutions :</h2>
                    <ul>
                        <?php
                        $evolutions = getEvolutions($pokemonData->id);
                        $preEvolution = getPreEvolution($pokemonData);
                        if ($preEvolution) {
                        ?>
                            <li class="arrow">
                                <a href="pokemon.php?id=<?= $preEvolution->id ?>">
                                    <img src="<?= $preEvolution->sprite ?>" alt="<?= $preEvolution->name ?>">
                                    <p>No. <?= getFormattedId($preEvolution->id) . ' - ' . $preEvolution->name ?></p>
                                </a>
                            </li>
                        <?php
                        }
                        ?>
                        <li class="<?php if ($evolutions[0]) echo "arrow" ?>">
                            <div>
                                <img src="<?= $pokemonData->sprite ?>" alt="<?= $pokemonData->name ?>">
                                <p>No. <?= getFormattedId($pokemonData->id) . ' - ' . $pokemonData->name ?></p>
                            </div>
                        </li>
                        <?php
                        if ($evolutions) {
                            foreach ($evolutions as $evolution) {
                        ?>
                                <li>
                                    <a href="pokemon.php?id=<?= $evolution->id ?>">
                                        <img src="<?= $evolution->sprite ?>" alt="<?= $evolution->name ?>">
                                        <p>No. <?= getFormattedId($evolution->id) . ' - ' . $evolution->name ?></p>
                                    </a>
                                </li>
                        <?php
                            }
                        }
                        ?>
                        </li>
                    </ul>
                </div>
            <?php
            } else {
            ?>
                <h1>Aucun pokémon trouvé</h1>
            <?php
            }
            ?>
        <?php
        } else {
        ?>
            <h1>Numéro du pokémon invalide</h1>
        <?php
        }
        $conn->close();
        ?>
    </main>
</body>

</html>