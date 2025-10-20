<?php
$url = "http://dms-back.test";
$response = @file_get_contents($url);
if ($response === FALSE) {
    echo "Cannot connect to $url";
} else {
    echo "Connected successfully!";
}

