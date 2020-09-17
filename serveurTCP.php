<?php
require('vendor/autoload.php');

$host = 'localhost';
$port = '9095';
$null = NULL;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, $port);
socket_listen($socket);

$clients = array($socket);

while (true) {
    $changed = $clients;
    socket_select($changed, $null, $null, 0, 10);

    if (in_array($socket, $changed)) {
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;

        $header = socket_read($socket_new, 1024);
        perform_handshaking($header, $socket_new, $host, $port);

        socket_getpeername($socket_new, $ip);
        $response = json_encode(array('type' => 'system', 'message' => $ip . ' connected'));
        send_message($response);
        $found_socket = array_search($socket, $changed);
        unset($changed[$found_socket]);
    }

    foreach ($changed as $changed_socket) {
        while (socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
            var_dump(date_create()->format('H:i:s') . " : " . $buf);
            send_message(json_encode(array('type' => 'status', 'message' => "coucou")));
            break 2;
        }

        $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
        if ($buf === false) {
            $found_socket = array_search($changed_socket, $clients);
            @socket_getpeername($changed_socket, $ip);
            unset($clients[$found_socket]);

            $response = json_encode(array('type' => 'system', 'message' => $ip . ' disconnected'));
            send_message($response);
        }
    }
}

socket_close($socket);

function send_message($msg)
{
    global $clients;
    foreach ($clients as $changed_socket) {
        @socket_write($changed_socket, $msg, strlen($msg));
    }
    return true;
}

function perform_handshaking($receved_header, $client_conn, $host, $port)
{
    $headers = array();
    $lines = preg_split("/\r\n/", $receved_header);
    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade  = "CONNEXION AU SERVEUR TCP DE LULU\r\n\r\n";
    socket_write($client_conn, $upgrade, strlen($upgrade));
}
