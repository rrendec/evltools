<?php

// Simple command line tool to make it easier to connect to EnvisaLink and
// send commands manually. In addition to a simple telnet session, the tool
// implements the following features:
//  - The checksum is calculated and appended automatically on outgoing
//    commands.
//  - Incoming commands are parsed and printed using ANSI colors to make
//    them easier to read.
//  - Some commands that require a user response (such as the initial
//    authentication and master/installer code prompt) are automated.
//
// To send commands to EnvisaLink, type the 3 digit command followed by the
// data and Enter. The checksum is calculated and appended automatically.
//
// To exit from the tool, type Ctrl-D, which corresponds to end of file.

error_reporting(E_ALL);

include_once('config.php');
include_once('common.php');

function cmdCallback($cmd, $data)
{
    static $loginTable = [
        'Password Incorrect',
        'Password Correct',
        'Timeout',
        'Password Required',
    ];

    switch ($cmd) {
    case '500':
        userMsg(0, 'Command Acknowledge: ' . $data);
        break;
    case '501':
        userMsg(1, 'Command Error');
        break;
    case '505':
        userMsg($data[0] == '1' ? 0 : 1, 'Login: ' . $loginTable[$data[0]]);
        if ($data[0] == '3' && defined('CFG_TPI_PASSWORD')) {
            sendTpi('005', substr(CFG_TPI_PASSWORD, 0, 10));
        }
        break;
    case '921':
        userMsg(1, 'Master Code Required');
        if (defined('CFG_MASTER_CODE')) {
            sendTpi('200', CFG_MASTER_CODE);
        }
        break;
    case '922':
        userMsg(1, 'Installer Code Required');
        if (defined('CFG_INSTALLER_CODE')) {
            sendTpi('200', CFG_INSTALLER_CODE);
        }
        break;
    }
}

$host = CFG_TPI_HOST;
$port = CFG_TPI_PORT;

if ($_SERVER['argc'] > 1) {
    $host = $_SERVER['argv'][1];
}

if ($_SERVER['argc'] > 2) {
    $port = $_SERVER['argv'][2];
}

$in = fopen('php://stdin', 'r');
$sock = fsockopen($host, $port);

if (!$sock) {
    exit(1);
}

mainLoop('cmdCallback');
