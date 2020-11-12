<?php

require_once __DIR__ . '/../src/bootstrap.php';

$configFile = is_file(__DIR__ . '/config.php') ? __DIR__ . '/config.php' : __DIR__ . '/config.php.dist';
$configs    = include $configFile;

$consulHost = JetUtil::arrayGet($configs, 'consul.host', '127.0.0.1');
$consulPort = JetUtil::arrayGet($configs, 'consul.port', 8500);
echo sprintf("CONSUL_URI: http://%s:%s\n", $consulHost, $consulPort);

$agent = new JetConsulAgent(array(
    'uri'     => sprintf('http://%s:%s', $consulHost, $consulPort),
    'timeout' => 2,
));

$health = new JetConsulHealth(array(
    'uri'     => sprintf('http://%s:%s', $consulHost, $consulPort),
    'timeout' => 2,
));

$protocols = array('jsonrpc-http', 'jsonrpc');
$ports     = array(9502, 9503);
$host      = PHP_OS === 'Darwin' ? getHostByName(getHostName()) : 'localhost';

foreach ($protocols as $i => $protocol) {
    echo "Registering {$protocol} ...\n";

    // $agent
    $requestBody = array(
        'Name'    => 'CalculatorService',
        'ID'      => 'CalculatorService-' . $protocol,
        'Address' => $host,
        'Port'    => $ports[$i],
        'Meta'    => array(
            'Protocol' => $protocol,
        ),
    );

    switch ($protocol) {
        case 'jsonrpc-http':
            $requestBody['Check'] = array(
                'DeregisterCriticalServiceAfter' => '90m',
                'HTTP'                           => "http://{$host}:{$ports[$i]}/",
                'Interval'                       => '1s',
            );
            break;
        case 'jsonrpc':
        case 'jsonrpc-tcp-length-check':
            $requestBody['Check'] = array(
                'DeregisterCriticalServiceAfter' => '90m',
                'TCP'                            => "{$host}:{$ports[$i]}",
                'Interval'                       => '1s',
            );
            break;
    }

    echo "Service Metadata: " . json_encode($requestBody) . "\n";

    if ($agent->registerService($requestBody)->throwIf()->ok()) {
        echo "Registered!\n";
    }

}

var_dump($health->service('CalculatorService')->throwIf()->json());
