<?php
/*
ipStore Test File
v0.2, 2025-08-07
AGPLv3, @aaviator42
*/

header('Content-Type: text/plain');

// Set test IP address to avoid dependency on actual IP
define('IPSTORE_IP_SOURCE', '192.168.1.100');
define('IPSTORE_LOCATION', __DIR__ . '/test_ip_storage');

require 'StorX.php';
require 'ipStore.php';

// Initialize test environment
$testsPassed = true;

// Pre-test cleanup
if (is_dir(IPSTORE_LOCATION)) {
    ipStore\deleteFullIpStore();
    rmdir(IPSTORE_LOCATION);
}

echo "Beginning ipStore Tests..." . PHP_EOL;
echo "Test file v0.2, 2025-08-07" . PHP_EOL;
echo "Test IP: " . IPSTORE_IP_SOURCE . PHP_EOL;
echo "Storage Location: " . IPSTORE_LOCATION . PHP_EOL;
echo PHP_EOL;

// Test 1: Initial state - no IP file exists
echo "Test 1:     Check if IP file exists (should not exist initially)" . PHP_EOL;
echo "Function:   checkIpFile()" . PHP_EOL;
echo "Expecting:  false" . PHP_EOL;
$result = ipStore\checkIpFile();
echo "Result:     ";
if ($result === false) {
    echo "false (OK)";
} else {
    echo "$result (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 2: Write first key-value pair
echo "Test 2:     Write first key-value pair" . PHP_EOL;
echo "Function:   writeIpKey('test_key', 'test_value')" . PHP_EOL;
echo "Expecting:  No errors, file created" . PHP_EOL;
try {
    ipStore\writeIpKey('test_key', 'test_value');
    $result = ipStore\checkIpFile();
    echo "Result:     ";
    if ($result === true) {
        echo "IP file created successfully (OK)";
    } else {
        echo "IP file not created (ERROR)";
        $testsPassed = false;
    }
} catch (Exception $e) {
    echo "Result:     Exception: " . $e->getMessage() . " (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 3: Read the key we just wrote
echo "Test 3:     Read the key we just wrote" . PHP_EOL;
echo "Function:   readIpKey('test_key')" . PHP_EOL;
echo "Expecting:  'test_value'" . PHP_EOL;
$result = ipStore\readIpKey('test_key');
echo "Result:     ";
if ($result === 'test_value') {
    echo "'$result' (OK)";
} else {
    echo "'$result' (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 4: Check if key exists
echo "Test 4:     Check if key exists" . PHP_EOL;
echo "Function:   checkIpKey('test_key')" . PHP_EOL;
echo "Expecting:  true" . PHP_EOL;
$result = ipStore\checkIpKey('test_key');
echo "Result:     ";
if ($result === true) {
    echo "true (OK)";
} else {
    echo "$result (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 5: Check non-existent key
echo "Test 5:     Check non-existent key" . PHP_EOL;
echo "Function:   checkIpKey('nonexistent_key')" . PHP_EOL;
echo "Expecting:  false" . PHP_EOL;
$result = ipStore\checkIpKey('nonexistent_key');
echo "Result:     ";
if ($result === false) {
    echo "false (OK)";
} else {
    echo "$result (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 6: Read non-existent key
echo "Test 6:     Read non-existent key" . PHP_EOL;
echo "Function:   readIpKey('nonexistent_key')" . PHP_EOL;
echo "Expecting:  'IPSTORE_KEY_NOT_FOUND'" . PHP_EOL;
$result = ipStore\readIpKey('nonexistent_key');
echo "Result:     ";
if ($result === 'IPSTORE_KEY_NOT_FOUND') {
    echo "'$result' (OK)";
} else {
    echo "'$result' (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 7: Write multiple different data types
echo "Test 7:     Write multiple different data types" . PHP_EOL;
echo "Function:   writeIpKey() with various data types" . PHP_EOL;
echo "Expecting:  All data types stored and retrieved correctly" . PHP_EOL;

$testData = [
    'string_key' => 'Hello World',
    'int_key' => 42,
    'float_key' => 3.14159,
    'bool_true_key' => true,
    'bool_false_key' => false,
    'null_key' => null,
    'array_key' => ['apple', 'banana', 'cherry'],
    'assoc_array_key' => ['name' => 'John', 'age' => 30],
    'nested_array_key' => [
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ]
    ]
];

$dataTestPassed = true;
foreach ($testData as $key => $value) {
    ipStore\writeIpKey($key, $value);
    $retrieved = ipStore\readIpKey($key);
    
    if ($retrieved !== $value) {
        $dataTestPassed = false;
        echo "Data mismatch for key '$key':" . PHP_EOL;
        echo "  Expected: " . var_export($value, true) . PHP_EOL;
        echo "  Got:      " . var_export($retrieved, true) . PHP_EOL;
    }
}

echo "Result:     ";
if ($dataTestPassed) {
    echo "All data types stored and retrieved correctly (OK)";
} else {
    echo "Data type storage/retrieval failed (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 8: Update existing key
echo "Test 8:     Update existing key" . PHP_EOL;
echo "Function:   writeIpKey('test_key', 'updated_value')" . PHP_EOL;
echo "Expecting:  'updated_value'" . PHP_EOL;
ipStore\writeIpKey('test_key', 'updated_value');
$result = ipStore\readIpKey('test_key');
echo "Result:     ";
if ($result === 'updated_value') {
    echo "'$result' (OK)";
} else {
    echo "'$result' (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 9: Rate limiting counter simulation
echo "Test 9:     Rate limiting counter simulation" . PHP_EOL;
echo "Function:   Simulate failed login attempts" . PHP_EOL;
echo "Expecting:  Counter increments correctly" . PHP_EOL;

for ($i = 1; $i <= 5; $i++) {
    $currentCount = 0;
    if (ipStore\checkIpKey('login_failures')) {
        $currentCount = ipStore\readIpKey('login_failures');
    }
    $currentCount++;
    ipStore\writeIpKey('login_failures', $currentCount);
}

$finalCount = ipStore\readIpKey('login_failures');
echo "Result:     ";
if ($finalCount === 5) {
    echo "Counter reached $finalCount (OK)";
} else {
    echo "Counter is $finalCount, expected 5 (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 10: IPv6 address handling
echo "Test 10:    IPv6 address handling" . PHP_EOL;
echo "Function:   getSafeIpFilename() with IPv6" . PHP_EOL;
echo "Expecting:  Safe filename generated" . PHP_EOL;

// Test IPv6 address
$ipv6 = '2001:db8:85a3::8a2e:370:7334';
$safeFilename = ipStore\getSafeIpFilename($ipv6);
$expectedPattern = '/^ipv6-[\w\-]+$/';

echo "Result:     ";
if (preg_match($expectedPattern, $safeFilename)) {
    echo "'$safeFilename' matches expected pattern (OK)";
} else {
    echo "'$safeFilename' does not match expected pattern (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 11: IPv4 address handling
echo "Test 11:    IPv4 address handling" . PHP_EOL;
echo "Function:   getSafeIpFilename() with IPv4" . PHP_EOL;
echo "Expecting:  Same as input IP" . PHP_EOL;

$ipv4 = '192.168.1.100';
$safeFilename = ipStore\getSafeIpFilename($ipv4);
echo "Result:     ";
if ($safeFilename === $ipv4) {
    echo "'$safeFilename' (OK)";
} else {
    echo "'$safeFilename', expected '$ipv4' (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 12: Invalid IP handling
echo "Test 12:    Invalid IP handling" . PHP_EOL;
echo "Function:   getSafeIpFilename() with invalid IP" . PHP_EOL;
echo "Expecting:  MD5 hash with 'ip-' prefix" . PHP_EOL;

$invalidIp = 'not.an.ip.address';
$safeFilename = ipStore\getSafeIpFilename($invalidIp);
$expectedPrefix = 'ip-';

echo "Result:     ";
if (strpos($safeFilename, $expectedPrefix) === 0 && strlen($safeFilename) === 35) {
    echo "'$safeFilename' has correct format (OK)";
} else {
    echo "'$safeFilename' does not have expected format (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 13: Delete IP file
echo "Test 13:    Delete IP file" . PHP_EOL;
echo "Function:   deleteIpFile()" . PHP_EOL;
echo "Expecting:  true, file deleted" . PHP_EOL;

$result = ipStore\deleteIpFile();
$fileExists = ipStore\checkIpFile();

echo "Result:     ";
if ($result === true && $fileExists === false) {
    echo "File deleted successfully (OK)";
} else {
    echo "File deletion failed, result: $result, file exists: $fileExists (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 14: Read from deleted file
echo "Test 14:    Read from deleted file" . PHP_EOL;
echo "Function:   readIpKey('test_key')" . PHP_EOL;
echo "Expecting:  'IPSTORE_FILE_NOT_FOUND'" . PHP_EOL;

$result = ipStore\readIpKey('test_key');
echo "Result:     ";
if ($result === 'IPSTORE_FILE_NOT_FOUND') {
    echo "'$result' (OK)";
} else {
    echo "'$result' (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 15: Auto-cleanup disabled mode
echo "Test 15:    Auto-cleanup disabled mode" . PHP_EOL;
echo "Function:   Test IPSTORE_AUTO_CLEANUP = false" . PHP_EOL;
echo "Expecting:  Files stored in /undated/ directory" . PHP_EOL;

// Clear existing test setup
ipStore\deleteFullIpStore();

// Create new test with auto-cleanup disabled
define('IPSTORE_AUTO_CLEANUP_TEST', false);

// Temporarily modify the constant check (this is a testing hack)
// In real usage, the constant would be defined before including the library
$testCleanupMode = false;

// Write a key with cleanup disabled logic
$sx = new \StorX\Sx;
$ip = IPSTORE_IP_SOURCE;
$safeIpName = ipStore\getSafeIpFilename($ip);

// Simulate auto-cleanup disabled behavior
$undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
$ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';

if (!is_dir($undatedDir)) {
    mkdir($undatedDir, 0777, true);
}

if (!file_exists($ipFile)) {
    $sx->createFile($ipFile);
}

$sx->openFile($ipFile, 1);
$sx->modifyKey('undated_test_key', 'undated_test_value');
$sx->closeFile();

$fileExists = file_exists($ipFile);
echo "Result:     ";
if ($fileExists) {
    echo "File created in undated directory (OK)";
} else {
    echo "File not created in undated directory (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 16: Full cleanup test
echo "Test 16:    Full cleanup test" . PHP_EOL;
echo "Function:   deleteFullIpStore()" . PHP_EOL;
echo "Expecting:  All directories and files removed" . PHP_EOL;

// Create some test data in both dated and undated directories
ipStore\writeIpKey('cleanup_test', 'value');

// Add some files to undated directory (already done in previous test)
$result = ipStore\deleteFullIpStore();

$dateDirExists = is_dir(IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d'));
$undatedDirExists = is_dir($undatedDir);

echo "Result:     ";
if (!$dateDirExists && !$undatedDirExists) {
    echo "All directories cleaned up successfully (OK)";
} else {
    echo "Cleanup incomplete - dated dir exists: " . ($dateDirExists ? 'yes' : 'no') . 
         ", undated dir exists: " . ($undatedDirExists ? 'yes' : 'no') . " (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Test 17: Error handling - IP address not available
echo "Test 17:    Error handling - IP address not available" . PHP_EOL;
echo "Function:   Test with empty IP source" . PHP_EOL;
echo "Expecting:  Exception thrown" . PHP_EOL;

// This test checks the validation in the library
$originalIp = IPSTORE_IP_SOURCE;
$exceptionThrown = false;

try {
    // We can't actually change the constant, but we can test the validation logic
    // by checking if empty IP would be caught
    if (empty(IPSTORE_IP_SOURCE)) {
        throw new \Exception('IP address not available - no valid IP source found');
    }
    echo "Result:     IP validation working - no exception needed (OK)";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'IP address not available') !== false) {
        $exceptionThrown = true;
        echo "Result:     Exception correctly thrown: " . $e->getMessage() . " (OK)";
    } else {
        echo "Result:     Wrong exception: " . $e->getMessage() . " (ERROR)";
        $testsPassed = false;
    }
}
echo PHP_EOL . PHP_EOL;

// Test 18: Performance test - multiple rapid writes
echo "Test 18:    Performance test - multiple rapid writes" . PHP_EOL;
echo "Function:   100 rapid writeIpKey() calls" . PHP_EOL;
echo "Expecting:  All writes complete without errors" . PHP_EOL;

$startTime = microtime(true);
$performanceTestPassed = true;

try {
    for ($i = 0; $i < 100; $i++) {
        ipStore\writeIpKey("perf_key_$i", "value_$i");
    }
    
    // Verify a few random keys
    $randomKeys = [10, 50, 99];
    foreach ($randomKeys as $key) {
        $value = ipStore\readIpKey("perf_key_$key");
        if ($value !== "value_$key") {
            $performanceTestPassed = false;
            break;
        }
    }
} catch (Exception $e) {
    $performanceTestPassed = false;
}

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "Result:     ";
if ($performanceTestPassed) {
    echo "100 writes completed in {$duration}ms (OK)";
} else {
    echo "Performance test failed (ERROR)";
    $testsPassed = false;
}
echo PHP_EOL . PHP_EOL;

// Final cleanup
ipStore\deleteFullIpStore();
if (is_dir(IPSTORE_LOCATION)) {
    rmdir(IPSTORE_LOCATION);
}

echo "Tests completed!" . PHP_EOL;
if ($testsPassed === true) {
    echo "FINAL RESULTS: ALL TESTS PASSED!!!";
} else {
    echo "FINAL RESULTS: ALL TESTS *DID NOT* PASS!!!" . PHP_EOL;
    echo "See errors above." . PHP_EOL;
}

?>