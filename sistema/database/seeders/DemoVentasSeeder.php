<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DetailSale;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoVentasSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $role = Role::firstOrCreate(
                ['name' => 'Administrador'],
                ['description' => 'Acceso total al sistema']
            );
            $seller = User::firstOrCreate(
                ['email' => 'demo@sanjuan.com'],
                ['role_id' => $role->id, 'name' => 'Vendedor Demo', 'dni' => '99999999', 'password' => 'password']
            );

            $medicines = collect([
                ['category' => 'Analgésicos', 'medicine' => 'Paracetamol 500 mg', 'price' => 5.00, 'stock' => 100],
                ['category' => 'Antibióticos', 'medicine' => 'Amoxicilina 500 mg', 'price' => 18.50, 'stock' => 80],
                ['category' => 'Antiinflamatorios', 'medicine' => 'Ibuprofeno 400 mg', 'price' => 9.90, 'stock' => 75],
                ['category' => 'Antigripales', 'medicine' => 'Clorfenamina Compuesta', 'price' => 12.50, 'stock' => 60],
                ['category' => 'Vitaminas', 'medicine' => 'Multivitamínico Adulto', 'price' => 35.00, 'stock' => 50],
            ])->map(function (array $data): Medicine {
                $category = Category::firstOrCreate(
                    ['name' => $data['category']],
                    ['description' => 'Categoría de demostración para el Dashboard']
                );

                return Medicine::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $data['medicine']],
                    ['price' => $data['price'], 'stock' => $data['stock']]
                );
            });

            $customers = collect([
                ['dni' => '90000001', 'name' => 'Cliente Demo Norte', 'phone' => '900000001'],
                ['dni' => '90000002', 'name' => 'Cliente Demo Sur', 'phone' => '900000002'],
                ['dni' => '90000003', 'name' => 'Cliente Demo Centro', 'phone' => '900000003'],
                ['dni' => '90000004', 'name' => 'Cliente Demo Este', 'phone' => '900000004'],
                ['dni' => '90000005', 'name' => 'Cliente Demo Oeste', 'phone' => '900000005'],
            ])->map(fn (array $data): Customer => Customer::firstOrCreate(['dni' => $data['dni']], $data));

            // Crea una venta por cada uno de los últimos siete días, incluido hoy.
            foreach (range(6, 0) as $daysAgo) {
                $date = now()->subDays($daysAgo)->setTime(10 + ($daysAgo % 5), 15, 0);
                $customer = $customers->get($daysAgo % $customers->count());
                $this->createSale($seller, $customer, $medicines, $date, 1 + ($daysAgo % 3));
            }
        });
    }

    private function createSale(User $seller, Customer $customer, $medicines, Carbon $date, int $amount): void
    {
        $createdAt = $date->copy();
        $sale = Sale::firstOrCreate(
            ['customer_id' => $customer->id, 'user_id' => $seller->id, 'date' => $date->toDateTimeString()],
            ['total' => 0, 'igv' => 0]
        );

        if (! $sale->wasRecentlyCreated) {
            return;
        }

        // Se sincronizan ambas fechas para que el Dashboard pueda consultar created_at y mostrar date.
        $sale->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        $total = 0;

        foreach ($medicines->values() as $index => $medicine) {
            $lineAmount = $amount + ($index % 2);
            $subtotal = round((float) $medicine->price * $lineAmount, 2);
            $total += $subtotal;
            DetailSale::firstOrCreate(
                ['sale_id' => $sale->id, 'medicine_id' => $medicine->id],
                ['amount' => $lineAmount, 'price_unit' => $medicine->price, 'subtotal' => $subtotal]
            );
        }

        $sale->update([
            'total' => round($total, 2),
            'igv' => round($total - $total / (1 + Sale::IGV_RATE), 2),
        ]);
    }
}
