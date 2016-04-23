<?php

/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__ . '/../../../../bootstrap.php';

use runnerupweb\common\Logging;
use runnerupweb\common\ActivityManager;

// avoid php cache system for this download
session_cache_limiter('');
include_once('../../../../include/header_session.php');

// the file is downloaded in gzip => if not accepted just a 404
if (strpos(filter_input(INPUT_SERVER, 'HTTP_ACCEPT_ENCODING'), 'gzip') === false) {
    Logging::warning("The browser does not support gzip encoding...");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . " 406 Not Acceptable");
    die();
}
// get the file if exists
$am = ActivityManager::getActivityManager();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array("options" => array("min_range" => 1)));
$file = null;
if ($id) {
    $file = $am->getActivityFile($user->getLogin(), $id);
}
if (!$file) {
    // file does not exists => 404
    Logging::debug("File $id does not exists!");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 404 Not Found');
    die();
}
// read the file size and info
$size = filesize($file);
$fileinfo = pathinfo($file)['basename'];
// check 304 by modified since
$last_modified_time = filemtime($file); 
if (strtotime(filter_input(INPUT_SERVER, 'HTTP_IF_MODIFIED_SINCE')) >= $last_modified_time) {
    Logging::debug("Returning 304 cos not modified.");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 304 Not Modified');
    exit;
}
// check 304 by etag
$etag = md5_file($file);
if (trim(filter_input(INPUT_SERVER, 'HTTP_IF_NONE_MATCH')) == $etag) {
    Logging::debug("Returning 304 cos ETAG.");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 304 Not Modified');
    exit;
}
// manage the possible ranges
// snippet: http://www.php.net/manual/en/function.fread.php#84115
if (filter_input(INPUT_SERVER, 'HTTP_RANGE')) {
    list($size_unit, $range_orig) = explode('=', filter_input(INPUT_SERVER, 'HTTP_RANGE'), 2);
    if ($size_unit == 'bytes') {
        //multiple ranges could be specified at the same time, but for simplicity only serve the first range
        //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
        list($range, $extra_ranges) = explode(',', $range_orig, 2);
    } else {
        $range = '';
    }
} else {
    $range = '';
}
//figure out download piece from range (if set)
if (strpos($range, '-') !== false) {
    list($seek_start, $seek_end) = explode('-', $range, 2);
}
//set start and end based on range (if set), else set defaults
//also check for invalid ranges.
$seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);
// set the headers for ranges
if ($seek_start > 0 || $seek_end < ($size - 1)) {
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 206 Partial Content');
}
header('Accept-Ranges: bytes');
header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="' . $fileinfo . '"');
header('Content-Length: ' . ($seek_end - $seek_start + 1));
// set the headers for 304 not modified
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time)." GMT"); 
header("Etag: $etag"); 
// the file is always sent gzipped (gzip should be supported by the browser)
header('Content-Encoding: gzip');
// set custom cache-control to make work 304 (not cached but at least 304)
header('Cache-Control: private, max-age=0');
//open the file
$fp = fopen($file, 'rb');
//seek to start of missing part
fseek($fp, $seek_start);
// start buffered download (using 8K buffer)
$buffer = fread($fp, 8192);
while (strlen($buffer) === 8192) {
    print($buffer);
    flush();
    $buffer = fread($fp, 8192);
}
// print last chunk if not 0
if (strlen($buffer) > 0) {
    print($buffer);
    flush();
}
fclose($fp);