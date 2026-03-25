<?php

namespace App\Http\Controllers;

// 1. CRITICAL IMPORTS: These tell the controller where to find your files
use App\Models\Order;
use App\Models\Product;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function store(Request $request)
    {

    $request->validate([
        
        'products'            => 'required|array',
        'products.*.id'       => 'required|exists:products,id',
        'products.*.quantity' => 'required|integer|min:1',
    ]);
        // 1. Create the Order in the 'orders' table
        // We hardcode user_id 1 for the demo
        $order = Order::create(['user_id' => 1]);
        
        // 2. Attach the Product to the Order (Pivot Table)
        // This creates a row in the 'order_product' table
        $order->products()->attach($request->product_id, [
            'quantity' => 1, 
            'price' => 20
        ]);
        GenerateOrderInvoice::dispatch($order->id);

        // 3. THE "SLOW" PART: Sending the email
        // Because MAIL_MAILER=log, it writes to storage/logs/laravel.log
        Mail::to('client@test.com')->send(new OrderConfirmation($order));

        // 4. Update the Inventory
        // We find the product and subtract 1 from the 'stock' column
        
        // 5. Respond to Postman
        return response()->json([
            'message' => 'Order processed successfully!',
            'order_id' => $order->id,
            'remaining_stock' => $product->stock ?? 'N/A'
        ], 201);
    }
}