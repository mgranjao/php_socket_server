<?php
//importing laravel autoload, copy this script in the project root
require 'vendor/autoload.php';
/*PHP MULTIPLE CLIENT SOCKET SERVER, WITH IMPROVED MEMORY PERFOMANCE
 *USE servidordesensores FILE TO CREATE A DAEMON IN LINUX SERVER
 */



// Notify all errors except E_NOTICE
error_reporting(E_ALL ^ E_NOTICE);

//activate garbage collector
gc_enable();

/* Allow the script to wait for connections */
set_time_limit(0);

/* Enable implicit dump output */
ob_implicit_flush();



//Server Ip and Port
$address = '192.168.100.99';
$port = 1515;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() fallo: razon: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() fallo: razon: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() fallo: razon: " . socket_strerror(socket_last_error($sock)) . "\n";
}

//clients array
$clients = array();

do {
    $read = array();
    $read[] = $sock;
    
    $read = array_merge($read,$clients);
    
    // Set up a blocking call to socket_select
    if(socket_select($read,$write = NULL, $except = NULL, $tv_sec = 5) < 1)
    {

        continue;
    }
    
    // Handle new Connections
    if (in_array($sock, $read)) {        
        
        if (($msgsock = socket_accept($sock)) === false) {
            echo "socket_accept() fallo: razon: " . socket_strerror(socket_last_error($sock)) . "\n";

            break;
        }
        $clients[] = $msgsock;
        $key = array_keys($clients, $msgsock);
        /* Send instrucctions to client */
        $msg = "\nServidor De Prueba de VOC. \n" .
        "Usted es el cliente numero: {$key[0]}\n" .
        "Para salir, escriba 'quit'. Para cerrar el servidor escriba 'shutdown'.\n";
        socket_write($msgsock, $msg, strlen($msg));
        
    }
    
    // Handle Input
    foreach ($clients as $key => $client) { // for each client        
        if (in_array($client, $read)) {
            if (false === ($buf = socket_read($client, 2048, PHP_NORMAL_READ))) {
                echo "socket_read() fallo: razon: " . socket_strerror(socket_last_error($client)) . "\n";
                if(strcmp ('Connection reset by peer',socket_strerror(socket_last_error($client)))==0)
                {
                    unset($clients[$key]);
                    socket_close($client);
                    break;
                }
                else{
                    unset($clients[$key]);
                    socket_close($client);
                    break ;
                }
		
            }
            if (!$buf = trim($buf)) {
                continue;
            }
            if ($buf == 'quit') {
                unset($clients[$key]);
                socket_close($client);
                break;
            }
            if ($buf == 'shutdown') {
                socket_close($client);
                pg_close($dbconn);
                break 2;
            }
            $talkback = "Cliente {$key}: Usted dijo '$buf'.\n";
            socket_write($client, $talkback, strlen($talkback));
            echo "$buf\n";


            /*************************************************************************************************************************
            INSERT YOUR BUSSINESS LOGIC HERE
             * $buf contains the received string
            ************************************************************************************************************************/





            unset($talkback);unset($result);unset($buf);unset($key);unset($client);
            //Unset your created vars here to optimize memory usage

            gc_collect_cycles();
            print sprintf("Allocated memory usage: %s, Memory Limit: %d, PID: %d\n",
                number_format(memory_get_usage()),
                ini_get("memory_limit"),
                getmypid()
                );



        }
        
    }        
} while (true);
// Closing connection
socket_close($sock);
?>
