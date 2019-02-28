<?php
	require_once __DIR__.'/vendor/autoload.php';

	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PiPHP\GPIO\Pin\PinInterface;
	use PiPHP\GPIO\GPIO;

	// I/O Stuffs
	$io = new GPIO();

    $yellowPin = $io->getOutputPin(20);
    $redPin = $io->getOutputPin(19);
    $greenPin = $io->getOutputPin(16);

	$connection = new AMQPStreamConnection('192.168.8.100', 5672, 'bosunski', 'gabriel10');
	$channel = $connection->channel();

	$channel->queue_declare('ledAction', false, false, false, false);

	echo "[*] Waiting for messages. To exit press CTRL+C\n";

    $handleReceivedMessage = function ($msg) use ($yellowPin, $greenPin, $redPin) {
        switch ($msg) {
            case 'pending':
                $yellowPin->setValue(PinInterface::VALUE_HIGH);
                break;
            case 'passed':
                $greenPin->setValue(PinInterface::VALUE_HIGH);
                break;
            default:
                $redPin->setValue(PinInterface::VALUE_HIGH);
                break;

        }
    };

	$callback = function ($msg) use ($handleReceivedMessage) {
	    echo ' [x] Received LED instruction: ', $msg->body, PHP_EOL;

	    $handleReceivedMessage($msg->body);
	};

	$channel->basic_consume('travisStatus', '', false, true, false, false, $callback);

	while (count($channel->callbacks)) {
	    $channel->wait();
	}
