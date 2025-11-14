<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Services\Blockchain\ProductCertificateService;
use Illuminate\Console\Command;

class MintExistingProducts extends Command
{
    protected $signature = 'blockchain:mint-existing
                            {--variant= : Specific variant ID}
                            {--all : Mint all variants without certificates}';

    protected $description = 'Mint blockchain certificates for existing products';

    public function handle(ProductCertificateService $service)
    {
        if ($this->option('variant')) {
            // Mint specific variant
            $variant = ProductVariant::with('product.brand')->find($this->option('variant'));

            if (!$variant) {
                $this->error('❌ Variant not found');
                return 1;
            }

            $this->info("📦 Minting certificate for variant: {$variant->id} ({$variant->sku})");

            $cert = $service->mintCertificate($variant);

            if ($cert) {
                $this->info("✅ Certificate minted successfully!");
                $this->line("   Hash: {$cert->certificate_hash}");
                $this->line("   IPFS: {$cert->ipfs_url}");
            } else {
                $this->error("❌ Failed to mint certificate");
                return 1;
            }
        } elseif ($this->option('all')) {
            // Mint all variants without certificates
            $variants = ProductVariant::with('product.brand')
                ->whereDoesntHave('blockchainCertificate')
                ->get();

            if ($variants->isEmpty()) {
                $this->info('✅ All variants already have certificates!');
                return 0;
            }

            $this->info("📦 Found {$variants->count()} variants without certificates");
            $this->newLine();

            $bar = $this->output->createProgressBar($variants->count());
            $bar->start();

            $success = 0;
            $failed = 0;
            $errors = [];

            foreach ($variants as $variant) {
                try {
                    $cert = $service->mintCertificate($variant);
                    if ($cert) {
                        $success++;
                    } else {
                        $failed++;
                        $errors[] = "Variant {$variant->id}: Failed to mint";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Variant {$variant->id}: {$e->getMessage()}";
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Success: {$success}");
            if ($failed > 0) {
                $this->error("❌ Failed: {$failed}");
                if ($this->option('verbose')) {
                    foreach ($errors as $error) {
                        $this->line("   - {$error}");
                    }
                }
            }
        } else {
            $this->error('❌ Please specify --variant=ID or --all');
            $this->line('');
            $this->line('Examples:');
            $this->line('  php artisan blockchain:mint-existing --variant=123');
            $this->line('  php artisan blockchain:mint-existing --all');
            return 1;
        }

        return 0;
    }
}
