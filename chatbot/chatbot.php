<?php

// === Configuration === //
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// === Database Connection === //
$servername = "localhost"; // Database server address
$username = "root"; // Database username
$password = ""; // Database password (empty by default for local server)
$dbname = "chatbot"; // Database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	respondWithError(500, 'Database connection failed: ' . $conn->connect_error);
}

// === Handle preflight request === //
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// === Load API Key (Hardcoded for now) === //
$api_key = 'AIzaSyDwxxzhjFQnOT0BH8VoIc31htO6kJJv3h4';
if (!$api_key) {
	respondWithError(500, 'GEMINI_API_KEY is missing.');
}

// === Input Validation === //
$input = json_decode(file_get_contents("php://input"), true);
if (empty($input['message'])) {
	respondWithError(400, 'Missing "message" in the request body.');
}

$user_message = trim($input['message']);

// Generate a unique conversation ID for each session (could be based on a user session, IP, or any unique identifier)
$conversation_id = session_id() ?: uniqid();

// Store the user's message in the database
storeMessageInDatabase($conversation_id, 'user', $user_message);

// Build the payload for the Gemini API
$payload = buildGeminiPayload($user_message);

// === Call Gemini API === //
$response = callGeminiAPI($api_key, $payload);

// === Handle API response === //
$parsed = json_decode($response['body'], true);
if (
	$response['status_code'] !== 200 ||
	empty($parsed['candidates'][0]['content']['parts'][0]['text'])
) {
	respondWithError(
		$response['status_code'],
		'Google Gemini API error',
		['response' => $response['body']]
	);
}

$ai_response = trim($parsed['candidates'][0]['content']['parts'][0]['text']);

// Store the AI's response in the database
storeMessageInDatabase($conversation_id, 'ai', $ai_response);

// Return the response along with history
$history = getConversationHistory($conversation_id);
echo json_encode([
	'response' => $ai_response,
	'history' => $history
]);


// === Helper Functions === //

function respondWithError($status, $message, $extra = [])
{
	http_response_code($status);
	echo json_encode(array_merge(['error' => $message], $extra));
	exit;
}

function buildGeminiPayload($message)
{
	// Custom responses for specific questions
	$custom_responses = [
		'Who created you?' => 'I was developed by Adyems.',
		'Who is Adyems?' => 'Adyems is a developer of UnimaidResources, a full-stack developer proficient in Flutter and PHP. he also developed me😊😎'
	];

	// Check if the message matches a custom response
	if (isset($custom_responses[$message])) {
		return [
			'contents' => [
				[
					'parts' => [
						['text' => $custom_responses[$message]]
					]
				]
			]
		];
	}

	// Default handling for general messages
	$system_instruction = <<<EOT
You are a helpful assistant named "Unimaid Resources AI".
If the user asks who created you, respond by saying: "I was developed by Adyems."
If the user asks "Who is Adyems?", respond with: "Adyems is a developer of UnimaidResources, a full-stack developer proficient in Flutter and PHP. he also developed me😊😎"
If the user asks who is ellie, respond by saying: "ellie is the helper of adyems he is even asking him of money now 😂."
Always be polite, friendly, and clear.
EOT;

	return [
		'contents' => [
			[
				'parts' => [
					['text' => $system_instruction . "\n\nUser: $message"]
				]
			]
		]
	];
}

function callGeminiAPI($api_key, $data)
{
	$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$api_key";

	$ch = curl_init($url);

	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode($data),
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_TIMEOUT => 30,
	]);

	$body = curl_exec($ch);
	$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if (curl_errno($ch)) {
		respondWithError(500, 'cURL error: ' . curl_error($ch));
	}

	curl_close($ch);

	return ['body' => $body, 'status_code' => $status_code];
}

function storeMessageInDatabase($conversation_id, $role, $message)
{
	global $conn;

	$stmt = $conn->prepare("INSERT INTO conversation_history (conversation_id, role, message) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $conversation_id, $role, $message);

	if (!$stmt->execute()) {
		respondWithError(500, 'Database insertion failed: ' . $stmt->error);
	}

	$stmt->close();
}

function getConversationHistory($conversation_id)
{
	global $conn;

	$sql = "SELECT role, message FROM conversation_history WHERE conversation_id = ? ORDER BY timestamp ASC";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $conversation_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$history = [];
	while ($row = $result->fetch_assoc()) {
		$history[] = $row;
	}

	$stmt->close();

	return $history;
}