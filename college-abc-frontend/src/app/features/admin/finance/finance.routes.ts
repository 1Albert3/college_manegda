import { Routes } from '@angular/router';
import { AdminFinanceComponent } from './finance.component';
import { InvoicesManagementComponent } from './invoices/invoices.component';

export const FINANCE_ROUTES: Routes = [
  {
    path: '',
    component: AdminFinanceComponent,
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: 'dashboard', loadComponent: () => import('./dashboard/finance-dashboard.component').then(m => m.FinanceDashboardComponent) },
      { path: 'invoices', loadComponent: () => import('./invoices/invoices.component').then(m => m.InvoicesManagementComponent) },
      { path: 'payments', loadComponent: () => import('./payments/payments.component').then(m => m.PaymentsHistoryComponent) },
      { path: 'settings', loadComponent: () => import('./settings/fee-settings.component').then(m => m.FeeSettingsComponent) },
    ]
  }
];
