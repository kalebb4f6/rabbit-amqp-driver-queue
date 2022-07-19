<?php
namespace Kalebb4f6\RabbitAmqpDriverQueue\Broker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class ProducerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $exchange = '';
    /**
     * Fire the job.
     *
     * @return void
     */
    public function send(string $queueName, string $routingKey, array $content = [], string $exchange = '')
    {
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            env("RABBITMQ_HOST"),
            env("RABBITMQ_PORT"),
            env("RABBITMQ_USER"),
            env("RABBITMQ_PASSWORD")
        );
        $channel = $connection->channel();

        $channel->exchange_declare($exchange, 'direct', false, false, true);
        $channel->queue_bind($queueName, $exchange, $routingKey);

        $rabbitMsg = new \PhpAmqpLib\Message\AMQPMessage(json_encode($content));
        $channel->basic_publish($rabbitMsg, $exchange, $routingKey);
        $channel->close();
        $connection->close();

    }
}
