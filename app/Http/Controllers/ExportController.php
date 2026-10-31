<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\B2bDocument;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function download(Request $request, string $resource, string $format): Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'docx'], true), 404);
        $rows = $this->rows($request, $resource);
        $filename = $resource.'-'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            return Pdf::loadView('exports.table', ['title' => $this->title($resource), 'rows' => $rows])
                ->download($filename.'.pdf');
        }

        if ($format === 'xlsx') {
            return Excel::download(new ArrayExport($rows), $filename.'.xlsx');
        }

        $word = new PhpWord;
        $section = $word->addSection();
        $section->addTitle($this->title($resource), 1);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9E2EC', 'cellMargin' => 80]);
        foreach ($rows as $row) {
            $tableRow = $table->addRow();
            foreach ($row as $cell) {
                $tableRow->addCell(2200)->addText((string) ($cell ?? ''));
            }
        }
        $tempPath = tempnam(sys_get_temp_dir(), 'hubmarchand-export-').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($tempPath);

        return response()->download($tempPath, $filename.'.docx')->deleteFileAfterSend(true);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function rows(Request $request, string $resource): array
    {
        if ($resource === 'merchants') {
            abort_unless($request->user()->isSuperAdmin(), 403);

            return Store::with(['user', 'subscriptionPlan'])->latest()->get()
                ->map(fn (Store $store): array => [$store->name, $store->user?->name, $store->user?->email, $store->subscriptionPlan?->name ?? 'Aucun forfait', $store->status])
                ->prepend(['Boutique', 'Responsable', 'Email', 'Forfait', 'État'])->all();
        }

        if ($resource === 'plans') {
            abort_unless($request->user()->isSuperAdmin(), 403);

            return SubscriptionPlan::where('is_active', true)->latest()->get()
                ->map(fn (SubscriptionPlan $plan): array => [$plan->name, $plan->monthly_price, $plan->annual_price, $plan->product_limit ?? 'Illimité', $plan->ai_included ? 'Oui' : 'Non'])
                ->prepend(['Forfait', 'Prix mensuel', 'Prix annuel', 'Produits', 'IA incluse'])->all();
        }

        $store = $request->user()->currentStore();
        $data = match ($resource) {
            'products' => $store->products()->latest()->get()->map(fn (Product $product): array => [$product->name, $product->sku, $product->category, $product->stock_quantity, $product->unit_price])->prepend(['Produit', 'SKU', 'Catégorie', 'Stock', 'Prix'])->all(),
            'customers' => $store->customers()->latest()->get()->map(fn (Customer $customer): array => [$customer->name, $customer->email, $customer->phone, $customer->segment])->prepend(['Client', 'Email', 'Téléphone', 'Segment'])->all(),
            'orders' => $store->salesOrders()->latest('ordered_at')->get()->map(fn (SalesOrder $order): array => [$order->reference, $order->customer_name, $order->source, $order->status, $order->total_amount, $order->ordered_at?->format('d/m/Y H:i')])->prepend(['Référence', 'Client', 'Source', 'Statut', 'Montant', 'Date'])->all(),
            'billing' => $store->b2bDocuments()->with('customer')->latest('issued_at')->get()->map(fn (B2bDocument $document): array => [$document->number, $document->customer?->name, $document->type, $document->status, $document->total_amount, $document->issued_at?->format('d/m/Y')])->prepend(['Numéro', 'Client', 'Type', 'Statut', 'Montant', 'Date'])->all(),
            'delivery' => $store->salesOrders()->whereNotNull('fulfillment_status')->latest('ordered_at')->get()->map(fn (SalesOrder $order): array => [$order->reference, $order->customer_name, $order->fulfillment_status, $order->delivery_person, $order->total_amount])->prepend(['Référence', 'Client', 'Statut livraison', 'Livreur', 'Montant'])->all(),
            default => abort(404),
        };

        return $data;
    }

    private function title(string $resource): string
    {
        return match ($resource) {
            'products' => 'Catalogue produits',
            'customers' => 'Clients',
            'orders' => 'Commandes et ventes',
            'billing' => 'Documents B2B',
            'delivery' => 'Livraisons',
            'merchants' => 'Marchands',
            'plans' => 'Forfaits',
            default => 'Export HubMarchand',
        };
    }
}
