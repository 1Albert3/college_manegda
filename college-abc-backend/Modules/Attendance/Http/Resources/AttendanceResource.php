<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'matricule' => $this->student->matricule,
                    'first_name' => $this->student->first_name,
                    'last_name' => $this->student->last_name,
                    'full_name' => $this->student->full_name,
                ];
            }),
            'session' => $this->whenLoaded('session', function () {
                return [
                    'id' => $this->session->id,
                    'name' => $this->session->name,
                    'session_date' => $this->session->session_date->format('Y-m-d'),
                    'subject' => $this->whenLoaded('session.subject', function () {
                        return [
                            'id' => $this->session->subject->id,
                            'name' => $this->session->subject->name,
                        ];
                    }),
                    'class' => $this->whenLoaded('session.class', function () {
                        return [
                            'id' => $this->session->class->id,
                            'name' => $this->session->class->name,
                        ];
                    }),
                ];
            }),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge' => $this->status_badge,
            'check_in_time' => $this->check_in_time?->format('H:i:s'),
            'check_out_time' => $this->check_out_time?->format('H:i:s'),
            'duration' => $this->duration,
            'minutes_late' => $this->minutes_late,
            'is_late' => $this->is_late,
            'justified' => $this->justified,
            'absence_reason' => $this->absence_reason,
            'absence_notes' => $this->absence_notes,
            'admin_approved' => $this->admin_approved,
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ];
            }),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'recorded_by' => $this->whenLoaded('recordedBy', function () {
                return [
                    'id' => $this->recordedBy->id,
                    'name' => $this->recordedBy->name,
                ];
            }),
            'recorded_at' => $this->recorded_at?->format('Y-m-d H:i:s'),
            'teacher_notes' => $this->teacher_notes,
            'admin_notes' => $this->admin_notes,
            'justification' => $this->whenLoaded('justification', function () {
                return new JustificationResource($this->justification);
            }),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
