<?php
function parseStoreFilters(array $input): array {
    $categories = $input['category'] ?? [];
    $prices     = $input['price'] ?? [];
    $conditions = $input['condition'] ?? [];
    $featured   = $input['featured'] ?? [];

    if (!is_array($categories)) $categories = [$categories];
    if (!is_array($prices))     $prices     = [$prices];
    if (!is_array($conditions)) $conditions = [$conditions];
    if (!is_array($featured))   $featured   = [$featured];

    return [
        'categories' => array_values(array_filter(array_map('trim', $categories))),
        'prices'     => array_values(array_filter(array_map('trim', $prices))),
        'conditions' => array_values(array_filter(array_map('trim', $conditions))),
        'featured'   => array_values(array_filter(array_map('trim', $featured))),
    ];
}
