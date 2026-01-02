<?php

namespace Modules\Finance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\FeeType;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    /** @test */
    public function it_can_record_a_payment()
    {
        // Arrange
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create(['amount' => 50000]);
        $academicYear = AcademicYear::factory()->create();

        $paymentData = [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'academic_year_id' => $academicYear->id,
            'amount' => 50000,
            'payment_method' => 'especes',
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $payment = $this->paymentService->recordPayment($paymentData);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotNull($payment->receipt_number);
        $this->assertEquals(50000, $payment->amount);
        $this->assertEquals('especes', $payment->payment_method);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'student_id' => $student->id,
            'amount' => 50000,
        ]);
    }

    /** @test */
    public function it_generates_unique_receipt_numbers()
    {
        // Arrange
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $data = [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'academic_year_id' => $academicYear->id,
            'amount' => 10000,
            'payment_method' => 'especes',
        ];

        // Act
        $payment1 = $this->paymentService->recordPayment($data);
        $payment2 = $this->paymentService->recordPayment($data);

        // Assert
        $this->assertNotEquals($payment1->receipt_number, $payment2->receipt_number);
    }

    /** @test */
    public function it_validates_payment_amount()
    {
        // Arrange
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $data = [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'academic_year_id' => $academicYear->id,
            'amount' => 0, // Invalid
            'payment_method' => 'especes',
        ];

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Le montant doit être supérieur à 0');
        
        $this->paymentService->recordPayment($data);
    }

    /** @test */
    public function it_calculates_student_balance_correctly()
    {
        // Arrange
        $student = Student::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        
        // Create a payment
        $feeType = FeeType::factory()->create(['amount' => 50000]);
        Payment::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'fee_type_id' => $feeType->id,
            'amount' => 30000,
            'status' => 'valide',
        ]);

        // Act
        $balance = $this->paymentService->calculateBalance($student->id, $academicYear->id);

        // Assert
        $this->assertEquals($student->id, $balance['student_id']);
        $this->assertEquals($academicYear->id, $balance['academic_year_id']);
        $this->assertEquals(30000, $balance['summary']['total_paid']);
        $this->assertEquals(1, $balance['payments_count']);
    }

    /** @test */
    public function it_can_validate_a_pending_payment()
    {
        // Arrange
        $this->actingAs($user = \Modules\Core\Entities\User::factory()->create());
        
        $payment = Payment::factory()->create(['status' => 'en_attente']);

        // Act
        $validatedPayment = $this->paymentService->validatePayment($payment);

        // Assert
        $this->assertEquals('valide', $validatedPayment->status);
        $this->assertEquals($user->id, $validatedPayment->validated_by);
        $this->assertNotNull($validatedPayment->validated_at);
    }

    /** @test */
    public function it_cannot_validate_an_already_validated_payment()
    {
        // Arrange
        $this->actingAs(\Modules\Core\Entities\User::factory()->create());
        
        $payment = Payment::factory()->create(['status' => 'valide']);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Seuls les paiements en attente peuvent être validés');
        
        $this->paymentService->validatePayment($payment);
    }

    /** @test */
    public function it_can_cancel_a_payment()
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'valide']);
        $reason = 'Erreur de saisie';

        // Act
        $cancelledPayment = $this->paymentService->cancelPayment($payment, $reason);

        // Assert
        $this->assertEquals('annule', $cancelledPayment->status);
        $this->assertStringContainsString($reason, $cancelledPayment->notes);
    }
}
