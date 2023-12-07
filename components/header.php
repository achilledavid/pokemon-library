<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT apiGeneration FROM pokemons GROUP BY apiGeneration";

$sqlTypes = "SELECT * FROM types";
?>

<header>
    <div class="top">
        <a href="index.php">Pokédex</a>
        <ul class="menu">
            <li>
                <a href="index.php">Tout</a>
            </li>
            <li id="generations">
                <span>Générations</span>
            </li>
            <li id="types">
                <span>Types</span>
            </li>
        </ul>
        <ul class="sub" id="generations-list">
            <?php
            $result = $conn->query($sql);
            $generations = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($generations as $generation) {
                $generationData = (object) $generation;
            ?>
                <li><a href="generation.php?id=<?= $generationData->apiGeneration; ?>">Génération <?= $generationData->apiGeneration; ?></a></li>
            <?php
            }
            ?>
        </ul>
        <ul class="sub" id="types-list">
            <?php
            $resultTypes = $conn->query($sqlTypes);
            $types = $resultTypes->fetch_all(MYSQLI_ASSOC);
            foreach ($types as $type) {
                $typeData = (object) $type;
            ?>
                <li>
                    <a href="type.php?type=<?= $typeData->name; ?>">
                        <img src="<?= $typeData->image; ?>" alt="<?= $typeData->name ?>">
                        <?= $typeData->name; ?>
                    </a>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>
    <form id="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="text" name="search" placeholder="Rechercher..." autocomplete="off">
        <button type="submit">Rechercher</button>
    </form>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $searchValue = $_POST["search"];
        search($searchValue);
    }

    function search($searchValue)
    {
        $url = "https://pokebuildapi.fr/api/v1/pokemon/" . $searchValue;
        $headers = get_headers($url);
        if (strpos($headers[0], '200') !== false) {
            $response = file_get_contents($url);
            $pokemonData = json_decode($response);
            $pokemonId = $pokemonData->id ?? null;
            if ($pokemonId) {
                echo "<script>window.location.href = 'pokemon.php?id=" . $pokemonId . "'</script>";
            } else {
                echo "<script>window.location.href = '404.php'</script>";
            }
        } else {
            echo "<script>window.location.href = '404.php'</script>";
        }
    }
    ?>
</header>

<script>
    const generations = document.getElementById('generations');
    const ul = document.getElementById('generations-list');
    const types = document.getElementById('types');
    const ulTypes = document.getElementById('types-list');

    generations.addEventListener('click', () => {
        if (ulTypes.classList.contains('active')) ulTypes.classList.remove('active');
        ul.classList.toggle('active');
    })

    types.addEventListener('click', () => {
        if (ul.classList.contains('active')) ul.classList.remove('active');
        ulTypes.classList.toggle('active');
    })
</script>