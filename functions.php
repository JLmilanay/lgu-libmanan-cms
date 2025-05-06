<?php
// functions.php

function format_date($date_string, $full = false) {
    $date = new DateTime($date_string);
    if ($full) {
        return $date->format('M j, Y g:i A');
    }
    return $date->format('M j, Y');
}

function truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Add other utility functions as needed