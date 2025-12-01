import { Student } from './student.model';

export interface Enrollment {
    id?: number;
    student_id: string;
    student?: Student;
    academic_year_id: number;
    class_id: number;
    enrollment_date: Date;
    status: string;
    discount_percentage?: number;
    notes?: string;
}
