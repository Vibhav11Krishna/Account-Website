<?php
// chat_proxy.php
header('Content-Type: application/json');

// 1. Load Groq API key from .env
$env = parse_ini_file(__DIR__ . '/.env');
$apiKey = $env['GROQ_API_KEY'] ?? '';

$input = json_decode(file_get_contents('php://input'), true);
$userMsg = $input['message'] ?? 'Hi';

// 2. GROQ ENDPOINT
$url = "https://api.groq.com/openai/v1/chat/completions";
$payload = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        [
            "role" => "system", 
            "content" => "ROLE: You are the Senior Assistant for Karunesh Kumar & Associates, a professional accounting firm.
            
            FIRM PROFILE:
            - About: We are a premier firm specializing in taxation, audit, and financial consultancy. Our mission is to simplify financial compliance for businesses and individuals.
            - Founder: Karunesh Kumar.
            - Address: Patna (Bihar) 2nd Floor, Shyam Market, Pillar No: 75, Bailey Road, Patna - 800014.
            - Timing: Monday to Saturday, 10:00 AM to 6:30 PM. (Closed on Sundays).
            
            CORE SERVICES:
            1. Incoporation: Quick & Compliant registration for the companies.
            2. Accounting: accurate accounting and financial reporting to strengthen the business.
            3. Auditing: professional audit services to ensure comliance, transparency and financial integrity.
            4. Taxation: Expert GST & income tax services.
            5. Compliances: complete statuaory and regulate compliance managements.
            6. Startups: end to end advisory & compilance support to help startups
            7. Consulting: we provide advisory & consulting services to support informed decisions and sustainable growth. 

            STRICT RULES:
            - If a user asks for 'Karunesh Sir', inform them he is available for appointments. Ask for their name and phone number to schedule a call.
            - If you don't know an answer regarding a specific law, say: 'That is a technical query. Please share your documents at our office or call us so we can provide a precise answer.'"
        ],
        ["role" => "user", "content" => $userMsg]
    ],
    "temperature" => 0.5
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    echo json_encode([
        "error" => true,
        "details" => $err['error']['message'] ?? "Groq API Error $httpCode"
    ]);
} else {
    echo $response;
}
?>
