import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface Subject {
  id: number;
  name: string;
  code: string;
  coefficient: number;
}

@Injectable({
  providedIn: 'root'
})
export class SubjectService {
  private http = inject(HttpClient);
  
  private apiUrl = `${environment.apiUrl}/subjects`; 

  getSubjects(): Observable<Subject[]> {
    return this.http.get<any>(this.apiUrl).pipe(
      map(response => response.data)
    );
  }

  getSubjectsByClass(classId: number): Observable<Subject[]> {
    return this.http.get<any>(`${environment.apiUrl}/classes/${classId}/subjects`).pipe(
      map(response => response.data)
    );
  }
}
