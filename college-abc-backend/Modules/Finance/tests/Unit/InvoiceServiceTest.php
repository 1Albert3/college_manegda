<?php

namespace Modules\Finance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\FeeType;
use Modules\Finance\Entities\Scholarship;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
    }

    /** @test */
    public function it_can_generate_an_invoice()
    {
        // Arrange
        $this->actingAs($user = \Modules\Core\Entities\User::factory()->create());
        
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        // Create enrollment for student
        $classRoom = \Modules\Academic\Entities\ClassRoom::factory()->create();
        \Modules\Student\Entities\Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $invoiceData = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'period' => 'annuel',
        ];

        // Act
        $invoice = $this->invoiceService->generateInvoice($invoiceData);

        // Assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertEquals('annuel', $invoice->period);
        $this->assertEquals($user->id, $invoice->generated_by);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers()
    {
        // Arrange
        $this->actingAs(\Modules\Core\Entities\User::factory()->create());
        
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        $classRoom = \Modules\Academic\Entities\ClassRoom::factory()->create();
        \Modules\Student\Entities\Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $data = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'period' => 'trimestriel_1',
        ];

        // Act
        $invoice1 = $this->invoiceService->generateInvoice($data);
        
        $data['period'] = 'trimestriel_2'; // Different period
        $invoice2 = $this->invoiceService->generateInvoice($data);

        // Assert
        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }

    /** @test */
    public function it_prevents_duplicate_invoices_for_same_period()
    {
        // Arrange
        $this->actingAs(\Modules\Core\Entities\User::factory()->create());
        
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        $classRoom = \Modules\Academic\Entities\ClassRoom::factory()->create();
        \Modules\Student\Entities\Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $data = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'period' => 'annuel',
        ];

        // Act
        $this->invoiceService->generateInvoice($data);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Une facture existe déjà pour cette période');
        
        $this->invoiceService->generateInvoice($data);
    }

    /** @test */
    public function it_calculates_total_due_correctly()
    {
        // Arrange
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        $classRoom = \Modules\Academic\Entities\ClassRoom::factory()->create();
        \Modules\Student\Entities\Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        // Create fee types
        FeeType::factory()->create([
            'amount' => 100000,
            'is_active' => true,
            'is_mandatory' => true,
        ]);
        FeeType::factory()->create([
            'amount' => 50000,
            'is_active' => true,
            'is_mandatory' => true,
        ]);

        // Act
        $calculation = $this->invoiceService->calculateTotalDue(
            $student->id,
            $academicYear->id,
            'annuel'
        );

        // Assert
        $this->assertEquals(150000, $calculation['total_amount']);
        $this->assertEquals(0, $calculation['total_paid']);
        $this->assertEquals(150000, $calculation['remaining_due']);
    }

    /** @test */
    public function it_applies_scholarships_to_calculation()
    {
        // Arrange
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        $classRoom = \Modules\Academic\Entities\ClassRoom::factory()->create();
        \Modules\Student\Entities\Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        FeeType::factory()->create([
            'amount' => 100000,
            'is_active' => true,
            'is_mandatory' => true,
        ]);

        // Create 50% scholarship
        Scholarship::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'percentage' => 50,
            'status' => 'active',
        ]);

        // Act
        $calculation = $this->invoiceService->calculateTotalDue(
            $student->id,
            $academicYear->id,
            'annuel'
        );

        // Assert
        $this->assertEquals(100000, $calculation['total_amount']);
        $this->assertEquals(50000, $calculation['total_discount']);
        $this->assertEquals(50000, $calculation['net_amount']);
    }

    /** @test */
    public function it_can_issue_an_invoice()
    {
        // Arrange
        $invoice = Invoice::factory()->create(['status' => 'brouillon']);

        // Act
        $issuedInvoice = $this->invoiceService->issueInvoice($invoice, false);

        // Assert
        $this->assertEquals('emise', $issuedInvoice->status);
        $this->assertNotNull($issuedInvoice->issue_date);
    }

    /** @test */
    public function it_cannot_issue_an_already_issued_invoice()
    {
        // Arrange
        $invoice = Invoice::factory()->create(['status' => 'emise']);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Seules les factures en brouillon peuvent être émises');
        
        $this->invoiceService->issueInvoice($invoice);
    }

    /** @test */
    public function it_can_get_unpaid_invoices()
    {
        // Arrange
        Invoice::factory()->count(3)->create(['status' => 'emise', 'due_amount' => 50000]);
        Invoice::factory()->count(2)->create(['status' => 'payee', 'due_amount' => 0]);

        // Act
        $unpaidInvoices = $this->invoiceService->getUnpaidInvoices();

        // Assert
        $this->assertCount(3, $unpaidInvoices);
    }

    /** @test */
    public function it_can_cancel_an_invoice()
    {
        // Arrange
        $invoice = Invoice::factory()->create(['status' => 'emise', 'paid_amount' => 0]);
        $reason = 'Erreur de génération';

        // Act
        $cancelledInvoice = $this->invoiceService->cancelInvoice($invoice, $reason);

        // Assert
        $this->assertEquals('annulee', $cancelledInvoice->status);
        $this->assertStringContainsString($reason, $cancelledInvoice->notes);
    }

    /** @test */
    public function it_cannot_cancel_partially_paid_invoice()
    {
        // Arrange
        $invoice = Invoice::factory()->create(['status' => 'partiellement_payee', 'paid_amount' => 10000]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible d\'annuler une facture partiellement payée');
        
        $this->invoiceService->cancelInvoice($invoice);
    }
}
