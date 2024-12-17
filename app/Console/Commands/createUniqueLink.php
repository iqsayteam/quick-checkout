<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;
class createUniqueLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createUniqueLink';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Unique Link';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Request $request)
    {
        $CheckoutController = new CheckoutController;
        $CheckoutController->createUniqueLink($request); 
    }
}
