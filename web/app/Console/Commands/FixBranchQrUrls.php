<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;

class FixBranchQrUrls extends Command
{
    protected $signature = 'fix:branch-qr-urls';
    
    protected $description = 'Fix branch QR URLs from localhost to correct domain';

    public function handle()
    {
        $this->info('Fixing branch QR URLs...');
        
        $branches = Branch::whereNotNull('payment_qr_url')
            ->where('payment_qr_url', 'LIKE', '%localhost%')
            ->get();
            
        if ($branches->isEmpty()) {
            $this->info('No branches found with localhost QR URLs.');
            return 0;
        }
        
        $updated = 0;
        
        foreach ($branches as $branch) {
            $oldUrl = $branch->payment_qr_url;
            $newUrl = str_replace('http://localhost', 'http://127.0.0.1:8000', $oldUrl);
            
            $branch->update(['payment_qr_url' => $newUrl]);
            
            $this->line("Updated Branch {$branch->name}: {$oldUrl} → {$newUrl}");
            $updated++;
        }
        
        $this->info("Successfully updated {$updated} branch QR URLs.");
        
        return 0;
    }
}