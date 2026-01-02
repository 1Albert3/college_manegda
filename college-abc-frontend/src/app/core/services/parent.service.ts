import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Child {
  id: string;
  matricule: string;
  nom: string;
  prenoms: string;
  full_name: string;
  photo_url: string | null;
  class_name: string;
  niveau: string;
  cycle?: string; // e.g. 'mp', 'college', 'lycee'
}

export interface DashboardData {
  children: Child[];
  current_child: Child | null;
  grades_summary: {
    moyenne_generale: number;
    rang: number;
    effectif: number;
    trimestre: number;
  };
  recent_grades: {
    subject: string;
    note: number;
    date: string;
    type: string;
  }[];
  upcoming_events: {
    title: string;
    date: string;
    type: string;
  }[];
  attendance_summary: {
    absences: number;
    retards: number;
    non_justifiees: number;
  };
  payment_status: {
    total: number;
    paid: number;
    remaining: number;
    next_deadline: string;
  };
  unread_messages: number;
}

@Injectable({
  providedIn: 'root'
})
export class ParentService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  getDashboard(childId?: string): Observable<DashboardData> {
    const params: any = {};
    if (childId) {
      params.child_id = childId;
    }
    return this.http.get<DashboardData>(`${this.apiUrl}/dashboard/parent`, { params });
  }

  // Future methods for specific details
  getChildGrades(childId: string, trimestre?: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/dashboard/parent/grades/${childId}`, { 
      params: trimestre ? { trimestre } : {} 
    });
  }
}
