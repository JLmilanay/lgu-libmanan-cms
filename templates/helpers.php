<?php
// helpers.php - Common helper functions

if (!function_exists('getYoutubeId')) {
    function getYoutubeId($url) {
        $youtube_id = "";
        if (strpos($url, 'youtu.be') !== false) {
            $parts = parse_url($url);
            $youtube_id = ltrim($parts['path'], '/');
        } elseif (strpos($url, 'youtube.com') !== false) {
            $parts = parse_url($url);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                if (isset($query['v'])) {
                    $youtube_id = $query['v'];
                }
            }
        }
        return $youtube_id;
    }
}
?>