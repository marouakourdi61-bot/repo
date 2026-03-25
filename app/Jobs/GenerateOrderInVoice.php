<?php

use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use App\Models\Product;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class GenerateOrderInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public int $tries = 3;
    public int $timeout = 60;



    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    
    {
        $order = Order::with('products')->find($this->orderId);
        if (!$order)
            return;


        Mail::to('client@test.com')->send(new OrderConfirmation($order));



        $product = Product::find($request->product_id);
        if ($product) {
            $product->decrement('stock', 1);
        }



        $order->markAsProcessed();
    }
}