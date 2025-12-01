<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JustificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'attendance' => $this->whenLoaded('attendance', function () {
                return [
                    'id' => $this->attendance->id,
                    'status' => $this->attendance->status,
                    'student' => $this->whenLoaded('attendance.student', function () {
                        return [
                            'id' => $this->attendance->student->id,
                            'matricule' => $this->attendance->student->matricule,
                            'full_name' => $this->attendance->student->full_name,
                        ];
                    }),
                ];
            }),
            'type' => $this->type,
            'type_label' => $this->type_label,
            'reason' => $this->reason,
            'description' => $this->description,
            'documents' => $this->documents,
            'medical_certificate_path' => $this->medical_certificate_path,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge' => $this->status_badge,
            'submitted_by' => $this->whenLoaded('submittedBy', function () {
                return [
                    'id' => $this->submittedBy->id,
                    'name' => $this->submittedBy->name,
                ];
            }),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ];
            }),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'approval_notes' => $this->approval_notes,
            'admin_notes' => $this->admin_notes,
            'metadata' => $this->metadata,
            'can_be_reviewed' => $this->can_be_reviewed,
            'is_approved' => $this->is_approved,
            'is_rejected' => $this->is_rejected,
            'is_pending' => $this->is_pending,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
