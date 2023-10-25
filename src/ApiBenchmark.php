<?php

namespace GuzzleBenchmarkTool;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ApiBenchmark
{
    private $client;
    private $url;
    private $method;
    private $body;
    private $formParams;
    private $headers;
    private $rounds;
    private $concurrency;
    private $responses = [];

    public function __construct($url, $method = 'GET', $body = '', $formParams = [], $headers = [], $rounds = 100, $concurrency = 100)
    {
        $this->client = new Client();
        $this->url = $url;
        $this->method = $method;
        $this->body = $body;
        $this->formParams = $formParams;
        $this->headers = $headers;
        $this->rounds = $rounds;
        $this->concurrency = $concurrency;
    }

    public function run()
    {
        for ($i = 0; $i < $this->rounds; $i++) {
            $promises = [];

            $options = [
                'headers' => $this->headers,
                'body' => $this->body,
                'on_stats' => function (\GuzzleHttp\TransferStats $stats) {
                    $this->responses[] = [
                        'transfer_time' => $stats->getTransferTime(),
                        'response' => $stats->hasResponse() ? (string) $stats->getResponse()->getBody() : 'No response',
                    ];
                }
            ];

            if (!empty($this->formParams)) {
                $options['form_params'] = $this->formParams;
            }

            $startRound = microtime(true);
            for ($j = 0; $j < $this->concurrency; $j++) {
                $promises[] = $this->client->requestAsync($this->method, $this->url, $options);
            }

            Promise\settle($promises)->wait();
            $endRound = microtime(true);

            echo "Round " . ($i + 1) . " completed in " . ($endRound - $startRound) . " seconds.\n";
        }
    }

    public function dumpResponsesTo($logFilePath)
    {
        $file = fopen($logFilePath, 'w');
        foreach ($this->responses as $i => $response) {
            fwrite($file, sprintf("%s [%s] -> %s\n", $i + 1, $response['transfer_time'], $response['response']));
        }
        fclose($file);
    }

    public function printResults()
    {
        $transactionTimes = array_column($this->responses, 'transfer_time');
        $totalTime = array_sum($transactionTimes);
        $transactionRate = count($transactionTimes) / $totalTime;
        $longestTransaction = max($transactionTimes);
        $shortestTransaction = min($transactionTimes);

        echo "\n-------------------------RESULTS-------------------------\n";
        echo "Transaction rate: $transactionRate t/s\n";
        echo "Longest transaction: $longestTransaction s\n";
        echo "Shortest transaction: $shortestTransaction s\n";
    }
}