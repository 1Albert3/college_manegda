<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\User;
use App\Models\SchoolYear;

class ScheduleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) $schoolYear = SchoolYear::first();
        if (!$schoolYear) return;

        // S'assurer qu'il y a des matières
        $subjects = [
            ['code' => 'MATH', 'name' => 'Mathématiques', 'color' => 'blue'],
            ['code' => 'FR', 'name' => 'Français', 'color' => 'purple'],
            ['code' => 'PC', 'name' => 'Physique-Chimie', 'color' => 'indigo'],
            ['code' => 'SVT', 'name' => 'SVT', 'color' => 'emerald'],
            ['code' => 'ANG', 'name' => 'Anglais', 'color' => 'orange'],
            ['code' => 'HG', 'name' => 'Hist-Géo', 'color' => 'amber'],
            ['code' => 'EPS', 'name' => 'EPS', 'color' => 'rose'],
        ];

        foreach ($subjects as $s) {
            Subject::updateOrCreate(['code' => $s['code']], $s);
        }

        $classroom = Classroom::first();
        if (!$classroom) {
            $classroom = Classroom::create(['name' => '6ème A', 'level' => '6eme', 'capacity' => 50, 'is_active' => true]);
        }

        $defaultTeacher = User::where('role', 'teacher')->first();

        $teacherMath = User::where('email', 'jb.solen@manegda.bf')->first() ?? $defaultTeacher;
        $teacherFr = User::where('email', 'm.traore@manegda.bf')->first() ?? $defaultTeacher;
        $teacherPC = User::where('email', 'p.kabore@manegda.bf')->first() ?? $defaultTeacher;
        $teacherEng = User::where('email', 's.johnson@manegda.bf')->first() ?? $defaultTeacher;

        $subjectsList = Subject::all()->keyBy('code');

        $scheduleData = [
            ['day_of_week' => 'monday', 'start_time' => '07:00', 'end_time' => '09:00', 'subject_id' => $subjectsList['MATH']->id, 'teacher_id' => $teacherMath->id, 'room' => 'Salle 01'],
            ['day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '12:00', 'subject_id' => $subjectsList['FR']->id, 'teacher_id' => $teacherFr->id, 'room' => 'Salle 01'],
            ['day_of_week' => 'tuesday', 'start_time' => '08:00', 'end_time' => '10:00', 'subject_id' => $subjectsList['PC']->id, 'teacher_id' => $teacherPC->id, 'room' => 'Labo 1'],
            ['day_of_week' => 'tuesday', 'start_time' => '10:00', 'end_time' => '12:00', 'subject_id' => $subjectsList['ANG']->id, 'teacher_id' => $teacherEng->id, 'room' => 'Salle 01'],
            ['day_of_week' => 'wednesday', 'start_time' => '07:00', 'end_time' => '10:00', 'subject_id' => $subjectsList['MATH']->id, 'teacher_id' => $teacherMath->id, 'room' => 'Salle 01'],
            ['day_of_week' => 'thursday', 'start_time' => '08:00', 'end_time' => '10:00', 'subject_id' => $subjectsList['SVT']->id, 'teacher_id' => $teacherPC->id, 'room' => 'Labo 1'],
            ['day_of_week' => 'thursday', 'start_time' => '10:00', 'end_time' => '12:00', 'subject_id' => $subjectsList['FR']->id, 'teacher_id' => $teacherFr->id, 'room' => 'Salle 01'],
            ['day_of_week' => 'friday', 'start_time' => '08:00', 'end_time' => '10:00', 'subject_id' => $subjectsList['PC']->id, 'teacher_id' => $teacherPC->id, 'room' => 'Labo 1'],
            ['day_of_week' => 'friday', 'start_time' => '15:00', 'end_time' => '17:00', 'subject_id' => $subjectsList['EPS']->id, 'teacher_id' => $teacherMath->id, 'room' => 'Terrain'],
        ];

        foreach ($scheduleData as $data) {
            Schedule::updateOrCreate(
                [
                    'class_room_id' => $classroom->id,
                    'day_of_week' => $data['day_of_week'],
                    'start_time' => $data['start_time'],
                    'academic_year_id' => $schoolYear->id
                ],
                $data
            );
        }
    }
}
