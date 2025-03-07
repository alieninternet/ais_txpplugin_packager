#! /usr/bin/env php
<?php
/**
 * Build a PHP archive for this CLI tool
 *
 * Run with:
 * 
 *      php -d phar.readonly=off ./build-phar.php
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
 * @copyright   Copyright (c) 2025 Alien Internet Services
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3
 * @version	0.1
 * @link	https://github.com/alieninternet/ais_txpplugin_packager/
 */

// Output PHAR
$pharFile = 'ais_txpplugin_packager.phar';

// Main (executable) file
$execFile = 'ais_txpplugin_packager.php';

// Clean up any existing PHAR
if (file_exists($pharFile)) {
    unlink($pharFile);
}

// Create the PHAR
$phar = new Phar($pharFile);

// Extract the main boilerplate from the executable
if (preg_match('/\/\*.*?\*\//s',
	       file_get_contents($execFile),
	       $matches)) {
    $boilerplate = $matches[0];
} else {
    echo "Couldn't extract the boilerplate. Woops?\n";
    die();
}

// Strip main executable and restore boilerplate, and add it to the PHAR
$execFileStr = trim(preg_replace('/^\s*<\?php/', '', php_strip_whitespace($execFile)));
$execFileStr = "#! /usr/bin/env php\n<?php\n$boilerplate\n$execFileStr";
$phar->addFromString($execFile, $execFileStr);

// Compress the files in the PHAR
$phar->compressFiles(Phar::GZ);

// Add a stub for the PHAR, but write it out so we can cheat and strip it with php_strip_whitespace()
$pharStubTempFile = ($pharFile . '.tmp');
file_put_contents($pharStubTempFile, $phar->createDefaultStub($execFile));
$pharStub = trim(preg_replace('/^\s*<\?php/', '', php_strip_whitespace($pharStubTempFile)));
unlink($pharStubTempFile);
$phar->setStub("#! /usr/bin/env php\n<?php\n$boilerplate\n$pharStub");

// Make the PHAR executable
chmod($pharFile, 0755);

// All done
echo "PHAR created: $pharFile\n";
