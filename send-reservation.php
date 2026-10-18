<?php
// STAR BIRYANI reservation email handler for cPanel hosting.
// Replace this with the restaurant's actual receiving email address before publishing.
$recipient = 'contact@starbiryani.us';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Please submit the reservation form.']);
}

$firstName = trim((string)($_POST['firstName'] ?? ''));
$lastName = trim((string)($_POST['lastName'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$date = trim((string)($_POST['date'] ?? ''));
$type = trim((string)($_POST['reservationType'] ?? 'table'));
$time = trim((string)($_POST['time'] ?? ''));
$guests = trim((string)($_POST['guests'] ?? ''));
$occasion = trim((string)($_POST['occasion'] ?? 'Not specified'));
$requests = trim((string)($_POST['requests'] ?? 'None'));

if ($firstName === '' || $lastName === '' || $phone === '' || $date === '' || $time === '' || $guests === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['success' => false, 'message' => 'Please complete all required fields with a valid email address.']);
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $date);
if (!$dateObject || $dateObject->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
    respond(422, ['success' => false, 'message' => 'Please choose a valid date that is not in the past.']);
}
if ($type === 'premium-weekend-buffet' && !in_array($dateObject->format('w'), ['0', '6'], true)) {
    respond(422, ['success' => false, 'message' => 'Premium buffet reservations are available Saturdays and Sundays.']);
}

// Basic header-injection protection for values used in email headers.
$email = str_replace(["\r", "\n"], '', $email);
$subject = 'STAR BIRYANI Reservation Request - ' . $date;
$body = "A new STAR BIRYANI reservation request was submitted.\n\n"
    . "Name: {$firstName} {$lastName}\n"
    . "Email: {$email}\n"
    . "Phone: {$phone}\n"
    . "Date: {$date}\n"
    . "Time: {$time}\n"
    . "Guests: {$guests}\n"
    . "Reservation type: {$type}\n"
    . "Occasion: {$occasion}\n"
    . "Special requests: {$requests}\n";

if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    respond(500, ['success' => false, 'message' => 'The reservation form is not fully configured yet. Please contact the restaurant by phone.']);
}

$host = preg_replace('/[^a-zA-Z0-9.-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: STAR BIRYANI Website <reservations@' . ($host !== '' ? $host : 'localhost') . '>',
    'Reply-To: ' . $email,
];

// Uses PHP mail() supplied by the hosting server; SMTP credentials are not required here.
// cPanel/hosting must still permit outbound mail and have mail delivery configured.
$sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
if (!$sent) {
    respond(502, ['success' => false, 'message' => 'The server could not send your reservation email. Please call the restaurant.']);
}

respond(200, ['success' => true, 'message' => 'Reservation request sent.']);
