<?php

namespace App\Messaging;

use App\Models\Pesanan;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PesananDikonfirmasiPublisher
{
    public function publish(Pesanan $pesanan): void
    {
        $connection = new AMQPStreamConnection(
            (string) config('services.rabbitmq.host'),
            (int) config('services.rabbitmq.port'),
            (string) config('services.rabbitmq.user'),
            (string) config('services.rabbitmq.password'),
            (string) config('services.rabbitmq.vhost'),
        );

        $channel = $connection->channel();

        try {
            $exchange = (string) config(
                'services.rabbitmq.exchange',
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

            $eventId = (string) Str::uuid();

            $payload = [
                'event_id' => $eventId,
                'event_name' => 'PesananDikonfirmasi',
                'occurred_at' => now()->toIso8601String(),
                'data' => [
                    'kode_pesanan' => $pesanan->kode_pesanan,
                    'kanal' => $pesanan->kanal,
                    'nama_pelanggan' => $pesanan->nama_pelanggan,
                    'status' => $pesanan->status,
                    'total' => $pesanan->total,
                    'items' => $pesanan->items
                        ->map(fn ($item): array => [
                            'kode_menu' => $item->kode_menu,
                            'nama_menu' => $item->nama_menu,
                            'jumlah' => $item->jumlah,
                            'harga_satuan' => $item->harga_satuan,
                            'subtotal' => $item->subtotal,
                        ])
                        ->values()
                        ->all(),
                ],
            ];

            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2,
                    'message_id' => $eventId,
                    'type' => 'PesananDikonfirmasi',
                    'timestamp' => time(),
                    'app_id' => 'layanan-pemesanan',
                ],
            );

            $channel->confirm_select();
            $channel->basic_publish(
                $message,
                $exchange,
                $routingKey,
            );
            $channel->wait_for_pending_acks_returns(5.0);
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}
