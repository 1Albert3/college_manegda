import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Invoice {
  id: number;
  student_id: number;
  reference: string;
  amount: number;
  due_date: string;
  status: 'pending' | 'paid' | 'overdue' | 'cancelled';
  description: string;
  created_at: string;
  student?: {
    first_name: string;
    last_name: string;
    matricule: string;
  };
}

export interface Payment {
  id: number;
  invoice_id: number;
  amount: number;
  payment_date: string;
  payment_method: string;
  reference: string;
  status: 'pending' | 'validated' | 'cancelled';
}

export interface FinanceStats {
  total_invoiced: number;
  total_paid: number;
  total_pending: number;
  total_overdue: number;
  collection_rate: number;
}

@Injectable({
  providedIn: 'root'
})
export class FinanceService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  // ============ INVOICES ============
  getInvoices(params?: any): Observable<any> {
    return this.http.get(`${this.apiUrl}/finance/invoices`, { params });
  }

  getInvoice(id: string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/finance/invoices/${id}`);
  }

  createInvoice(data: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/finance/invoices`, data);
  }

  cancelInvoice(id: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/finance/invoices/${id}/cancel`, {});
  }

  downloadInvoicePdf(id: string | number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/finance/invoices/${id}/print`, { responseType: 'blob' });
  }

  getUnpaidInvoices(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/finance/payments/unpaid`);
  }

  getInvoiceStats(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/finance/payments/stats`);
  }

  // ============ PAYMENTS ============
  getPayments(params?: any): Observable<any> {
    return this.http.get(`${this.apiUrl}/finance/payments`, { params });
  }

  createPayment(data: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/finance/payments`, data);
  }

  validatePayment(id: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/finance/payments/${id}/validate`, {});
  }

  rejectPayment(id: string, reason: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/finance/payments/${id}/reject`, { reason });
  }

  downloadReceipt(id: string): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/finance/payments/${id}/receipt`, { responseType: 'blob' });
  }

  // ============ FEE TYPES ============
  getFeeTypes(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/finance/fee-types`);
  }

  getStudents(): Observable<any> {
      return this.http.get<any>(`${this.apiUrl}/dashboard/secretary/students`);
  }

  getDashboardStats(): Observable<any> {
      return this.http.get<any>(`${this.apiUrl}/dashboard/accounting`);
  }
}
