<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pokedex</title>
    <link rel="stylesheet" href="./css/reset.css">
    <link rel="stylesheet" href="./css/style.css">
</head>


<body>
    <?php
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "pokemon";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM pokemons";
    $result = $conn->query($sql);
    $pokemons = $result->fetch_all(MYSQLI_ASSOC);

    include './components/header.php';
    ?>
    <main class="container">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
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
            $response = file_get_contents($url);
            $pokemonData = json_decode($response);
            $pokemonId = $pokemonData->id;
            if ($pokemonId) {
                header("Location: ./pokemon.php?id=$pokemonId");
            } else {
                echo "<div class='search-result'><p>Aucun résultat pour $searchValue</p></div>";
            }
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
        ?>
        <?php
        if ($pokemons) {
        ?>
            <h2>Pokemons enregistrés :</h2>
            <div class="pokemon-list">
                <?php
                foreach ($pokemons as $pokemon) {
                    $pokemonData = (object) $pokemon;
                    $pokemonData->id = getFormattedId($pokemonData->id);
                    include './components/pokemon-card.php';
                }
                ?>
            </div>
        <?php } ?>
        </div>
    </main>
</body>

</html>