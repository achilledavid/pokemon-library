<a href="pokemon.php?id=<?= $pokemonData->id; ?>" class="card">
    <h2>n°<?= $pokemonData->id . ' - ' . $pokemonData->name; ?></h2>
    <img src="<?= $pokemonData->image; ?>" alt="<?= $pokemonData->name; ?>">
</a>