<?php

namespace Kalebb4f6\RabbitAmqpDriverQueue\Broker;

use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

abstract class ConsumerCommand extends Command
{
    protected $queue = 'default';
    protected $exchanges = [];
    protected $exchange = '';
    protected $routingKeys = [];
    protected $consumerTag = '';

    abstract public function messageReceived(AMQPMessage $msg, $channel, $connection);


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            env("RABBITMQ_HOST"),
            env("RABBITMQ_PORT"),
            env("RABBITMQ_USER"),
            env("RABBITMQ_PASSWORD")
        );
        $channel = $connection->channel();
        $channel->queue_declare($this->queue, false, true, false, false);

        if (empty($this->exchanges)) {
            $this->onlyOneExchange($channel);
        } else {
            $this->loadExchanges($channel);
        }
        $channel->basic_consume($this->queue,
            $this->consumerTag,
            false,
            false,
            false,
            false,
            fn (AMQPMessage $msg) => $this->messageReceived($msg, $channel, $connection));

        while ($channel->is_open()) {
            $channel->wait();
        }

        return 0;
    }

    private function onlyOneExchange($channel)
    {
        $channel->exchange_declare($this->exchange, 'direct');

        foreach($this->routingKeys as $routingKey)
            $channel->queue_bind($this->queue, $this->exchange, $routingKey);

    }

    private function loadExchanges($channel)
    {
        foreach($this->exchanges as $exchange => $routingKeys) {
            
            $channel->exchange_declare($exchange, 'direct');
            
            foreach($routingKeys as $routingKey)
                $channel->queue_bind($this->queue, $exchange, $routingKey);
        }
    }
}
