<?php
/**
 * helpers.php
 *
 * This file contains various helper functions used across the Inventory System.
 * Functions here are generally utility functions that don't belong to a specific class
 * or handle core business logic, but rather provide common functionalities like
 * date formatting, input sanitization, etc.
 */

/**
 * format_last_activity
 *
 * Formats a given timestamp into a human-readable string, indicating
 * "Today", "Yesterday", "X days ago", or a specific date.
 *
 * @param string $timestamp The timestamp string to format.
 * @return string The formatted date string.
 */
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A'; // Return 'N/A' if timestamp is empty

    $date = new DateTime($timestamp); // Create DateTime object from timestamp
    $now = new DateTime(); // Create DateTime object for current time
    $interval = $now->diff($date); // Calculate the difference between now and the timestamp

    // Check if the activity was today
    if ($interval->d == 0 && $interval->h < 24) {
        return "Today, " . $date->format('g:i A'); // Format as "Today, 10:30 AM"
    } elseif ($interval->d == 1) { // Check if the activity was yesterday
        return "Yesterday, " . $date->format('g:i A'); // Format as "Yesterday, 10:30 AM"
    } elseif ($interval->days < 7) { // Check if the activity was within a week
        return $interval->days . " days ago"; // Format as "X days ago"
    } else {
        return $date->format('M j, Y'); // Format as "Jan 1, 2023" for older dates
    }
}

// Add other helper functions here as needed
?>
