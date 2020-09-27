<?php

// Simple command line tool to simulate an EnvisaLink board. It is meant as
// a test tool for EnvisaLink client. In addition to a simple nc session,
// the tool implements the following features:
//  - The checksum is calculated and appended automatically on outgoing
//    commands.
//  - Incoming commands are parsed and printed using ANSI colors to make
//    them easier to read.
//
// To send commands to the client, type the 3 digit command followed by the
// data and Enter. The checksum is calculated and appended automatically.
//
// To exit from the tool, type Ctrl-D, which corresponds to end of file.

error_reporting(E_ALL);

include_once('common.php');

$in = fopen('php://stdin', 'r');
$listen = socket_create_listen(4025);

if (!$listen) {
    exit(1);
}

socket_getsockname($listen, $addr, $port);
userMsg(0, "Server Listening on $addr:$port");

$sock = socket_accept($listen);
socket_close($listen);
socket_getpeername($sock, $addr, $port);
userMsg(0, "Received Connection from $addr:$port");
$sock = socket_export_stream($sock);

sendTpi('505', '3');
mainLoop();
