<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pokemonId = isset($_POST['id']) ? $_POST['id'] : null;

$aze = "DELETE FROM pokemons WHERE id = $pokemonId";

$conn->query($aze);

echo "<script>window.location.href = 'index.php'</script>";;
