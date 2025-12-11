<?php
session_start();

header('Content-Type: application/json');

try {
    // Clear the recently viewed session
    if (isset($_SESSION['recently_viewed'])) {
        unset($_SESSION['recently_viewed']);
        echo json_encode([
            'success' => true,
            'message' => 'Viewing history cleared successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No viewing history to clear'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error clearing viewing history: ' . $e->getMessage()
    ]);
}
