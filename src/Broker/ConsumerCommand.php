<?php

namespace Kalebb4f6\RabbitAmqpDriverQueue\Broker;

use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

abstract class ConsumerCommand extends Command
{
    protected $queue = 'default';
    protected $exchange = '';
    protected $routingKey = '';
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
        $channel->exchange_declare($this->exchange, 'direct');
        
        $channel->queue_bind($this->queue, $this->exchange, $this->routingKey);

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
}
