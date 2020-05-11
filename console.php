<?php
error_reporting(E_ALL);

include_once('config.php');

function tpiChecksum($str)
{
    $sum = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $sum += ord($str[$i]);
    }
    return sprintf('%02X', $sum & 0xff);
}

function sendTpi($cmd, $data)
{
    global $sock;

    $cmd .= $data;
    $cmd .= tpiChecksum($cmd);
    printf("[send] %s\n", $cmd);
    fwrite($sock, $cmd . "\r\n");
}

function handleCmdUser($data)
{
    if (strlen($data) < 3) {
        printf("%% Invalid Length\n");
        return;
    }

    sendTpi(substr($data, 0, 3), substr($data, 3));
}

function handleCmdTpi($data)
{
    printf("[recv] %s\n", $data);

    if (strlen($data) < 5) {
        printf("%% Invalid Length\n");
        return;
    }

    $checksum = substr($data, -2);
    $data = substr($data, 0, strlen($data) - 2);
    if ($checksum !== tpiChecksum($data)) {
        printf("%% Invalid Checksum\n");
        return;
    }

    $cmd = substr($data, 0, 3);
    if (!ctype_digit($cmd)) {
        printf("%% Invalid Command\n");
        return;
    }

    $data = substr($data, 3);

    switch ($cmd) {
    case '505':
        if ($data[0] == '3') {
            sendTpi('005', substr(TPI_PASSWORD, 0, 10));
        }
        break;
    }
}

function splitCmd(&$buf, &$data)
{
    $data = explode("\n", $buf . $data);
    $buf = array_pop($data);
    foreach ($data as &$line) {
        $line = rtrim($line, "\r");
    }
}

$in = fopen('php://stdin', 'r');
$sock = fsockopen(TPI_HOST, TPI_PORT);


$buf1 = $buf2 = '';
while (true) {
    $read = [$in, $sock];
    $write = null;
    $except = null;
    if (stream_select($read, $write, $except, null) === false) {
        break;
    }

    foreach ($read as $stream) {
        $data = fread($stream, 4096);
        if (!strlen($data)) {
            break 2;
        }

        if ($stream == $in) {
            splitCmd($buf1, $data);
            foreach ($data as $line) {
                handleCmdUser($line);
            }
        } elseif ($stream == $sock) {
            splitCmd($buf2, $data);
            foreach ($data as $line) {
                handleCmdTpi($line);
            }
        }
    }
}
