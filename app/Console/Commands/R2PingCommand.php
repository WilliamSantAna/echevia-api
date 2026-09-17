<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class R2PingCommand extends Command
{
    protected $signature = 'r2:ping';

    protected $description = 'Envia um arquivo de teste ao Cloudflare R2 e confirma a leitura';

    public function handle(): int
    {
        $disk = Storage::disk('r2');
        $path = 'healthchecks/echevia-ping.txt';
        $payload = 'echevia-r2 '.now()->toIso8601String();

        try {
            $disk->put($path, $payload);
            $stored = $disk->get($path);
        } catch (Throwable $exception) {
            $this->error('Falha ao falar com o R2: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($stored !== $payload) {
            $this->error('O R2 gravou, mas o conteúdo lido não bate.');

            return self::FAILURE;
        }

        $publicUrl = rtrim((string) config('filesystems.disks.r2.url'), '/');
        if ($publicUrl !== '') {
            $this->line('Público: '.$publicUrl.'/'.$path);
        }

        $this->info('R2 ok. Arquivo '.$path.' gravado e lido de volta.');

        return self::SUCCESS;
    }
}
