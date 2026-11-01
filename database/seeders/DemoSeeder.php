<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\Product;
use App\Models\ProductOffering;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Admin Demo',
            'password' => bcrypt('password123'),
            'phone_number' => '081234567890',
            'role' => 'admin',
        ]);

        $clients = collect([
            ['name' => 'Zam Demo', 'email' => 'zam.demo@gmail.com', 'phone_number' => '081111111111'],
            ['name' => 'Budi Demo', 'email' => 'budi.demo@gmail.com', 'phone_number' => '082222222222'],
            ['name' => 'Sari Demo', 'email' => 'sari.demo@gmail.com', 'phone_number' => '083333333333'],
        ])->map(fn (array $client) => User::updateOrCreate(
            ['email' => $client['email']],
            [...$client, 'password' => bcrypt('password123'), 'role' => 'client'],
        ));

        $products = collect([
            ['name' => 'ChatGPT', 'slug' => 'chatgpt', 'description' => 'Akses ChatGPT sharing.'],
            ['name' => 'Claude', 'slug' => 'claude', 'description' => 'Akses Claude sharing.'],
        ])->mapWithKeys(fn (array $product) => [$product['slug'] => Product::updateOrCreate(
            ['slug' => $product['slug']],
            [...$product, 'status' => true],
        )]);

        $types = collect([
            ['code' => 'private', 'name' => 'Private', 'max_users' => 1],
            ['code' => 'shared_5', 'name' => 'Shared 5 Users', 'max_users' => 5],
        ])->mapWithKeys(fn (array $type) => [$type['code'] => AccountType::updateOrCreate(
            ['code' => $type['code']],
            [...$type, 'status' => true],
        )]);

        $offerings = [
            ['product' => 'chatgpt', 'type' => 'private', 'email' => 'chatgpt.demo@gmail.com', 'price' => 30000],
            ['product' => 'chatgpt', 'type' => 'shared_5', 'email' => 'chatgpt.shared@gmail.com', 'price' => 50000],
            ['product' => 'claude', 'type' => 'private', 'email' => 'claude.demo@gmail.com', 'price' => 170000],
            ['product' => 'claude', 'type' => 'shared_5', 'email' => 'claude.shared@gmail.com', 'price' => 75000],
        ];

        foreach ($offerings as $item) {
            $start = now()->subDays(3);
            $offering = ProductOffering::updateOrCreate(
                ['product_id' => $products[$item['product']]->id, 'account_type_id' => $types[$item['type']]->id],
                ['email' => $item['email'], 'price' => $item['price'], 'duration_days' => 30, 'starts_at' => $start, 'expires_at' => $start->copy()->addDays(30), 'status' => true],
            );

            foreach ($clients->take($item['type'] === 'private' ? 1 : 3) as $index => $client) {
                $transactionDate = now()->subDays(3 + $index * 4);
                $startsAt = $transactionDate->copy();
                $expiresAt = $startsAt->copy()->addDays(30);
                $subscription = Subscription::updateOrCreate(
                    ['user_id' => $client->id, 'product_offering_id' => $offering->id, 'starts_at' => $startsAt],
                    ['status' => $expiresAt->isFuture() ? 'active' : 'expired', 'expires_at' => $expiresAt],
                );
                Transaction::updateOrCreate(
                    ['number' => 'DEMO-'.$offering->id.'-'.$client->id.'-'.$transactionDate->format('Ymd')],
                    ['user_id' => $client->id, 'cashier_id' => $admin->id, 'product_offering_id' => $offering->id, 'subscription_id' => $subscription->id, 'product_name' => $products[$item['product']]->name, 'total_amount' => $item['price'], 'duration_days' => 30, 'starts_at' => $startsAt, 'expires_at' => $expiresAt, 'status' => 'paid', 'paid_at' => $transactionDate],
                );
            }
        }

        $this->command?->info('Demo data berhasil dibuat. Login: admin@gmail.com / password123');
    }
}
