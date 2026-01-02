import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface TeacherDashboardData {
  teacher: { id: string; name: string; email: string };
  school_year: string;
  current_trimestre: number;
  classes: any[];
  subjects: any[];
  pending_grades: any[];
  today_schedule: any[];
  recent_activity: any[];
  stats: {
    total_students: number;
    total_grades: number;
    classes_count: number;
  };
   // Add other fields as needed based on backend response
   unread_messages?: number; // Backend doesn't return this yet in my update, I might need to add it or mock it
}

@Injectable({
  providedIn: 'root'
})
export class TeacherService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  getDashboard(): Observable<TeacherDashboardData> {
    return this.http.get<TeacherDashboardData>(`${this.apiUrl}/dashboard/teacher`);
  }
}
