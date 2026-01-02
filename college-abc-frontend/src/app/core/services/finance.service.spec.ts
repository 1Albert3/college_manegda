import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { FinanceService, Invoice, Payment, FinanceStats } from './finance.service';
import { environment } from '../../../environments/environment';

describe('FinanceService', () => {
  let service: FinanceService;
  let httpMock: HttpTestingController;

  const mockInvoice: Invoice = {
    id: 1,
    student_id: 1,
    reference: 'INV-2024-0001',
    amount: 50000,
    due_date: '2024-12-31',
    status: 'pending',
    description: 'Frais de scolarité',
    created_at: '2024-01-15T10:00:00Z',
    student: {
      first_name: 'Jean',
      last_name: 'KABORE',
      matricule: 'STD-2024-0001'
    }
  };

  const mockPayment: Payment = {
    id: 1,
    invoice_id: 1,
    amount: 25000,
    payment_date: '2024-01-20',
    payment_method: 'cash',
    reference: 'PAY-2024-0001',
    status: 'validated'
  };

  const mockStats: FinanceStats = {
    total_invoiced: 100000,
    total_paid: 75000,
    total_pending: 25000,
    total_overdue: 5000,
    collection_rate: 75
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [FinanceService]
    });

    service = TestBed.inject(FinanceService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  describe('Invoice Operations', () => {
    it('should be created', () => {
      expect(service).toBeTruthy();
    });

    it('should get invoices list', () => {
      const mockResponse = {
        success: true,
        data: [mockInvoice],
        meta: { current_page: 1, last_page: 1, total: 1 }
      };

      service.getInvoices().subscribe(response => {
        expect(response.data).toHaveSize(1);
        expect(response.data[0].reference).toBe('INV-2024-0001');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should get single invoice', () => {
      service.getInvoice(1).subscribe(invoice => {
        expect(invoice.id).toBe(1);
        expect(invoice.reference).toBe('INV-2024-0001');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/1`);
      expect(req.request.method).toBe('GET');
      req.flush(mockInvoice);
    });

    it('should create invoice', () => {
      const newInvoiceData = {
        student_id: 1,
        amount: 60000,
        description: 'Frais d\'inscription',
        due_date: '2024-12-31'
      };

      service.createInvoice(newInvoiceData).subscribe(invoice => {
        expect(invoice.amount).toBe(60000);
        expect(invoice.description).toBe('Frais d\'inscription');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newInvoiceData);
      req.flush({ ...mockInvoice, ...newInvoiceData });
    });

    it('should issue invoice', () => {
      service.issueInvoice(1).subscribe(response => {
        expect(response.success).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/1/issue`);
      expect(req.request.method).toBe('POST');
      req.flush({ success: true, message: 'Invoice issued successfully' });
    });

    it('should cancel invoice', () => {
      service.cancelInvoice(1).subscribe(response => {
        expect(response.success).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/1/cancel`);
      expect(req.request.method).toBe('POST');
      req.flush({ success: true, message: 'Invoice cancelled' });
    });

    it('should download invoice PDF', () => {
      const mockBlob = new Blob(['PDF content'], { type: 'application/pdf' });

      service.downloadInvoicePdf(1).subscribe(blob => {
        expect(blob).toBeInstanceOf(Blob);
        expect(blob.type).toBe('application/pdf');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/1/pdf`);
      expect(req.request.method).toBe('GET');
      req.flush(mockBlob);
    });

    it('should get unpaid invoices', () => {
      service.getUnpaidInvoices().subscribe(invoices => {
        expect(invoices).toHaveSize(1);
        expect(invoices[0].status).toBe('pending');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/unpaid/list`);
      expect(req.request.method).toBe('GET');
      req.flush([mockInvoice]);
    });

    it('should get invoice statistics', () => {
      service.getInvoiceStats().subscribe(stats => {
        expect(stats.total_invoiced).toBe(100000);
        expect(stats.collection_rate).toBe(75);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/statistics/summary`);
      expect(req.request.method).toBe('GET');
      req.flush(mockStats);
    });
  });

  describe('Payment Operations', () => {
    it('should get payments list', () => {
      const mockResponse = {
        success: true,
        data: [mockPayment]
      };

      service.getPayments().subscribe(response => {
        expect(response.data).toHaveSize(1);
        expect(response.data[0].reference).toBe('PAY-2024-0001');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create payment', () => {
      const newPaymentData = {
        invoice_id: 1,
        amount: 30000,
        payment_method: 'bank_transfer',
        payment_date: '2024-01-25'
      };

      service.createPayment(newPaymentData).subscribe(payment => {
        expect(payment.amount).toBe(30000);
        expect(payment.payment_method).toBe('bank_transfer');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newPaymentData);
      req.flush({ ...mockPayment, ...newPaymentData });
    });

    it('should validate payment', () => {
      service.validatePayment(1).subscribe(response => {
        expect(response.success).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments/1/validate`);
      expect(req.request.method).toBe('POST');
      req.flush({ success: true, message: 'Payment validated' });
    });

    it('should cancel payment', () => {
      service.cancelPayment(1).subscribe(response => {
        expect(response.success).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments/1/cancel`);
      expect(req.request.method).toBe('POST');
      req.flush({ success: true, message: 'Payment cancelled' });
    });

    it('should download receipt', () => {
      const mockBlob = new Blob(['Receipt content'], { type: 'application/pdf' });

      service.downloadReceipt(1).subscribe(blob => {
        expect(blob).toBeInstanceOf(Blob);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments/1/receipt`);
      expect(req.request.method).toBe('GET');
      req.flush(mockBlob);
    });

    it('should get payment statistics', () => {
      service.getPaymentStats().subscribe(stats => {
        expect(stats).toBeDefined();
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/payments/statistics/summary`);
      expect(req.request.method).toBe('GET');
      req.flush(mockStats);
    });
  });

  describe('Student-specific Operations', () => {
    it('should get student payments', () => {
      service.getStudentPayments(1).subscribe(payments => {
        expect(payments).toHaveSize(1);
        expect(payments[0].id).toBe(1);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students/1/payments`);
      expect(req.request.method).toBe('GET');
      req.flush([mockPayment]);
    });

    it('should get student balance', () => {
      const mockBalance = {
        total_invoiced: 50000,
        total_paid: 30000,
        balance: 20000
      };

      service.getStudentBalance(1).subscribe(balance => {
        expect(balance.balance).toBe(20000);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students/1/balance`);
      expect(req.request.method).toBe('GET');
      req.flush(mockBalance);
    });
  });

  describe('Fee Types', () => {
    it('should get fee types', () => {
      const mockFeeTypes = [
        { id: 1, name: 'Frais de scolarité', amount: 50000, is_active: true },
        { id: 2, name: 'Frais d\'inscription', amount: 25000, is_active: true }
      ];

      service.getFeeTypes().subscribe(feeTypes => {
        expect(feeTypes).toHaveSize(2);
        expect(feeTypes[0].name).toBe('Frais de scolarité');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/fee-types`);
      expect(req.request.method).toBe('GET');
      req.flush(mockFeeTypes);
    });
  });

  describe('Error Handling', () => {
    it('should handle HTTP errors', () => {
      service.getInvoices().subscribe({
        next: () => fail('Should have failed'),
        error: (error) => {
          expect(error.status).toBe(500);
        }
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices`);
      req.flush({ message: 'Server error' }, { status: 500, statusText: 'Internal Server Error' });
    });

    it('should handle 404 errors', () => {
      service.getInvoice(999).subscribe({
        next: () => fail('Should have failed'),
        error: (error) => {
          expect(error.status).toBe(404);
        }
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/invoices/999`);
      req.flush({ message: 'Invoice not found' }, { status: 404, statusText: 'Not Found' });
    });
  });
});