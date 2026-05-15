<?php
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'Forbidden.';
    exit;
}

function clean($v) { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }

$name    = clean($_POST['name']    ?? '');
$company = clean($_POST['company'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = clean($_POST['phone']   ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message) {
    http_response_code(400);
    echo 'Please fill in all required fields.';
    exit;
}

$to      = 'info@implifytechnologies.com';
$subject = "New Contact Form Enquiry from {$name}";

$body  = "Name: {$name}\r\n";
$body .= $company ? "Company: {$company}\r\n" : '';
$body .= "Email: {$email}\r\n";
$body .= $phone   ? "Phone: {$phone}\r\n"   : '';
$body .= $service ? "Service: {$service}\r\n" : '';
$body .= "\nMessage:\n{$message}\r\n";

$headers  = "From: Implify Contact Form <noreply@implifytechnologies.com>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers)) {
    echo "Thank you, {$name}! Your message has been sent. We'll be in touch within one business day.";
} else {
    http_response_code(500);
    echo 'Something went wrong. Please email us directly at info@implifytechnologies.com.';
}
