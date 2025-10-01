<?php

/*

ipStore by @aaviator42
v0.2, 2025-08-07
AGPLv3
*/

namespace ipStore;

// We require the aaviator42/StorX library for sqlite storage
require_once __DIR__ . '/StorX.php';

// Folder in which to store sqlite files tracking IP data
// Override this constant before including the library to change storage location
if (!defined('IPSTORE_LOCATION')) {
    define('IPSTORE_LOCATION', __DIR__ . '/../db/ip');
}

// Automatic cleanup configuration
// Override this constant before including the library to disable automatic cleanup
if (!defined('IPSTORE_AUTO_CLEANUP')) {
    define('IPSTORE_AUTO_CLEANUP', true);
}

// IP address source configuration
// Override this constant before including the library to change IP source
if (!defined('IPSTORE_IP_SOURCE')) {
    // Default: Use REMOTE_ADDR for direct connections
    define('IPSTORE_IP_SOURCE', $_SERVER['REMOTE_ADDR'] ?? '');
    
    // Alternative configurations for different setups:
    // For CloudFlare: 
    //      define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    // For reverse proxy/load balancer: 
    //      define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    // For real IP header: 
    //      define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    // For AWS ALB/ELB: 
    //      define('IPSTORE_IP_SOURCE', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    // Multiple proxy chain: 
    //      define('IPSTORE_IP_SOURCE', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
}

// Validate IP address source
if (empty(IPSTORE_IP_SOURCE)) {
    throw new \Exception('IP address not available - no valid IP source found');
}

// Function to create a safe filename from an IP address
function getSafeIpFilename($ip) {
    // For IPv4 addresses, keep them as-is (they're already safe)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ip;
    }
    
    // For IPv6 addresses, replace colons with dashes and compress
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Normalize the IPv6 address first
        $normalized = inet_ntop(inet_pton($ip));
        // Replace colons with dashes to make it filesystem-safe
        return 'ipv6-' . str_replace(':', '-', $normalized);
    }
    
    // Fallback: use MD5 hash for any other case
    return 'ip-' . md5($ip);
}


function writeIpKey($key, $value) {
    $sx = new \StorX\Sx;
    $ip = IPSTORE_IP_SOURCE;
    $safeIpName = getSafeIpFilename($ip);

    if (IPSTORE_AUTO_CLEANUP) {
        $dateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
        $ipFile = $dateDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';

        // Create the date directory if it doesn't exist
        if (!is_dir($dateDir)) {
            if (!mkdir($dateDir, 0777, true)) {
                throw new \Exception("Failed to create directory: $dateDir");
            }
        }
    } else {
        $undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
        $ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';

        // Create the undated directory if it doesn't exist
        if (!is_dir($undatedDir)) {
            if (!mkdir($undatedDir, 0777, true)) {
                throw new \Exception("Failed to create directory: $undatedDir");
            }
        }
    }

    // Create DB file if it doesn't exist
    if (!file_exists($ipFile)) {
        // error_log("Creating new IP store file: $ipFile");
        $sx->createFile($ipFile);
    }

    // Open the file and write
    $sx->openFile($ipFile, 1);
    $sx->modifyKey($key, $value);
    $sx->closeFile();

    if (IPSTORE_AUTO_CLEANUP) {
        deleteOldIpStore();
    }
}

function readIpKey($key){
	$sx = new \StorX\Sx;
	$ip = IPSTORE_IP_SOURCE;
	$safeIpName = getSafeIpFilename($ip);
	
	if (IPSTORE_AUTO_CLEANUP) {
		$dateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
		$ipFile = $dateDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	} else {
		$undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
		$ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	}
	
	if ( !file_exists($ipFile) ) { 
		return 'IPSTORE_FILE_NOT_FOUND';
	}
	
	$sx->openFile($ipFile);
	
	if ( !$sx->checkKey($key) ){
		return 'IPSTORE_KEY_NOT_FOUND';
	}
	
	$value = $sx->returnKey($key);
	$sx->closeFile();
	return $value;

}

function checkIpKey($key){
	$sx = new \StorX\Sx;
	$ip = IPSTORE_IP_SOURCE;
	$safeIpName = getSafeIpFilename($ip);
	
	if (IPSTORE_AUTO_CLEANUP) {
		$dateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
		$ipFile = $dateDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	} else {
		$undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
		$ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	}

	if ( !file_exists($ipFile) ) { 
		// return 'IPSTORE_FILE_NOT_FOUND';
		return false;
	}
	
	$sx->openFile($ipFile);
	
	if ( $sx->checkKey($key) ){
		return true;
	} else {
		return false;
	}
	
}

function checkIpFile(){
	$sx = new \StorX\Sx;
	$ip = IPSTORE_IP_SOURCE;
	$safeIpName = getSafeIpFilename($ip);
	
	if (IPSTORE_AUTO_CLEANUP) {
		$dateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
		$ipFile = $dateDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	} else {
		$undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
		$ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	}

	if ( $sx->checkFile($ipFile) === 1 ) { 
		return true;
	} else {
		return false;
	}
}


function deleteIpFile(){
	$sx = new \StorX\Sx;
	$ip = IPSTORE_IP_SOURCE;
	$safeIpName = getSafeIpFilename($ip);
	
	if (IPSTORE_AUTO_CLEANUP) {
		$dateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
		$ipFile = $dateDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	} else {
		$undatedDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . 'undated';
		$ipFile = $undatedDir . DIRECTORY_SEPARATOR . $safeIpName . '.db';
	}
	
	if ( !file_exists($ipFile) ){
		return true;
	}
	
	if ( $sx->deleteFile($ipFile) ) { 
		return true;
	} else {
		return false;
	}
}




function deleteOldIpStore() {
	// Get all subdirectories in the ipstore directory
    $subDirs = getSubdirectories(IPSTORE_LOCATION);
	
    // Delete existing subdirectories
    foreach ($subDirs as $dir) {
        $currentDateDir = IPSTORE_LOCATION . DIRECTORY_SEPARATOR . date('Y-m-d');
        if($dir === $currentDateDir) {
			continue; //skip current date ipstore subfolder
		} else {
			deleteDir($dir); //delete all other ipstore subfolders
		}
    }

}


function deleteFullIpStore() {
	// Get all subdirectories in the ipstore directory
    $subDirs = getSubdirectories(IPSTORE_LOCATION);
	
    // Delete existing subdirectories
    foreach ($subDirs as $dir) {
		deleteDir($dir); //delete all ipstore subfolders
	}
}

// Function to recursively delete a directory and its contents
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }
    
    $files = array_diff(scandir($dirPath), array('.', '..'));
    
    foreach ($files as $file) {
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
        is_dir($filePath) ? deleteDir($filePath) : unlink($filePath);
    }
    
    rmdir($dirPath);
}

// realpath() but works with files that don't exist yet
function get_absolute_path($path) {
	$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
	$absolutes = array();
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = $part;
		}
	}
	return "/" . implode(DIRECTORY_SEPARATOR, $absolutes);
}

function getSubdirectories($directory) {
    $subdirs = [];
    
    // Ensure the directory exists
    if (is_dir($directory)) {
        // Scan the directory
        $items = scandir($directory);
        
        // Loop through each item
        foreach ($items as $item) {
            // Skip current and parent directory links
            if ($item !== "." && $item !== "..") {
                $itemPath = $directory . DIRECTORY_SEPARATOR . $item;
                
                // Check if the item is a directory
                if (is_dir($itemPath)) {
                    $subdirs[] = $itemPath; // Add directory to list
                }
            }
        }
    }
    
    return $subdirs;
}