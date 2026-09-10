<?php

function shopImageUrl(?string $imageUrl, ?string $itemName = null): string
{
    $root = __DIR__ . '/uploads/shop/';

    if (is_string($imageUrl) && $imageUrl !== '') {
        $imageUrl = trim($imageUrl);
        if (preg_match('#^(https?:)?//#i', $imageUrl) || str_starts_with($imageUrl, '/')) {
            return $imageUrl;
        }
        return '/uploads/shop/' . $imageUrl;
    }

    if (is_string($itemName) && $itemName !== '') {
        if (is_file($root . $itemName)) {
            return '/uploads/shop/' . $itemName;
        }

        $aliases = [
            "Satoru's unlimited void.gif" => "Satiru's unlimited void.gif",
        ];
        if (isset($aliases[$itemName]) && is_file($root . $aliases[$itemName])) {
            return '/uploads/shop/' . $aliases[$itemName];
        }
    }

    return '';
}
