<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingDashboardController extends Controller
{
    public function index()
    {
        // 1. Totaux
        $totalInvoiced = Invoice::sum('montant_ttc');
        $totalCollected = Payment::where('statut', 'valide')->sum('montant');
        $totalPending = Invoice::where('statut', 'emise')->orWhere('statut', 'partiellement_payee')->sum(DB::raw('montant_ttc - montant_paye'));

        $unpaidCount = Invoice::where('statut', 'emise')->orWhere('statut', 'partiellement_payee')->count();
        $pendingPayments = Payment::where('statut', 'en_attente')->count();
        $transactionCount = Payment::where('statut', 'valide')->count();

        // 2. Revenue par mois (basé sur paiements validés - année courante)
        $payments = Payment::where('statut', 'valide')
            ->whereYear('date_paiement', date('Y'))
            ->get();

        $monthlyRevenue = $payments->groupBy(function ($d) {
            return $d->date_paiement->format('M'); // Jan, Feb...
        })->map(function ($rows) {
            return $rows->sum('montant');
        });

        // Mapping basique pour le frontend (qui attend 'month' => 'Sep', 'value' => ...)
        // On va envoyer ce qu'on a.
        $chartData = [];
        foreach ($monthlyRevenue as $month => $amount) {
            $chartData[] = ['month' => $month, 'value' => $amount];
        }


        // 3. Répartition des Frais
        $breakdown = Invoice::select('type', DB::raw('sum(montant_ttc) as total'))
            ->groupBy('type')
            ->get();

        $grandTotal = $breakdown->sum('total');
        $feeBreakdown = $breakdown->map(function ($item) use ($grandTotal) {
            return [
                'name' => ucfirst($item->type),
                'amount' => $item->total,
                'percentage' => $grandTotal > 0 ? round(($item->total / $grandTotal) * 100) : 0,
                'color' => $this->getColorForType($item->type)
            ];
        });

        // 4. Payment Methods Breakdown
        $methods = Payment::select('mode_paiement', DB::raw('sum(montant) as total'), DB::raw('count(*) as count'))
            ->where('statut', 'valide')
            ->groupBy('mode_paiement')
            ->get();

        $paymentMethodsBreakdown = $methods->map(function ($item) {
            return [
                'label' => ucfirst(str_replace('_', ' ', $item->mode_paiement)),
                'amount' => $item->total,
                'count' => $item->count,
                'icon' => $this->getIconForMethod($item->mode_paiement),
                'color' => $this->getColorForMethod($item->mode_paiement)
            ];
        });

        // 5. Dernières Transactions
        $recent = Payment::with('invoice')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Load student names roughly
        $recent->transform(function ($p) {
            $studentName = "Inconnu";
            if ($p->invoice) {
                // Try to find student
                $db = $p->invoice->student_database;
                $sid = $p->invoice->student_id;

                if (in_array($db, ['mp', 'school_mp'])) $s = StudentMP::find($sid);
                elseif (in_array($db, ['college', 'school_college'])) $s = StudentCollege::find($sid);
                elseif (in_array($db, ['lycee', 'school_lycee'])) $s = StudentLycee::find($sid);
                else $s = null;

                if ($s) $studentName = $s->nom . ' ' . $s->prenoms;
            }

            return [
                'type' => 'payment',
                'description' => 'Paiement ' . ucfirst($p->mode_paiement),
                'student' => $studentName,
                'date' => $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '-',
                'amount' => $p->montant,
                'method' => $p->mode_paiement
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'finance' => [
                    'monthly_revenue' => $totalCollected, // Total collected global
                    'collection_rate' => $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100) : 0,
                    'total_pending' => $totalPending,
                    'unpaid_count' => $unpaidCount,
                    'pending_payments' => $pendingPayments,
                    'transaction_count' => $transactionCount, // Added
                    'critical_unpaid' => Invoice::where('date_echeance', '<', now()->subDays(90))->where('statut', '!=', 'payee')->count(),
                    'warning_unpaid' => Invoice::where('date_echeance', '<', now()->subDays(30))->where('statut', '!=', 'payee')->count(),
                ],
                'monthly_data' => $chartData,
                'fee_breakdown' => $feeBreakdown,
                'payment_methods' => $paymentMethodsBreakdown, // Added
                'recent_transactions' => $recent
            ]
        ]);
    }

    private function getColorForType($type)
    {
        $colors = [
            'scolarite' => '#10B981',
            'inscription' => '#3B82F6',
            'cantine' => '#EC4899',
            'transport' => '#F59E0B',
            'fournitures' => '#8B5CF6'
        ];
        return $colors[$type] ?? '#9CA3AF';
    }

    private function getIconForMethod($method)
    {
        $icons = [
            'especes' => 'pi pi-money-bill',
            'mobile_money' => 'pi pi-mobile',
            'virement' => 'pi pi-building',
            'cheque' => 'pi pi-file',
            'carte' => 'pi pi-credit-card'
        ];
        return $icons[$method] ?? 'pi pi-credit-card';
    }

    private function getColorForMethod($method)
    {
        $colors = [
            'especes' => '#F59E0B',
            'mobile_money' => '#10B981',
            'virement' => '#3B82F6',
            'cheque' => '#8B5CF6',
            'carte' => '#EC4899'
        ];
        return $colors[$method] ?? '#6B7280';
    }
}
