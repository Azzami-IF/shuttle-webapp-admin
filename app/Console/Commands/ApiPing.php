<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiClient;

class ApiPing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:api-ping {path=/}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping configured external API and show status';

    public function handle(ApiClient $client)
    {
        $path = $this->argument('path');
        $this->info('Pinging API: ' . config('api.base_url') . '/' . ltrim($path, '/'));
        $result = $client->get($path);
        $this->line('Status: ' . $result['status']);
        $this->line('Body:');
        if (is_array($result['body'])) {
            $this->line(json_encode($result['body'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        } else {
            $this->line((string) $result['body']);
        }
        return 0;
    }
}
