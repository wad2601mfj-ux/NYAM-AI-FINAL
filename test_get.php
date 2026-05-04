<?php
// Test 1: Init (no since) — must return instantly
$t = microtime(true);
$r = file_get_contents('http://localhost/WAD%20php/long_poll.php?table=chats&filterKey=buyerId&filterVal=test_buyer');
echo "Init response (" . round((microtime(true)-$t)*1000) . "ms): " . $r . "\n\n";

// Test 2: With very old timestamp — must return instantly with existing data
$t = microtime(true);
$r = file_get_contents('http://localhost/WAD%20php/long_poll.php?table=chats&filterKey=buyerId&filterVal=test_buyer&since=2020-01-01+00:00:00');
echo "Old since (" . round((microtime(true)-$t)*1000) . "ms): " . substr($r, 0, 200) . "\n\n";

// Test 3: With current timestamp — will wait, should timeout after ~25s
$since = json_decode(file_get_contents('http://localhost/WAD%20php/long_poll.php?table=chats&filterKey=buyerId&filterVal=test_buyer'), true)['since'];
echo "Now testing wait with since=$since\n";
echo "(This will wait up to 25s, or until you send a new chat from browser...)\n";
$t = microtime(true);
$r = file_get_contents("http://localhost/WAD%20php/long_poll.php?table=chats&filterKey=buyerId&filterVal=test_buyer&since=" . urlencode($since));
echo "Wait result (" . round((microtime(true)-$t)*1000) . "ms): " . $r . "\n";
