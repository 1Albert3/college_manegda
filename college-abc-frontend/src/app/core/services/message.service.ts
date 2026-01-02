import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Message {
  id: number;
  sender_id: number;
  recipient_id: number;
  subject: string;
  body: string;
  read: boolean;
  created_at: string;
  sender?: { id: number; name: string; role: string };
  recipient?: { id: number; name: string; role: string };
}

export interface Appointment {
  id: number;
  parent_id: number;
  teacher_id: number;
  date: string;
  time: string;
  status: 'pending' | 'confirmed' | 'cancelled';
  notes?: string;
}

@Injectable({
  providedIn: 'root'
})
export class MessageService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  // Messages
  getMessages(folder: 'inbox' | 'sent' = 'inbox'): Observable<Message[]> {
    return this.http.get<Message[]>(`${this.apiUrl}/messages`, { params: { folder } });
  }

  getMessage(id: number): Observable<Message> {
    return this.http.get<Message>(`${this.apiUrl}/messages/${id}`);
  }

  sendMessage(data: { recipient_id: number; subject: string; body: string }): Observable<Message> {
    return this.http.post<Message>(`${this.apiUrl}/messages`, data);
  }

  markAsRead(id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/messages/${id}/read`, {});
  }

  deleteMessage(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/messages/${id}`);
  }

  getUnreadCount(): Observable<{ count: number }> {
    return this.http.get<{ count: number }>(`${this.apiUrl}/messages/unread-count`);
  }

  // Appointments
  getAppointments(): Observable<Appointment[]> {
    return this.http.get<Appointment[]>(`${this.apiUrl}/appointments`);
  }

  createAppointment(data: any): Observable<Appointment> {
    return this.http.post<Appointment>(`${this.apiUrl}/appointments`, data);
  }

  cancelAppointment(id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/appointments/${id}/cancel`, {});
  }

  confirmAppointment(id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/appointments/${id}/confirm`, {});
  }

  getAvailableSlots(teacherId: number, date: string): Observable<string[]> {
    return this.http.get<string[]>(`${this.apiUrl}/appointments/available-slots`, {
      params: { teacher_id: teacherId, date }
    });
  }
}
