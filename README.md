🚀 Laravel Order Processing: Sync vs. Async
A step-by-step technical guide for implementing queues.

🛠 Phase 1: Shared Infrastructure
Run these steps first. They are required for both versions.

1. Database Migrations
Create the core tables for products and orders.

Terminal Commands:

Bash

# Generate the migrations
php artisan make:migration create_products_table --create=products
php artisan make:migration create_orders_table --create=orders
php artisan make:migration create_order_product_table --create=order_product

# Run the migration
php artisan migrate

Schema Overview:

Products: name, price, stock (default: 10).

Orders: user_id, processed_at (nullable timestamp).

Pivot (order_product): order_id, product_id, quantity, price.

2. Eloquent Models & Relationships
Define how Orders and Products interact.

app/Models/Order.php

PHP

class Order extends Model {
    protected $fillable = ['user_id', 'processed_at'];
    protected $casts = ['processed_at' => 'datetime'];

    public function products() {
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }

    public function markAsProcessed() {
        $this->update(['processed_at' => now()]);
    }
}

3. Mailable (The Notification)
The email sent to users upon purchase.

app/Mail/OrderConfirmation.php

PHP

// Command: php artisan make:mail OrderConfirmation --markdown=emails.order_confirmation

public function build() {
    return $this->subject("Confirmation commande #{$this->order->id}")
                ->markdown('emails.order_confirmation');
}

🏎 Phase 2: Implementation Logic
Choose your path. Most production apps use Option B.

Option A: Synchronous (Without Queues)
The Problem: The user’s browser “spins” while the server sends emails. If the mail server is slow, the user waits.

OrderController.php

PHP

public function store(Request $request) {
    // 1. Create Order
    $order = Order::create(['user_id' => 1]);
    
    // 2. Attach Products (Simplified logic)
    $order->products()->attach($request->product_id, ['quantity' => 1, 'price' => 20]);

    // ❌ BLOCKING: The user is stuck waiting here
    Mail::to('client@test.com')->send(new OrderConfirmation($order));

    // 3. Update Stock & Mark Done
    $order->products->each->decrement('stock', 1);
    $order->markAsProcessed();

    return redirect()->route('orders.show', $order);
}

Option B: Asynchronous (With Queues) ✅
The Solution: The controller hands the work to a “Job” and returns a response instantly.

1. Create the Job

Bash

php artisan make:job GenerateOrderInvoice

2. Move the Logic to the Job (app/Jobs/GenerateOrderInvoice.php)

PHP

public function handle(): void {
    $order = Order::with('products')->find($this->orderId);

    // ✅ Processed in the background
    Mail::to('client@test.com')->send(new OrderConfirmation($order));
    
    foreach ($order->products as $product) {
        $product->decrement('stock', $product->pivot->quantity);
    }

    $order->markAsProcessed();
}

3. Dispatch from the Controller

PHP

public function store(Request $request) {
    $order = Order::create(['user_id' => 1]);
    
    // ✅ Dispatched to queue (takes ~2ms)
    GenerateOrderInvoice::dispatch($order->id);

    // ✅ Redirects user immediately
    return redirect()->route('orders.show', $order);
}

🚦 Phase 3: Testing the Difference
1. Preparation (Async Only)
Update your .env to use the database driver.

Extrait de code

QUEUE_CONNECTION=database

Run the queue table migration:

Bash

php artisan queue:table
php artisan migrate

2. Execution
Open three terminal windows:

Terminal 1: php artisan serve (The Web App)

Terminal 2: php artisan queue:work (The Background Worker)

Terminal 3: tail -f storage/logs/laravel.log (To monitor activity)

##############################################################################################################################################################################################################################################

📦 Simple Order & Notification API
A lightweight Laravel-based API for handling product orders, inventory management, and automated email confirmations. Built for high-speed local development using SQLite.

🚀 Quick Start (Local Setup)
Follow these steps to get the API running on your machine in under 2 minutes.

1. Prerequisites
PHP 8.2+ (Ensure extension=pdo_sqlite is enabled in your php.ini)

Composer

SQLite

2. Installation
Bash
# Clone the repository (or enter the project folder)
cd LIVECODING-Queues

# Install dependencies
composer install

# Create your environment file
cp .env.example .env

# Create the SQLite database file
touch database/database.sqlite
3. Database & Seeding
Initialize the database and create a test product (ID: 1) with stock.

Bash
php artisan migrate:fresh --seed
4. Run the Server
Bash
php artisan serve
The API will be available at: http://127.0.0.1:8000

🛠 API Documentation
Create New Order
Processes a purchase, decrements stock, and triggers an email.

URL: /api/orders

Method: POST

Content-Type: application/json

Request Body:

JSON
{
    "product_id": 1
}
Success Response:

Code: 201 Created

Content: { "message": "Done!" }

📧 Testing Emails
This project is configured to use the Log Driver for emails. This means no real emails are sent to the internet (perfect for offline demos).

Send a request via Postman.

Open storage/logs/laravel.log.

Scroll to the bottom to see the fully rendered Order Confirmation email.

📈 Performance Notes
Synchronous Execution: Currently, the API processes the email before returning a response.

Observed Latency: ~500ms - 1s (includes database writes and mail rendering).

Scalability: For production, it is recommended to implement Laravel Queues to reduce latency to <50ms.

🧪 Troubleshooting
500 Error? Check storage/logs/laravel.log for PHP errors.

Driver Not Found? Ensure SQLite is enabled in your php.ini.

Stock Empty? Run php artisan db:seed to refill the product shelf.


##############################################################################################################################################################################################################################################




🏗️ How I Built This (From Scratch)
If you want to recreate this workflow, follow these exact steps:

1. Database & Models
First, we create the blueprint for our data. We need Products, Orders, and a Pivot Table to connect them (since an order can have many products).

Bash
# Create Models and Migrations
php artisan make:model Product -m
php artisan make:model Order -m
php artisan make:migration create_order_product_table
Product Model: Added $fillable = ['name', 'price', 'stock'].

Order Model: Defined a belongsToMany(Product::class) relationship with withPivot('quantity', 'price').

2. The API Route
We expose a single endpoint in routes/api.php to handle the incoming purchase request.

PHP
use App\Http\Controllers\OrderController;
Route::post('/orders', [OrderController::class, 'store']);
3. The Order Controller (The "Brain")
I generated the controller using php artisan make:controller OrderController. The store method performs four critical tasks:

Validation: Captures the product_id.

Persistence: Saves the Order to the database and attaches the product.

Communication: Dispatches the OrderConfirmation email.

Inventory: Decrements the product stock by 1 using $product->decrement('stock').

4. The Email System
Laravel makes professional emails easy. I generated a "Mailable" class and a Markdown template:

Bash
php artisan make:mail OrderConfirmation --markdown=emails.order_confirmation
Data Passing: I passed the $order object into the Mailable constructor so the email knows exactly what was bought.

Environment: In .env, I set MAIL_MAILER=log to intercept outgoing emails and save them to storage/logs/laravel.log for instant local testing.

5. Seeding for Testing
To ensure the API works immediately, I added a default product to database/seeders/DatabaseSeeder.php:

PHP
Product::create([
    'name' => 'Mechanical Keyboard',
    'price' => 100,
    'stock' => 50
]);
🛠 Tech Stack Used
Framework: Laravel 11

Database: SQLite (File-based for zero-config setup)

Testing: Postman

Architecture: RESTful API with Synchronous Mail Processing











🛠️ Phase 1: The Data Layer (Models & Migrations)
1. The Product Model
File: app/Models/Product.php

Added: The $fillable array to allow mass assignment of data.

PHP
protected $fillable = ['name', 'price', 'stock'];
2. The Order Model
File: app/Models/Order.php

Added: The relationship to link orders to products.

PHP
public function products() {
    return $this->belongsToMany(Product::class)->withPivot('quantity', 'price');
}
3. The Pivot Table Migration
File: database/migrations/xxxx_create_order_product_table.php

Modified: Added the foreign keys to connect the two tables.

PHP
$table->foreignId('order_id')->constrained()->onDelete('cascade');
$table->foreignId('product_id')->constrained()->onDelete('cascade');
$table->integer('quantity');
$table->decimal('price', 8, 2);
🔌 Phase 2: The Logic Layer (Controller & Routes)
4. The API Route
File: routes/api.php

Added: The endpoint for Postman to hit.

PHP
use App\Http\Controllers\OrderController;
Route::post('/orders', [OrderController::class, 'store']);
5. The Order Controller
File: app/Http/Controllers/OrderController.php

Added: The store method. This is the "Engine."

PHP
public function store(Request $request) {
    // 1. Create Order
    $order = Order::create(['user_id' => 1]); 

    // 2. Attach Product
    $order->products()->attach($request->product_id, [
        'quantity' => 1, 
        'price' => 100
    ]);

    // 3. Send Email (Synchronous)
    Mail::to('test@example.com')->send(new OrderConfirmation($order));

    // 4. Update Inventory
    $product = Product::find($request->product_id);
    $product->decrement('stock', 1);

    return response()->json(['message' => 'Done!'], 201);
}
✉️ Phase 3: The Communication Layer (Mail)
6. The Mailable Class
File: app/Mail/OrderConfirmation.php

Modified: Passed the $order into the constructor.

PHP
public $order;

public function __construct(Order $order) {
    $this->order = $order;
}
7. The Email View
File: resources/views/emails/order_confirmation.blade.php

Added: The HTML/Markdown content for the user to see.

HTML
# Order Confirmed!
Thanks for your purchase. Your Order ID is: {{ $order->id }}
⚙️ Phase 4: The Environment Configuration
8. The .env File
File: .env

Modified: Forced Laravel to save emails to a local text file instead of trying to hit a real server.

Extrait de code
DB_CONNECTION=sqlite
MAIL_MAILER=log
9. The Database Seeder
File: database/seeders/DatabaseSeeder.php

Added: Created the initial "stock" so the API doesn't fail on the first request.

PHP
\App\Models\Product::create([
    'id' => 1,
    'name' => 'Mechanical Keyboard',
    'price' => 100,
    'stock' => 50,
]);
# livecoding
# repo
