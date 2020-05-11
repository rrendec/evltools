<?php
error_reporting(E_ALL);

include_once('config.php');

define('ANSI_COLOR_RESET',          "\033[0m");
define('ANSI_COLOR_LIGHT_RED',      "\033[91m");
define('ANSI_COLOR_LIGHT_GREEN',    "\033[92m");
define('ANSI_COLOR_LIGHT_YELLOW',   "\033[93m");
define('ANSI_COLOR_LIGHT_MAGENTA',  "\033[95m");
define('ANSI_COLOR_LIGHT_CYAN',     "\033[96m");

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

    $checksum = tpiChecksum($cmd . $data);
    printf("[%ssend%s] %s%s%s%s%s%s%s\n",
        ANSI_COLOR_LIGHT_GREEN,
        ANSI_COLOR_RESET,
        ANSI_COLOR_LIGHT_CYAN,
        $cmd,
        ANSI_COLOR_RESET,
        $data,
        ANSI_COLOR_LIGHT_MAGENTA,
        $checksum,
        ANSI_COLOR_RESET
    );
    fwrite($sock, $cmd . $data . $checksum . "\r\n");
}

function handleCmdUser($data)
{
    if (strlen($data) < 3) {
        printf("%% %sInvalid Length%s\n",
            ANSI_COLOR_LIGHT_YELLOW,
            ANSI_COLOR_RESET
        );
        return;
    }

    sendTpi(substr($data, 0, 3), substr($data, 3));
}

function handleCmdTpi($data)
{
    printf("[%srecv%s] %s%s%s%s%s%s%s\n",
        ANSI_COLOR_LIGHT_RED,
        ANSI_COLOR_RESET,
        ANSI_COLOR_LIGHT_CYAN,
        substr($data, 0, 3),
        ANSI_COLOR_RESET,
        substr($data, 3, max(0, strlen($data) - 5)),
        ANSI_COLOR_LIGHT_MAGENTA,
        substr($data, strlen($data) - 2, 2),
        ANSI_COLOR_RESET
    );

    if (strlen($data) < 5) {
        printf("%% %sInvalid Length%s\n",
            ANSI_COLOR_LIGHT_YELLOW,
            ANSI_COLOR_RESET
        );
        return;
    }

    $checksum = substr($data, -2);
    $data = substr($data, 0, strlen($data) - 2);
    if ($checksum !== tpiChecksum($data)) {
        printf("%% %sInvalid Checksum%s\n",
            ANSI_COLOR_LIGHT_YELLOW,
            ANSI_COLOR_RESET
        );
        return;
    }

    $cmd = substr($data, 0, 3);
    if (!ctype_digit($cmd)) {
        printf("%% %sInvalid Command%s\n",
            ANSI_COLOR_LIGHT_YELLOW,
            ANSI_COLOR_RESET
        );
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
                if (strlen($line)) {
                    handleCmdUser($line);
                }
            }
        } elseif ($stream == $sock) {
            splitCmd($buf2, $data);
            foreach ($data as $line) {
                handleCmdTpi($line);
            }
        }
    }
}
