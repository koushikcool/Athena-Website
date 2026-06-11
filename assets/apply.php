<?php
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'Forbidden.';
    exit;
}

function clean($v) { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }

// Sanitize text fields
$position   = clean($_POST['position']   ?? '');
$name       = clean($_POST['name']       ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone      = clean($_POST['phone']      ?? '');
$experience = clean($_POST['experience'] ?? '');
$linkedin   = clean($_POST['linkedin']   ?? '');
$message    = clean($_POST['message']    ?? '');

// Validate required fields
if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone) {
    http_response_code(400);
    echo 'Please fill in all required fields.';
    exit;
}

// Validate resume upload
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo 'Please upload your resume/CV.';
    exit;
}

$file     = $_FILES['resume'];
$allowed  = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowed, true)) {
    http_response_code(400);
    echo 'Only PDF, DOC, or DOCX files are accepted.';
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo 'File must be under 5 MB.';
    exit;
}

// Build email
$to       = 'info@implifytechnologies.com';
$subject  = "New Job Application — {$position} — {$name}";
$boundary = '----=_Part_' . md5(uniqid('', true));

// Plain-text body
$textBody  = "New application submitted via the Implify Careers page.\n";
$textBody .= str_repeat('-', 50) . "\n\n";
$textBody .= "Position:   " . ($position   ?: '—') . "\n";
$textBody .= "Name:       {$name}\n";
$textBody .= "Email:      {$email}\n";
$textBody .= "Phone:      {$phone}\n";
$textBody .= "Experience: " . ($experience ? "{$experience} year(s)" : '—') . "\n";
$textBody .= "LinkedIn:   " . ($linkedin   ?: '—') . "\n\n";
$textBody .= "Cover Note:\n" . ($message ?: '—') . "\n";

// Attachment
$fileName    = basename($file['name']);
$fileContent = chunk_split(base64_encode(file_get_contents($file['tmp_name'])));

// Build MIME message
$mime  = "--{$boundary}\r\n";
$mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
$mime .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$mime .= $textBody . "\r\n";

$mime .= "--{$boundary}\r\n";
$mime .= "Content-Type: {$fileType}; name=\"{$fileName}\"\r\n";
$mime .= "Content-Transfer-Encoding: base64\r\n";
$mime .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
$mime .= $fileContent . "\r\n";

$mime .= "--{$boundary}--";

// Headers
$headers  = "From: Implify Careers <noreply@implifytechnologies.com>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

if (mail($to, $subject, $mime, $headers)) {
    echo "Thank you, {$name}! Your application has been received. We review all applications carefully and will be in touch if your profile is a match.";
} else {
    http_response_code(500);
    echo 'Something went wrong. Please email your CV directly to info@implifytechnologies.com.';
}
