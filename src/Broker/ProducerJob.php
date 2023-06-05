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
    public function send(string $exchange, string $routingKey, array $content = [])
    {
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            env("RABBITMQ_HOST"),
            env("RABBITMQ_PORT"),
            env("RABBITMQ_USER"),
            env("RABBITMQ_PASSWORD")
        );
        $channel = $connection->channel();

        $channel->exchange_declare($exchange, 'direct', false, false, true);

        $rabbitMsg = new \PhpAmqpLib\Message\AMQPMessage(json_encode($content, JSON_UNESCAPED_UNICODE));
        $channel->basic_publish($rabbitMsg, $exchange, $routingKey);
        $channel->close();
        $connection->close();

    }
}
