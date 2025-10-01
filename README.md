# ipStore
IP-based data storage and security tracking library  
`v0.2`, `2025-08-07`  
by @aaviator42  
License: `AGPLv3`

## Overview

ipStore is a lightweight PHP library for storing and retrieving data on a per-IP address basis. It provides secure, date-organized storage for tracking user interactions, implementing rate limiting, and maintaining IP-based security measures.

## Features

- **Per-IP data storage** - Each IP address gets its own data file
- **Date-based organization** - Data is organized by date for easy cleanup
- **IPv4 and IPv6 support** - Handles both IP address formats safely
- **Automatic cleanup** - Removes old data to prevent storage bloat
- **Flexible IP source** - Configurable to work with proxies and CDNs
- **StorX integration** - Uses SQLite via [StorX](https://github.com/aaviator42/StorX) for reliable data storage
- **Thread-safe operations** - Concurrent access protection

## Usage

The easiest way to understand what this library does is to see it in action:

```php

// ipStore example

<?php

// include the StorX library first
require 'StorX.php';
// then include the ipStore library  
require 'ipStore.php';

// ipStore automatically uses StorX for data storage

// track failed login attempts for current IP
$failCount = 0;
if(ipStore\checkIpKey('loginFailCount')){ // check if key exists in db file for current IP
    $failCount = ipStore\readIpKey('loginFailCount'); // read key value from db file for current IP
}

// increment fail count
$failCount++;
ipStore\writeIpKey('loginFailCount', $failCount); // write key to db file for current IP

// check if IP should be blocked
if($failCount > 5){
    ipStore\writeIpKey('blocked', true);
    die('Too many failed attempts');
}

// track user preferences by IP
ipStore\writeIpKey('theme', 'dark');
ipStore\writeIpKey('language', 'en');

// read back preferences
$userTheme = ipStore\readIpKey('theme'); //returns 'dark'
$userLang = ipStore\readIpKey('language'); //returns 'en'

echo "User theme: $userTheme, Language: $userLang";

// store complex data like arrays
$userData = [
    'visits' => 10,
    'lastSeen' => time(),
    'preferences' => ['notifications' => true]
];
ipStore\writeIpKey('userData', $userData);

```

## Installation

1. Ensure [StorX](https://github.com/aaviator42/StorX) library is available:  
```php
require_once 'lib/StorX.php';
```

2. Include the ipStore library in your project:  
```php
require_once 'lib/ipStore.php';
```

## Configuration

### Storage Location
Override the storage directory before including the library:
```php
define('IPSTORE_LOCATION', '/custom/path/to/ip/storage');
require_once 'lib/ipStore.php';
```

### Automatic Cleanup Configuration
Control whether ipStore automatically cleans up old data:
```php
// Disable automatic cleanup (data stored permanently in /undated/ directory)
define('IPSTORE_AUTO_CLEANUP', false);

// Enable automatic cleanup (default - data stored in dated directories with 24-hour retention)
define('IPSTORE_AUTO_CLEANUP', true);

require_once 'lib/ipStore.php';
```

### IP Source Configuration
Configure IP address source for different server setups:

```php
// For CloudFlare
define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// For reverse proxy/load balancer
define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// For real IP header
define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// For AWS ALB/ELB
define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// Multiple proxy chain (first IP)
define('IPSTORE_IP_SOURCE', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);

require_once 'lib/ipStore.php';
```

## Data Retention Policy

ipStore supports two data retention modes controlled by the `IPSTORE_AUTO_CLEANUP` constant:

### Automatic Cleanup Mode (Default - `IPSTORE_AUTO_CLEANUP = true`)
- **Retention period:** Current day only (24-hour maximum retention)
- **Cleanup frequency:** Automatic cleanup runs on each writeIpKey() call
- **Storage location:** Date-organized directories (`/2025-08-07/`, `/2025-08-06/`, etc.)
- **Storage scope:** All IP-based data (counters, preferences, security flags)
- **Purpose:** Prevents indefinite data accumulation and supports privacy compliance

### Permanent Storage Mode (`IPSTORE_AUTO_CLEANUP = false`)
- **Retention period:** Indefinite (data persists until manually deleted)
- **Cleanup frequency:** No automatic cleanup
- **Storage location:** Single `/undated/` directory
- **Storage scope:** All IP-based data persists permanently
- **Purpose:** Long-term analytics, persistent user preferences, permanent security tracking
- **Manual cleanup:** Use `deleteFullIpStore()` to remove all undated data when needed

## Directory Structure

### With Automatic Cleanup (Default - `IPSTORE_AUTO_CLEANUP = true`)
```
{IPSTORE_LOCATION}/
├── 2025-08-07/     [current day]
│   ├── 192.168.1.1.db
│   ├── 10.0.0.5.db
│   └── ipv6-2001-db8-85a3-0-0-8a2e-370-7334.db
├── 2025-08-06/
│   └── [previous day's IP files - auto-deleted]
└── 2025-08-05/
    └── [older IP files - auto-deleted]
```

### With Permanent Storage (`IPSTORE_AUTO_CLEANUP = false`)
```
{IPSTORE_LOCATION}/
└── undated/
    ├── 192.168.1.1.db
    ├── 10.0.0.5.db
    ├── ipv6-2001-db8-85a3-0-0-8a2e-370-7334.db
    └── [all IP files persist indefinitely]
```

## API Reference

### Core Functions

#### writeIpKey($key, $value)
Store a key-value pair for the current IP address.

```php
use ipStore;

// Store user preference
ipStore\writeIpKey('theme', 'dark');

// Track failed attempts
ipStore\writeIpKey('loginFailCount', 3);

// Store complex data
ipStore\writeIpKey('userPrefs', ['lang' => 'en', 'timezone' => 'UTC']);
```

#### readIpKey($key)
Retrieve a value for the current IP address.

```php
// Read stored value
$theme = ipStore\readIpKey('theme');

// Handle missing keys
$failCount = ipStore\readIpKey('loginFailCount');
if ($failCount === 'IPSTORE_KEY_NOT_FOUND') {
    $failCount = 0;
}

// Handle missing files
$value = ipStore\readIpKey('someKey');
if ($value === 'IPSTORE_FILE_NOT_FOUND') {
    // IP has no stored data yet
}
```

#### checkIpKey($key)
Check if a key exists for the current IP address.

```php
if (ipStore\checkIpKey('isBlacklisted')) {
    // Key exists, check value
    $isBlocked = ipStore\readIpKey('isBlacklisted');
} else {
    // Key doesn't exist
    $isBlocked = false;
}
```

#### checkIpFile()
Check if the current IP has any stored data.

```php
if (ipStore\checkIpFile()) {
    echo "IP has existing data";
} else {
    echo "New IP address";
}
```

#### deleteIpFile()
Delete all data for the current IP address.

```php
// Remove all IP data (useful for unblocking)
if (ipStore\deleteIpFile()) {
    echo "IP data cleared successfully";
}
```

### Administrative Functions

#### deleteOldIpStore()
Remove IP data from previous days (keeps current day only).

```php
// Clean up old data (called automatically by writeIpKey)
ipStore\deleteOldIpStore();
```

#### deleteFullIpStore()
Remove all IP data from all directories (dated and undated).

```php
// Remove all IP data regardless of cleanup mode
ipStore\deleteFullIpStore();

// Useful for:
// - Clearing all data when IPSTORE_AUTO_CLEANUP = false
// - Complete system reset
// - Privacy compliance (complete data removal)
```

## Common Usage Patterns

### Rate Limiting
```php
use ipStore;

function trackFailedLogin() {
    $failCount = 0;
    if (ipStore\checkIpKey('loginFailCount')) {
        $failCount = ipStore\readIpKey('loginFailCount');
    }
    
    $failCount++;
    if ($failCount > 5) {
        // Block IP
        ipStore\writeIpKey('blocked', true);
        http_response_code(403);
        die('Too many failed attempts');
    }
    
    ipStore\writeIpKey('loginFailCount', $failCount);
}

function resetFailedLogins() {
    ipStore\writeIpKey('loginFailCount', 0);
}
```

### User Preferences
```php
function saveUserPreference($key, $value) {
    $prefs = [];
    if (ipStore\checkIpKey('preferences')) {
        $prefs = ipStore\readIpKey('preferences');
    }
    
    $prefs[$key] = $value;
    ipStore\writeIpKey('preferences', $prefs);
}

function getUserPreference($key, $default = null) {
    if (!ipStore\checkIpKey('preferences')) {
        return $default;
    }
    
    $prefs = ipStore\readIpKey('preferences');
    return $prefs[$key] ?? $default;
}
```

### Security Tracking
```php
function trackSuspiciousActivity($activity) {
    $activities = [];
    if (ipStore\checkIpKey('suspicious_activities')) {
        $activities = ipStore\readIpKey('suspicious_activities');
    }
    
    $activities[] = [
        'activity' => $activity,
        'timestamp' => time(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    // Keep only last 10 activities
    $activities = array_slice($activities, -10);
    ipStore\writeIpKey('suspicious_activities', $activities);
    
    // Auto-block after 3 suspicious activities
    if (count($activities) >= 3) {
        ipStore\writeIpKey('auto_blocked', true);
    }
}
```

### Permanent User Preferences (Disable Auto-Cleanup)
```php
// Configure for permanent storage
define('IPSTORE_AUTO_CLEANUP', false);
require_once 'lib/ipStore.php';

function savePermanentPreference($key, $value) {
    // Data persists indefinitely in /undated/ directory
    ipStore\writeIpKey($key, $value);
}

function getUserAnalytics($ip = null) {
    // Access long-term data for analytics
    $analytics = [
        'total_visits' => ipStore\readIpKey('visit_count'),
        'first_visit' => ipStore\readIpKey('first_visit_date'),
        'preferences' => ipStore\readIpKey('user_preferences'),
        'behavior_score' => ipStore\readIpKey('behavior_score')
    ];
    
    return $analytics;
}

// Periodic cleanup when needed
function performMaintenanceCleanup() {
    // Only when necessary - removes ALL permanent data
    ipStore\deleteFullIpStore();
    error_log("ipStore: All permanent data cleared for maintenance");
}
```

## Error Handling

ipStore uses specific return values to indicate errors:

```php
$result = ipStore\readIpKey('someKey');

switch ($result) {
    case 'IPSTORE_FILE_NOT_FOUND':
        // IP has no data file yet
        break;
        
    case 'IPSTORE_KEY_NOT_FOUND':
        // IP file exists but key doesn't
        break;
        
    default:
        // Valid data returned
        $value = $result;
}
```

## Security Considerations

### IP Spoofing Prevention
- Validate IP addresses before using as identifiers
- Use HTTPS-only in production to prevent header manipulation
- Consider additional authentication for sensitive operations

### Data Privacy
- IP data is automatically cleaned up daily
- Consider implementing data retention policies
- Ensure compliance with privacy regulations (GDPR, etc.)

### Storage Security
- Ensure proper file permissions on storage directory
- Consider encrypting sensitive data before storage
- Regular backups of critical IP-based data

## Performance Notes

### File System Impact
- Each IP creates a separate StorX SQLite file
- Daily cleanup prevents excessive file accumulation
- Consider disk space monitoring for high-traffic sites

### Concurrency
- StorX provides thread-safe operations
- Multiple requests from same IP are handled safely
- Lock contention is minimal due to per-IP file isolation

### Optimization Tips
```php
// Check if file exists before reading multiple keys
if (ipStore\checkIpFile()) {
    $value1 = ipStore\readIpKey('key1');
    $value2 = ipStore\readIpKey('key2');
    $value3 = ipStore\readIpKey('key3');
}

// Batch operations when possible
$allData = [
    'failCount' => $failCount,
    'lastAttempt' => time(),
    'userAgent' => $_SERVER['HTTP_USER_AGENT']
];
ipStore\writeIpKey('loginData', $allData);
```

## Integration with Other Libraries

### With rateLimiter
```php
// ipStore provides the storage backend for rateLimiter
require_once 'lib/ipStore.php';
require_once 'lib/rateLimiter.php';

// rateLimiter uses ipStore automatically
rateLimiter\trackCaptchaFailure();
```

### With Sesher
```php
// Track session data per IP
if (\Sesher\check()) {
    ipStore\writeIpKey('lastValidSession', time());
}
```

## Troubleshooting

**"IP address not available" Exception**
- Check server configuration for IP headers
- Verify proxy/CDN header forwarding
- Configure IPSTORE_IP_SOURCE constant properly

**Permission Errors**
```bash
# Ensure storage directory is writable
chmod 755 /path/to/ipstore/location
chown www-data:www-data /path/to/ipstore/location
```

### Debug Information
```php
// Check current IP source
echo "Current IP: " . IPSTORE_IP_SOURCE . "\n";

// Check storage location
echo "Storage: " . IPSTORE_LOCATION . "\n";

// List IP files for today
$today = date('Y-m-d');
$files = glob(IPSTORE_LOCATION . "/$today/*.db");
foreach ($files as $file) {
    echo basename($file, '.db') . "\n";
}
```

## License

AGPLv3 - See license file for details

## Dependencies

- PHP 8.0+
- StorX library v5.0+
- SQLite3 extension
- Standard PHP extensions (filter, preg)

## Author

@aaviator42

----


Documentation updated: `2025-09-30`



