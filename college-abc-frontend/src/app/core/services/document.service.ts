import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface AdminDoc {
  id: number;
  title: string;
  type: 'PDF' | 'DOC';
  date: string;
  iconColor: string; // 'red', 'blue', 'green'
}

@Injectable({
  providedIn: 'root'
})
export class DocumentService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Récupère la liste des documents administratifs disponibles pour un élève.
   */
  getDocuments(studentId: number): Observable<AdminDoc[]> {
    return this.http.get<AdminDoc[]>(`${this.apiUrl}/documents/${studentId}`);
  }
}
