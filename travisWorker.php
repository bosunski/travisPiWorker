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

$connection = new AMQPStreamConnection('157.230.183.190', 5672, 'bosunski', 'gabriel10');
$channel = $connection->channel();

echo "[*] Waiting for messages. To exit press CTRL+C\n";

$resetLEDs = function () use ($yellowPin, $greenPin, $redPin) {
    $yellowPin->setValue(PinInterface::VALUE_LOW);
    $greenPin->setValue(PinInterface::VALUE_LOW);
    $redPin->setValue(PinInterface::VALUE_LOW);
};


$handleReceivedMessage = function ($msg) use ($yellowPin, $greenPin, $redPin, $resetLEDs) {
    switch ($msg) {
        case 'started':
            $resetLEDs();
            $yellowPin->setValue(PinInterface::VALUE_HIGH);
            break;
        case 'passed':
            $resetLEDs();
            $greenPin->setValue(PinInterface::VALUE_HIGH);
            break;
        default:
            $resetLEDs();
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
