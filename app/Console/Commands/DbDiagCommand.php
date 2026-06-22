<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DbDiagCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:db-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a quick database connectivity and basic diagnostics for the admin panel';

    public function handle()
    {
        $this->info('DB diagnostics for admin panel');

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $database = $connection->getDatabaseName();

            $this->line("Driver: $driver");
            $this->line("Database: $database");

            // migrations table existence
            $migrationsExists = Schema::hasTable('migrations') ? 'yes' : 'no';
            $this->line("migrations table exists: $migrationsExists");

            // bookings count (guarded)
            try {
                $bookingsCount = DB::table('bookings')->count();
                $this->line("bookings_count: $bookingsCount");
            } catch (\Exception $e) {
                $this->error('Failed to count bookings: ' . $e->getMessage());
            }

            // list recent migration batches (if migrations table exists)
            if ($migrationsExists === 'yes') {
                $recent = DB::table('migrations')->orderBy('id', 'desc')->limit(5)->get();
                $this->line('Recent migrations:');
                foreach ($recent as $r) {
                    $this->line(' - ' . ($r->migration ?? json_encode($r)) . ' (batch ' . ($r->batch ?? '?') . ')');
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return 1;
        }
    }
}
