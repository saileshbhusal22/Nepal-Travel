<?php
/**
 * Email Validation and Fake Email Detection
 * Blocks disposable, temporary, and suspicious email domains
 */

// List of known disposable/temporary email providers
// Update this regularly from https://github.com/disposable-email-domains/disposable-email-domains
$DISPOSABLE_DOMAINS = [
    // Temporary email services
    '10minutemail.com',
    '10minutemail.de',
    'guerrillamail.com',
    'mailinator.com',
    'temp-mail.org',
    'tempmail.com',
    'throwaway.email',
    'yopmail.com',
    'maildrop.cc',
    'sharklasers.com',
    'tempmail.dev',
    'trashmail.com',
    'fakeinbox.com',
    'spam4.me',
    'catch-all.com',
    'mailnesia.com',
    'temp-mail.io',
    'tempail.com',
    'emailondeck.com',
    'guerrillamail.info',
    'grr.la',
    'pokemail.net',
    'spam.la',
    '0-mail.com',
    'temp-mail.ru',
    'mytrashmail.com',
    '9mail.org',
    'e4ward.com',
    'fakeemail.net',
    'mintemail.com',
    'nada.email',
    'randomail.net',
    'spambox.us',
    'tempail.com',
    'trbvm.com',
    'maildisposable.com',
];

/**
 * Validate email format using RFC 5322
 * @param string $email
 * @return bool
 */
function isValidEmailFormat($email) {
    $email = trim(strtolower($email));
    
    // Basic RFC 5322 validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check length
    if (strlen($email) > 254 || strlen($email) < 3) {
        return false;
    }
    
    return true;
}

/**
 * Check if email domain is disposable/temporary
 * @param string $email
 * @param array $disposableDomains
 * @return bool - true if disposable, false if legitimate
 */
function isDisposableEmail($email, $disposableDomains = []) {
    global $DISPOSABLE_DOMAINS;
    
    $domains = !empty($disposableDomains) ? $disposableDomains : $DISPOSABLE_DOMAINS;
    
    $email = trim(strtolower($email));
    $parts = explode('@', $email);
    
    if (count($parts) !== 2) {
        return true; // Invalid format
    }
    
    $domain = $parts[1];
    
    // Check exact domain match
    if (in_array($domain, $domains, true)) {
        return true;
    }
    
    // Check subdomains (e.g., subdomain.guerrillamail.com)
    foreach ($domains as $disposable_domain) {
        if (preg_match('/(?:^|\.)' . preg_quote($disposable_domain, '/') . '$/', $domain)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Validate email DNS records (MX records)
 * @param string $email
 * @return bool - true if MX records exist, false if not
 */
function validateEmailDNS($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }
    
    $domain = $parts[1];
    
    // Check for MX records
    if (function_exists('getmxrr')) {
        $mxrecords = [];
        if (getmxrr($domain, $mxrecords)) {
            return true;
        }
    }
    
    // Fallback: check if A or AAAA records exist
    if (checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA')) {
        return true;
    }
    
    return false;
}

/**
 * Check for suspicious email patterns
 * @param string $email
 * @return array - array of issues found (empty if clean)
 */
function checkSuspiciousPatterns($email) {
    $email = strtolower($email);
    $issues = [];
    
    // Check for multiple + signs (plus addressing abuse)
    if (substr_count($email, '+') > 1) {
        $issues[] = "Multiple plus signs detected";
    }
    
    // Check for excessive dots
    if (preg_match('/\.{2,}/', $email)) {
        $issues[] = "Consecutive dots found";
    }
    
    // Check for too many numbers at the end (common with fake generators)
    if (preg_match('/\d{6,}@/', $email)) {
        $issues[] = "Excessive consecutive numbers";
    }
    
    // Check if domain is all numbers or only numbers and dashes
    $parts = explode('@', $email);
    $domain = $parts[1];
    if (preg_match('/^[\d\-\.]+$/', $domain)) {
        $issues[] = "Domain is numeric only";
    }
    
    // Check for common spam patterns
    $spam_patterns = ['test', 'spam', 'fake', 'invalid', 'noreply', 'donotreply'];
    foreach ($spam_patterns as $pattern) {
        if (stripos($email, $pattern) !== false) {
            $issues[] = "Suspicious keyword: $pattern";
        }
    }
    
    return $issues;
}

/**
 * Comprehensive email validation
 * @param string $email
 * @param array $options - ['check_dns' => true, 'check_disposable' => true, 'allow_plus' => false]
 * @return array ['valid' => bool, 'error' => string|null, 'warnings' => array]
 */
function validateEmail($email, $options = []) {
    $defaults = [
        'check_dns' => true,
        'check_disposable' => true,
        'allow_plus' => false,
        'allow_subdomains' => false,
    ];
    
    $options = array_merge($defaults, $options);
    $email = trim($email);
    
    // 1. Basic format validation
    if (!isValidEmailFormat($email)) {
        return [
            'valid' => false,
            'error' => 'Invalid email format',
            'warnings' => []
        ];
    }
    
    // 2. Check for plus addressing if not allowed
    if (!$options['allow_plus'] && strpos($email, '+') !== false) {
        return [
            'valid' => false,
            'error' => 'Plus addressing is not allowed',
            'warnings' => []
        ];
    }
    
    // 3. Check disposable domains
    if ($options['check_disposable'] && isDisposableEmail($email)) {
        return [
            'valid' => false,
            'error' => 'Temporary/disposable email addresses are not allowed',
            'warnings' => []
        ];
    }
    
    // 4. Check suspicious patterns
    $warnings = checkSuspiciousPatterns($email);
    
    // 5. Validate DNS records (optional, can be slow)
    if ($options['check_dns']) {
        if (!validateEmailDNS($email)) {
            return [
                'valid' => false,
                'error' => 'Email domain does not exist or has no mail server',
                'warnings' => $warnings
            ];
        }
    }
    
    return [
        'valid' => true,
        'error' => null,
        'warnings' => $warnings
    ];
}


?>