<script type="application/ld+json">
    {!! json_encode([
        "@context" => "https://schema.org",
        "@type" => "Game",
        "name" => $game->name,
        "applicationCategory" => "OnlineGame",
        "image" => asset('storage/games/'.$game->slug.'.webp'),
        "author" => [
            "@type" => "Organization",
            "name" => "La168"
        ],
        "description" => $game->description,
    ]) !!}
</script>