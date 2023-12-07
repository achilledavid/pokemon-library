<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$records_per_page = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$sql = "SELECT * FROM pokemons LIMIT $offset, $records_per_page";
$result = $conn->query($sql);
$pokemons = $result->fetch_all(MYSQLI_ASSOC);

include './components/header.php';
?>
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
    <main class="container">
        <?php
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
        <?php if ($pokemons) { ?>
            <h2>Pokemons enregistrés :</h2>
            <div class="pokemon-list">
                <?php foreach ($pokemons as $pokemon) {
                    $pokemonData = (object) $pokemon;
                    $pokemonData->id = getFormattedId($pokemonData->id);
                    include './components/pokemon-card.php';
                } ?>
            </div>
        <?php } ?>
        <div class="pagination">
            <?php
            $total_pages_sql = "SELECT COUNT(*) FROM pokemons";
            $result = $conn->query($total_pages_sql);
            $total_rows = $result->fetch_row()[0];
            $total_pages = ceil($total_rows / $records_per_page);

            if ($page > 1) {
                echo "<a href='index.php?page=" . ($page - 1) . "'>Précédent</a> ";
            }

            for ($i = 1; $i <= $total_pages; $i++) {
                echo "<a href='index.php?page=$i'>$i</a> ";
            }

            if ($page < $total_pages) {
                echo "<a href='index.php?page=" . ($page + 1) . "'>Suivant</a> ";
            }
            ?>
        </div>
    </main>
</body>

</html>