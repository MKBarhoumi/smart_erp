<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use App\Enums\OldInvoiceStatus;
use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\OldInvoiceAllowance;
use App\Models\OldInvoiceLine;
use App\Models\OldInvoiceTaxLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuickRandomSeeder extends Seeder
{
    private array $tunisianCities = [
        'Tunis' => '1000',
        'Ariana' => '2080',
        'Ben Arous' => '2013',
        'Manouba' => '2010',
        'Nabeul' => '8000',
        'Zaghouan' => '1100',
        'Bizerte' => '7000',
        'Béja' => '9000',
        'Jendouba' => '8100',
        'Le Kef' => '7100',
        'Siliana' => '6100',
        'Sousse' => '4000',
        'Monastir' => '5000',
        'Mahdia' => '5100',
        'Sfax' => '3000',
        'Kairouan' => '3100',
        'Kasserine' => '1200',
        'Sidi Bouzid' => '9100',
        'Gabès' => '6000',
        'Médenine' => '4100',
        'Tataouine' => '3200',
        'Gafsa' => '2100',
        'Tozeur' => '2200',
        'Kébili' => '4200',
    ];

    private array $tunisianCompanyNames = [
        'Société Tunisienne de Commerce', 'STEG Services', 'Groupe Poulina',
        'Amen Bank', 'Société Générale Tunisie', 'Monoprix Tunisie',
        'Carrefour Market', 'Magasin Général', 'UTIC',
        'Ennakl Automobiles', 'Délice Holding', 'Sotumag',
        'SFBT', 'Tunisie Télécom', 'Ooredoo Tunisie',
        'Orange Tunisie', 'Banque de Tunisie', 'Attijari Bank',
        'UIB', 'BNA', 'BIAT', 'STB', 'BH Bank',
        'Tunisair', 'Topnet', 'Hexabyte', 'GlobalNet',
        'SPT', 'Office du Commerce de Tunisie', 'OCT',
    ];

    private array $productNames = [
        'Ordinateur portable HP ProBook 450',
        'Imprimante laser Brother HL-L2350DW',
        'Écran Samsung 27" FHD',
        'Clavier mécanique Logitech G413',
        'Souris sans fil Microsoft Wireless',
        'Câble HDMI 2m haute qualité',
        'Disque dur externe Seagate 2TB',
        'Clé USB Kingston 64GB',
        'Chaise de bureau ergonomique',
        'Bureau direction bois massif',
        'Armoire métallique 2 portes',
        'Table de réunion 8 places',
        'Climatiseur Samsung 12000 BTU',
        'Photocopieur Canon iR2625',
        'Serveur Dell PowerEdge T40',
        'Switch réseau TP-Link 24 ports',
        'Routeur WiFi 6 TP-Link AX3000',
        'Téléphone IP Grandstream GXP2170',
        'Vidéoprojecteur Epson EB-S41',
        'Tableau blanc magnétique 120x90',
        'Destructeur de documents Fellowes',
        'Coffre-fort électronique 50L',
        'Caméra surveillance Hikvision',
        'Onduleur APC Back-UPS 1100VA',
        'Rack serveur 42U',
        'Disque SSD Samsung 1TB',
        'RAM DDR4 16GB Kingston',
        'Carte graphique NVIDIA GTX 1650',
        'Casque audio Jabra Evolve2 40',
        'Webcam Logitech C920 HD Pro',
    ];

    public function run(): void
    {
        // 1. Create Company Settings
        $this->createCompanySettings();

        // 2. Create Users (10 users with different roles)
        $users = $this->createUsers();
 
        // 3. Create Customers (15 realistic Tunisian companies)
        $customers = $this->createCustomers();

        // 4. Create Products (25 realistic products)
          $products = $this->createProducts();

      // 5. Create OldInvoices with lines and tax lines (30 oldinvoices)
        $oldinvoices = $this->createOldInvoicesWithDetails($users, $customers, $products);
 
       // 6. Create Payments (20 payments)
        $this->createPayments($users, $oldinvoices);

        // 7. Create Stock Movements (40 movements)
        $this->createStockMovements($users, $products, $oldinvoices);

        // 8. Create Audit Logs (30 logs)
        $this->createAuditLogs($users, $customers, $oldinvoices); 
    }

    private function createCompanySettings(): void
    {
        CompanySetting::create([
            'company_name' => 'SmartERP Solutions SARL',
            'matricule_fiscal' => '1234567A',
            'category_type' => 'A',
            'person_type' => 'M',
            'tax_office' => '001',
            'registre_commerce' => 'B12345672024',
            'legal_form' => 'SARL',
            'address_description' => 'Zone Industrielle El Agba',
            'street' => 'Rue de l\'Industrie, N°45',
            'city' => 'Tunis',
            'postal_code' => '1002',
            'country_code' => 'TN',
            'phone' => '+216 71 123 456',
            'fax' => '+216 71 123 457',
            'email' => 'contact@smarterp.tn',
            'website' => 'https://smarterp.tn',
            'bank_rib' => '07040001001001234567',
            'bank_name' => 'BIAT',
            'bank_branch_code' => '040',
            'oldinvoice_prefix' => 'FA',
            'oldinvoice_number_format' => '{prefix}/{YYYY}/{counter}',
            'next_oldinvoice_counter' => 1,
            'default_timbre_fiscal' => '1.000',
        ]);
    }

    private function createUsers(): \Illuminate\Support\Collection
    {
        $adminUser = User::factory()->admin()->create([
            'name' => 'Ahmed Ben Ali',
            'email' => 'admin@smarterp.tn',
        ]);

        $accountants = User::factory()->count(4)->create([
            'role' => 'accountant',
        ]);

        $viewers = User::factory()->viewer()->count(5)->create();

        return collect([$adminUser])->merge($accountants)->merge($viewers);
    }

    private function createCustomers(): \Illuminate\Support\Collection
    {
        $customers = collect();

        foreach (array_slice($this->tunisianCompanyNames, 0, 15) as $index => $companyName) {
            $city = array_keys($this->tunisianCities)[array_rand(array_keys($this->tunisianCities))];
            $postalCode = $this->tunisianCities[$city];

            $customers->push(Customer::create([
                'name' => $companyName,
                'identifier_type' => IdentifierType::MATRICULE_FISCAL,
                'identifier_value' => $this->generateMF(),
                'matricule_fiscal' => null,
                'category_type' => fake()->randomElement(['A', 'B', 'D', 'N', 'P']),
                'person_type' => fake()->randomElement(['C', 'M', 'N', 'P']),
                'tax_office' => fake()->numerify('###'),
                'registre_commerce' => 'B' . fake()->numerify('########'),
                'legal_form' => fake()->randomElement(['SA', 'SARL', 'SUARL', 'SNC', 'EI']),
                'address_description' => fake()->randomElement(['Zone Industrielle', 'Centre Ville', 'Quartier des Affaires', 'Zone Commerciale']),
                'street' => 'Rue ' . fake()->lastName() . ', N°' . fake()->numberBetween(1, 200),
                'city' => $city,
                'postal_code' => $postalCode,
                'country_code' => 'TN',
                'phone' => '+216 ' . fake()->numerify('## ### ###'),
                'fax' => fake()->boolean(50) ? '+216 ' . fake()->numerify('## ### ###') : null,
                'email' => strtolower(Str::slug($companyName, '.')) . '@company.tn',
                'website' => fake()->boolean(60) ? 'https://' . Str::slug($companyName) . '.tn' : null,
                'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            ]));
        }

        return $customers;
    }

    private function createProducts(): \Illuminate\Support\Collection
    {
        $products = collect();

        foreach ($this->productNames as $index => $productName) {
            $trackInventory = $index < 20; // Track inventory for first 20 products
            $currentStock = $trackInventory ? fake()->randomFloat(3, 5, 500) : 0;

            $products->push(Product::create([
                'code' => 'PRD-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'name' => $productName,
                'description' => 'Description pour ' . $productName,
                'item_lang' => 'fr',
                'unit_price' => fake()->randomFloat(3, 15, 5000),
                'unit_of_measure' => fake()->randomElement(['U', 'KG', 'M', 'L', 'H']),
                'tva_rate' => fake()->randomElement([0.00, 7.00, 13.00, 19.00]),
                'is_subject_to_timbre' => fake()->boolean(25),
                'track_inventory' => $trackInventory,
                'current_stock' => $currentStock,
                'min_stock_alert' => $trackInventory ? fake()->randomFloat(3, 2, 20) : 0,
                'is_active' => true,
            ]));
        }

        return $products;
    }

    private function createOldInvoicesWithDetails($users, $customers, $products): \Illuminate\Support\Collection
    {
        $oldinvoices = collect();
        $statuses = [
            OldInvoiceStatus::DRAFT,
            OldInvoiceStatus::VALIDATED,
            OldInvoiceStatus::SIGNED,
            OldInvoiceStatus::SUBMITTED,
            OldInvoiceStatus::ACCEPTED,
            OldInvoiceStatus::REJECTED,
        ];

        for ($i = 1; $i <= 30; $i++) {
            $customer = $customers->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $oldinvoiceDate = fake()->dateTimeBetween('-6 months', 'now');
            $dueDate = (clone $oldinvoiceDate)->modify('+30 days');

            // Create oldinvoice
            $oldinvoice = OldInvoice::create([
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'oldinvoice_number' => 'FA/' . date('Y') . '/' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'document_identifier' => 'DOC/' . date('Y') . '/' . Str::uuid()->toString(),
                'document_type_code' => DocumentTypeCode::FACTURE,
                'status' => $status,
                'oldinvoice_date' => $oldinvoiceDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'notes' => fake()->boolean(30) ? fake()->sentence() : null,
                'total_gross' => '0.000',
                'total_discount' => '0.000',
                'total_net_before_disc' => '0.000',
                'total_ht' => '0.000',
                'total_tva' => '0.000',
                'timbre_fiscal' => '0.000',
                'total_ttc' => '0.000',
            ]);

            if ($status === OldInvoiceStatus::ACCEPTED) {
                $oldinvoice->ref_ttn_val = 'TTN-' . fake()->numerify('##########');
                $oldinvoice->accepted_at = now();
            } elseif ($status === OldInvoiceStatus::REJECTED) {
                $oldinvoice->rejection_reason = fake()->randomElement([
                    'Erreur dans le matricule fiscal',
                    'Montant incorrect',
                    'Document incomplet',
                    'Signature invalide',
                ]);
            }

            // Create 2-5 oldinvoice lines
            $lineCount = rand(2, 5);
            $totalGross = '0.000';
            $totalDiscount = '0.000';
            $totalHT = '0.000';
            $taxTotals = [];

            for ($l = 1; $l <= $lineCount; $l++) {
                $product = $products->random();
                $quantity = fake()->randomFloat(3, 1, 20);
                $unitPrice = (float) $product->unit_price;
                $discountRate = fake()->randomElement([0, 5, 10, 15]);
                $lineGross = bcmul((string) $quantity, (string) $unitPrice, 3);
                $discountAmount = bcmul($lineGross, bcdiv((string) $discountRate, '100', 4), 3);
                $lineNet = bcsub($lineGross, $discountAmount, 3);
                $tvaRate = (float) $product->tva_rate;
                $tvaAmount = bcmul($lineNet, bcdiv((string) $tvaRate, '100', 4), 3);
                $lineTotal = bcadd($lineNet, $tvaAmount, 3);

                OldInvoiceLine::create([
                    'oldinvoice_id' => $oldinvoice->id,
                    'product_id' => $product->id,
                    'line_number' => $l,
                    'item_code' => $product->code,
                    'item_description' => $product->name,
                    'item_lang' => 'fr',
                    'quantity' => $quantity,
                    'unit_of_measure' => $product->unit_of_measure,
                    'unit_price' => $unitPrice,
                    'discount_rate' => $discountRate,
                    'discount_amount' => $discountAmount,
                    'line_net_amount' => $lineNet,
                    'tva_rate' => $tvaRate,
                    'tva_amount' => $tvaAmount,
                    'line_total' => $lineTotal,
                ]);

                $totalGross = bcadd($totalGross, $lineGross, 3);
                $totalDiscount = bcadd($totalDiscount, $discountAmount, 3);
                $totalHT = bcadd($totalHT, $lineNet, 3);

                // Aggregate tax by rate
                $rateKey = number_format($tvaRate, 2);
                if (!isset($taxTotals[$rateKey])) {
                    $taxTotals[$rateKey] = ['taxable' => '0.000', 'tax' => '0.000'];
                }
                $taxTotals[$rateKey]['taxable'] = bcadd($taxTotals[$rateKey]['taxable'], $lineNet, 3);
                $taxTotals[$rateKey]['tax'] = bcadd($taxTotals[$rateKey]['tax'], $tvaAmount, 3);
            }

            // Create tax lines
            $totalTVA = '0.000';
            foreach ($taxTotals as $rate => $amounts) {
                OldInvoiceTaxLine::create([
                    'oldinvoice_id' => $oldinvoice->id,
                    'tax_type_code' => 'I-1601',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $rate,
                    'taxable_amount' => $amounts['taxable'],
                    'tax_amount' => $amounts['tax'],
                ]);
                $totalTVA = bcadd($totalTVA, $amounts['tax'], 3);
            }

            // Calculate timbre (1 TND if any product subject to timbre)
            $hasTimbre = OldInvoiceLine::where('oldinvoice_id', $oldinvoice->id)
                ->whereHas('product', fn ($q) => $q->where('is_subject_to_timbre', true))
                ->exists();
            $timbre = $hasTimbre ? '1.000' : '0.000';

            $totalTTC = bcadd(bcadd($totalHT, $totalTVA, 3), $timbre, 3);

            // Update oldinvoice totals
            $oldinvoice->update([
                'total_gross' => $totalGross,
                'total_discount' => $totalDiscount,
                'total_net_before_disc' => $totalGross,
                'total_ht' => $totalHT,
                'total_tva' => $totalTVA,
                'timbre_fiscal' => $timbre,
                'total_ttc' => $totalTTC,
            ]);

            // Add allowance/charge for some oldinvoices
            if (fake()->boolean(30)) {
                $allowanceType = fake()->randomElement(['allowance', 'charge']);
                $allowanceRate = fake()->randomElement([2, 3, 5]);
                $allowanceAmount = bcmul($totalHT, bcdiv((string) $allowanceRate, '100', 4), 3);

                OldInvoiceAllowance::create([
                    'oldinvoice_id' => $oldinvoice->id,
                    'type' => $allowanceType,
                    'reason' => $allowanceType === 'allowance' ? 'Remise commerciale' : 'Frais de transport',
                    'rate' => $allowanceRate,
                    'amount' => $allowanceAmount,
                ]);
            }

            $oldinvoices->push($oldinvoice);
        }

        return $oldinvoices;
    }

    private function createPayments($users, $oldinvoices): void
    {
        // Create payments only for validated/accepted oldinvoices
        $eligibleOldInvoices = $oldinvoices->filter(function ($oldinvoice) {
            return in_array($oldinvoice->status, [
                OldInvoiceStatus::VALIDATED,
                OldInvoiceStatus::SIGNED,
                OldInvoiceStatus::SUBMITTED,
                OldInvoiceStatus::ACCEPTED,
            ]);
        });

        for ($i = 0; $i < 20; $i++) {
            if ($eligibleOldInvoices->isEmpty()) break;

            $oldinvoice = $eligibleOldInvoices->random();
            $totalTTC = (float) $oldinvoice->total_ttc;
            $paymentPercent = fake()->randomElement([30, 50, 70, 100]);
            $amount = round($totalTTC * ($paymentPercent / 100), 3);

            Payment::create([
                'oldinvoice_id' => $oldinvoice->id,
                'created_by' => $users->random()->id,
                'payment_date' => fake()->dateTimeBetween($oldinvoice->oldinvoice_date, 'now')->format('Y-m-d'),
                'amount' => number_format($amount, 3, '.', ''),
                'method' => fake()->randomElement(['cash', 'bank_transfer', 'cheque', 'effect']),
                'reference' => fake()->randomElement([
                    'CHQ-' . fake()->numerify('######'),
                    'VIR-' . fake()->numerify('########'),
                    'ESP-' . fake()->numerify('####'),
                    null,
                ]),
                'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            ]);
        }
    }

    private function createStockMovements($users, $products, $oldinvoices): void
    {
        $trackableProducts = $products->filter(fn ($p) => $p->track_inventory);

        for ($i = 0; $i < 40; $i++) {
            if ($trackableProducts->isEmpty()) break;

            $product = $trackableProducts->random();
            $currentStock = (float) $product->current_stock;
            $movementType = fake()->randomElement(['in', 'out', 'adjustment']);
            $quantity = fake()->randomFloat(3, 1, 50);

            if ($movementType === 'out' && $quantity > $currentStock) {
                $quantity = max(1, $currentStock * 0.5);
            }

            $stockBefore = $currentStock;
            $stockAfter = match ($movementType) {
                'in' => $currentStock + $quantity,
                'out' => max(0, $currentStock - $quantity),
                'adjustment' => $quantity,
            };

            StockMovement::create([
                'product_id' => $product->id,
                'oldinvoice_id' => $movementType === 'out' ? $oldinvoices->random()->id : null,
                'type' => $movementType,
                'quantity' => number_format($quantity, 3, '.', ''),
                'stock_before' => number_format($stockBefore, 3, '.', ''),
                'stock_after' => number_format($stockAfter, 3, '.', ''),
                'reason' => match ($movementType) {
                    'in' => fake()->randomElement(['Réception fournisseur', 'Retour client', 'Inventaire initial']),
                    'out' => fake()->randomElement(['Vente client', 'Consommation interne', 'Casse']),
                    'adjustment' => fake()->randomElement(['Correction inventaire', 'Ajustement comptable']),
                },
                'performed_by' => $users->random()->id,
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);

            // Update product stock
            $product->update(['current_stock' => number_format($stockAfter, 3, '.', '')]);
        }
    }

    private function createAuditLogs($users, $customers, $oldinvoices): void
    {
        $actions = ['created', 'updated', 'deleted', 'validated', 'signed', 'submitted'];

        for ($i = 0; $i < 30; $i++) {
            $auditableType = fake()->randomElement(['OldInvoice', 'Customer', 'Product', 'Payment']);
            $auditable = match ($auditableType) {
                'OldInvoice' => $oldinvoices->random(),
                'Customer' => $customers->random(),
                default => $oldinvoices->random(),
            };

            AuditLog::create([
                'user_id' => $users->random()->id,
                'action' => fake()->randomElement($actions),
                'auditable_type' => 'App\\Models\\' . $auditableType,
                'auditable_id' => $auditable->id,
                'old_values' => fake()->boolean(50) ? json_encode(['status' => 'draft']) : null,
                'new_values' => json_encode(['status' => fake()->randomElement(['validated', 'signed', 'accepted'])]),
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }
    }

    private function generateMF(): string
    {
        $digits = fake()->numerify('#######');
        $letter = fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z']);
        $category = fake()->randomElement(['A', 'B', 'D', 'N', 'P']);
        $person = fake()->randomElement(['C', 'M', 'N', 'P']);
        return "{$digits}{$letter}{$category}{$person}000";
    }
}
