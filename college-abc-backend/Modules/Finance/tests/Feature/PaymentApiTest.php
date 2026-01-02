<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\FeeType;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_payments()
    {
        // Arrange
        Payment::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->getJson('/api/v1/payments');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'receipt_number',
                            'amount',
                            'payment_method',
                            'status',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_a_payment()
    {
        // Arrange
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $paymentData = [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'academic_year_id' => $academicYear->id,
            'amount' => 50000,
            'payment_method' => 'especes',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/v1/payments', $paymentData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'receipt_number',
                        'amount',
                    ]
                ]);

        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'amount' => 50000,
            'payment_method' => 'especes',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_payment()
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/v1/payments', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'student_id',
                    'fee_type_id',
                    'academic_year_id',
                    'amount',
                    'payment_method',
                ]);
    }

    /** @test */
    public function it_validates_payment_amount_is_positive()
    {
        // Arrange
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson('/api/v1/payments', [
                            'student_id' => $student->id,
                            'fee_type_id' => $feeType->id,
                            'academic_year_id' => $academicYear->id,
                            'amount' => 0,
                            'payment_method' => 'especes',
                        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_can_show_a_payment()
    {
        // Arrange
        $payment = Payment::factory()->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->getJson("/api/v1/payments/{$payment->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'receipt_number',
                        'amount',
                        'student',
                        'fee_type',
                    ]
                ]);
    }

    /** @test */
    public function it_can_validate_a_pending_payment()
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'en_attente']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson("/api/v1/payments/{$payment->id}/validate");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Paiement validé avec succès',
                ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'valide',
        ]);
    }

    /** @test */
    public function it_can_cancel_a_payment()
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'valide']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
                        ->postJson("/api/v1/payments/{$payment->id}/cancel", [
                            'reason' => 'Erreur de saisie'
                        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Paiement annulé avec succès',
                ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'annule',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Act
        $response = $this->getJson('/api/v1/payments');

        // Assert
        $response->assertStatus(401);
    }
}
