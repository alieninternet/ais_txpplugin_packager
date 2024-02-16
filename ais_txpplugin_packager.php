#! /usr/bin/env php
<?php
/**
 * ais_txpplugin_packager - Packaging tool for Textpattern plugins
 *
 * 
 * This tool will produce a packed, gzipped, base64 encoded plugin package
 * similar to that provided by zem_tpl and other tools, compatible with
 * Textpattern 4.8.x.
 * 
 * Its purpose is to allow Textpattern plugins to be developed with
 * stand-alone package files rather than one monolothic file, such as
 * the zem plugin template style).
 * 
 * The reason is that monolithic files are poor candidates for version control,
 * make it difficult to work efficiently with different file types, and increase
 * maintenance complexity for complex plugins.
 * 
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * 
 * @author	Ashley Butcher
 * @copyright   Copyright (C) 2022-2024 Ashley Butcher (Alien Internet Services)
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3
 * @version	0.1
 * @link	https://github.com/alieninternet/ais_txpplugin_packager/
 */

const VERSION = '0.1';

/* Must only be run on the CLI. This is to avoid the script being 
 * executed if accidentally made via a web server.
 */
if (php_sapi_name() !== 'cli') {
    exit(2);
}

// Log version
echo ('ais_txpplugin_packager - Packaging tool for Textpattern plugins - version ' . VERSION . "\n\n");

// We need a plugin folder to be specified
if ($argc <= 1) {
    echo "Usage: php ais_txpplugin_packager.php <plugin_path> [<output_file>]\n\n";
    exit(1);
}

// Fetch plugin path
$pluginPath = realpath($argv[1]);
if (!is_dir($pluginPath)) {
    echo "The specified plugin path must be a valid folder.\n";
    exit(3);
}

// Fetch output file, if provided
if (isset($argv[2])) {
    $outputFilename = $argv[2];
}

// Ensure the path has a trailing slash
if (substr($pluginPath, -1) !== '/') {
    $pluginPath .= '/';
}

// Log path
echo 'Reading Textpattern plugin from ' . $pluginPath . "\n";

/* ********************************************************************************** */
echo "\n";

// Determine plugin name
$plugin = basename(rtrim($pluginPath, '/'));
echo ('       Plugin name: ' . $plugin . "\n");

// Read in the manifest
$manifestFilename = ($pluginPath . 'manifest.json');
if (file_exists($manifestFilename)) {
    $manifestRaw = file_get_contents($manifestFilename);
}
if (!isset($manifestRaw) ||
    ($manifestRaw === false)) {
    echo ('Plugin manifest ' . basename($manifestFilename) . ' was not found in path: ' . $pluginPath . "\n");
    exit(4);
}

// Parse manifest
$manifest = json_decode($manifestRaw, true);
if (($manifest === null) && 
    (json_last_error() !== JSON_ERROR_NONE)) {
    echo ('Plugin manifest ' . basename($manifestFilename) . ' cannot be parsed: ' . json_last_error_msg() . "\n");
    exit(5);
}

// Convert plugin type to something for humans to read
function pluginTypeString(int $type): string
{
    $types = ['public',
	      'public + admin',
	      'library',
	      'admin',
	      'admin + ajax',
	      'public + admin + ajax'];

    if (!array_key_exists($type, $types)) {
	return 'UNKNOWN';
    }
    
    return $types[$type];
}

// Convert plugin flags to something for humans to read
function pluginFlagsString(int $flags): string
{
    $PLUGIN_HAS_PREFS = 0x0001;
    $PLUGIN_LIFECYCLE_NOTIFY = 0x0002;
      
    $flagArray = [];
    
    if ($flags & $PLUGIN_HAS_PREFS) {
	$flagArray[] = 'HAS_PREFS';
    }

    if ($flags & $PLUGIN_LIFECYCLE_NOTIFY) {
	$flagArray[] = 'LIFECYCLE_NOTIFY';
    }
    
    if (empty($flagArray)) {
	return 'none';
    }
    
    return implode(' | ', $flagArray);
}

// Validate manifest
foreach (['description', 'version', 'author', 'author_uri', 'type', 'flags'] as $v) {
    if (!isset($manifest[$v])) {
	echo "Manifest does not contain '$v'\n";
	exit(6);
    }
}

// Log manifest
echo ('       Description: ' . $manifest['description'] . "\n");
echo ('           Version: ' . $manifest['version'] . "\n");
echo ('            Author: ' . $manifest['author'] . "\n");
echo ('        Author URI: ' . $manifest['author_uri'] . "\n");
if (isset($manifest['order'])) {	  
    echo ('             Order: ' . $manifest['order'] . "\n");
}
echo ('              Type: ' . $manifest['type'] . ' (' . pluginTypeString(intval($manifest['type'])). ")\n");
echo ('             Flags: ' . $manifest['flags'] . ' (' . pluginFlagsString(intval($manifest['flags'])). ")\n");


/* ********************************************************************************** */
echo "\n";

// Read in code
$codeFilename = ($pluginPath . $plugin . '.php');
if (file_exists($codeFilename)) {
    $code = file_get_contents($codeFilename);
}
if (!isset($code) ||
    ($code === false)) {
    echo "Plugin code cannot be read from file $codeFilename\n";
    exit(7);
}
echo ("              Code: " . basename($codeFilename) . ' (' . strlen($code) . " bytes)\n");

// Textpattern will add the <?php prefix to the file itself, so we must remove it
if (substr($code, 0, 5) === '<?php') {
    $code = substr($code, 5);
}

// Try to find a data file
$dataFilename = ($pluginPath . 'data.txp');
if (file_exists($dataFilename)) {
    $data = file_get_contents($dataFilename);
}
if (isset($data) &&
    ($data !== false)) {
    echo ("              Data: " . basename($dataFilename) . ' (' . strlen($data) . " bytes)\n");
}

// Try to find a textpack
$textpackFilename = ($pluginPath . 'textpack.txp');
if (file_exists($textpackFilename)) {
    $textpack = file_get_contents($textpackFilename);
}
if (isset($textpack) &&
    ($textpack !== false)) {
    echo ("          Textpack: " . basename($textpackFilename) . ' (' . strlen($textpack) . " bytes)\n");
}

// Try to find a help file (HTML format)
$helpHTMLFilename = ($pluginPath . 'help.html');
if (file_exists($helpHTMLFilename)) {
    $helpHTML = file_get_contents($helpHTMLFilename);
}
if (isset($helpHTML) &&
    ($helpHTML !== false)) {
    echo ("         HTML help: " . basename($helpHTMLFilename) . ' (' . strlen($helpHTML) . " bytes)\n");
}

// Try to find a help file (textile format)
$helpTextileFilename = ($pluginPath . 'help.textile');
if (file_exists($helpTextileFilename)) {
    $helpTextile = file_get_contents($helpTextileFilename);
}
if (isset($helpTextile) &&
    ($helpTextile !== false)) {
    echo ("      Textile help: " . basename($helpTextileFilename) . ' (' . strlen($helpTextile) . " bytes)\n");
}

/* ********************************************************************************** */
echo "\n";

// Start packing by making a copy of the manifest
$packed = $manifest;
$packed['name'] = $plugin;

// Add files
$packedFileCount = 1;
$packed['code'] = $code;
$packed['code_md5'] = md5($code);
if (isset($data) &&
    is_string($data)) {
    $packed['data'] = $data;
    ++$packedFileCount;
}
if (isset($textpack) &&
    is_string($textpack)) {
    $packed['textpack'] = $textpack;
    ++$packedFileCount;
}
if (isset($helpHTML) &&
    is_string($helpHTML)) {
    $packed['help'] = $helpHTML;
    ++$packedFileCount;
}
if (isset($helpTextile) &&
    is_string($helpTextile)) {
    $packed['help_raw'] = $helpTextile;
    ++$packedFileCount;
}

// Serialise
$serialized = serialize($packed);
echo ("Serialized $packedFileCount files.\nPacked size is " . strlen($serialized) . " bytes.\n");

// Compress
$serialized = gzencode($serialized);
echo ('Compressed size is ' . strlen($serialized) . " bytes.\n");

// Base-64 encode
$serialized = base64_encode($serialized);
echo ('Base-64 encoded size is ' . strlen($serialized) . " bytes.\n");

// Build preamble
$preamble = ("# $plugin v{$manifest['version']}\n# {$manifest['description']}\n# {$manifest['author']}\n# {$manifest['author_uri']}\n" .
	     "\n# " . str_repeat('-', 76) .
	     "\n# This is a plugin for Textpattern built using ais_txpplugin_packager.php" .
	     "\n# To install: textpattern > admin > plugins" .
	     "\n# Paste the following text into the 'Install plugin' box:" .
	     "\n# " . str_repeat('-', 76) . "\n\n");

// Format output
$serialized = ($preamble . trim(chunk_split($serialized, 78)) . "\n");
echo ('Packaged size is ' . strlen($serialized) . " bytes.\n");

// Prepare output filename
if (!isset($outputFilename)) {
    $outputFilename = ("./{$plugin}-v" . str_replace('.', '_', $manifest['version']) . '.txt');
}

// Write the file out
if (!file_put_contents($outputFilename, $serialized)) {
    echo "Unable to write plugin to $outputFilename\n";
    exit(10);
}

echo "\nPlugin packaged as $outputFilename\n\n";

?>