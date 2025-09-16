<?php
// chatbot_api.php
header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get the raw JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validate input
if (!isset($input['message']) || !is_string($input['message']) || trim($input['message']) === '') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid or empty message']);
    exit;
}

$message = trim($input['message']);
$responseText = '';

// Basic chatbot logic
switch (strtolower($message)) {
    case 'hello':
    case 'hi':
        $responseText = "Hello! How can I assist you today?";
        break;

    case 'help':
        $responseText = "You can ask me about employees, leave types, or any admin questions.";
        break;

    case 'who are you?':
        $responseText = "I am your Admin Assistant chatbot.";
        break;

    default:
        $responseText = "Sorry, I don't understand '{$message}'. Please try asking something else.";
        break;
}

// Return JSON response with the 'answer' key
echo json_encode(['answer' => $responseText]);
