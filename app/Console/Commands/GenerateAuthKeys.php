<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAuthKeys extends Command
{
    protected $signature = 'auth:keys {--force}';
    protected $description = 'Gera o par RSA usado para assinar os JWT (so se ainda nao existir)';

    public function handle(): int
    {
        $priv = config('auth_service.private_key');
        $pub = config('auth_service.public_key');
        if (is_file($priv) && is_file($pub) && ! $this->option('force')) {
            $this->info('Chaves existentes mantidas.');
            return self::SUCCESS;
        }
        @mkdir(dirname($priv), 0700, true);
        $res = openssl_pkey_new(['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $pem);
        file_put_contents($priv, $pem);
        file_put_contents($pub, openssl_pkey_get_details($res)['key']);
        chmod($priv, 0600);
        $this->info('Chaves RSA geradas.');
        return self::SUCCESS;
    }
}
