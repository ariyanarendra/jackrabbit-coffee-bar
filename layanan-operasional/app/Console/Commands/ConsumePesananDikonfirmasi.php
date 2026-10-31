<?php

namespace App\Console\Commands;

use App\Actions\TanganiPesananDikonfirmasiAction;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use UnexpectedValueException;

class ConsumePesananDikonfirmasi extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rabbitmq:consume-pesanan-dikonfirmasi
                            {--once : Berhenti setelah memproses satu pesan}';

    /**
     * @var string
     */
    protected $description = 'Memproses event PesananDikonfirmasi dari RabbitMQ';

    public function handle(
        TanganiPesananDikonfirmasiAction $action,
    ): int {
        $connection = new AMQPStreamConnection(
            (string) config('services.rabbitmq.host'),
            (int) config('services.rabbitmq.port'),
            (string) config('services.rabbitmq.user'),
            (string) config('services.rabbitmq.password'),
            (string) config('services.rabbitmq.vhost'),
        );

        $channel = $connection->channel();
        $exitCode = self::SUCCESS;

        try {
            $exchange = (string) config(
                'services.rabbitmq.exchange',
            );

            $queue = (string) config(
                'services.rabbitmq.queue',
            );

            $routingKey = (string) config(
                'services.rabbitmq.routing_key',
            );

            $channel->exchange_declare(
                $exchange,
                'topic',
                false,
                true,
                false,
            );

            $channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false,
            );

            $channel->queue_bind(
                $queue,
                $exchange,
                $routingKey,
            );

            $channel->basic_qos(
                0,
                1,
                false,
            );

            $consumerTag = $channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $message) use (
                    $action,
                    &$exitCode,
                ): void {
                    try {
                        $payload = json_decode(
                            $message->getBody(),
                            true,
                            512,
                            JSON_THROW_ON_ERROR,
                        );

                        if (! is_array($payload)) {
                            throw new UnexpectedValueException(
                                'Payload event harus berupa object JSON.',
                            );
                        }

                        $pesanan = $action->handle($payload);

                        $message->ack();

                        $this->info(
                            "Pesanan {$pesanan->kode_pesanan} diterima.",
                        );
                    } catch (Throwable $exception) {
                        $exitCode = self::FAILURE;

                        $message->nack(
                            false,
                            false,
                        );

                        report($exception);

                        $this->error(
                            "Event gagal diproses: {$exception->getMessage()}",
                        );
                    }
                },
            );

            if ($this->option('once')) {
                try {
                    $channel->wait(
                        null,
                        false,
                        5,
                    );
                } catch (AMQPTimeoutException) {
                    $this->warn(
                        'Tidak ada event yang tersedia dalam antrean.',
                    );
                }

                $channel->basic_cancel($consumerTag);

                return $exitCode;
            }

            $this->info(
                'Consumer aktif. Tekan Ctrl+C untuk berhenti.',
            );

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            return $exitCode;
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}
