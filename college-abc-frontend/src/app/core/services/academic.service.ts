import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface AcademicYear {
  id: string;
  name: string;
  start_date: string;
  end_date: string;
  is_current: boolean;
  is_locked?: boolean;
}

export interface Cycle {
  id: number;
  name: string;
  code: string;
  levels_count?: number;
}

export interface Level {
  id: number;
  name: string;
  code: string;
  cycle_id: number;
}

@Injectable({
  providedIn: 'root'
})
export class AcademicService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  // School Years (Core)
  getAcademicYears(): Observable<AcademicYear[]> {
    return this.http.get<any>(`${this.apiUrl}/core/school-years`).pipe(
      map(response => response.data)
    );
  }

  getCurrentYear(): Observable<AcademicYear> {
    return this.http.get<any>(`${this.apiUrl}/core/school-years/current`).pipe(
      map(response => response.data)
    );
  }

  createAcademicYear(data: Partial<AcademicYear>): Observable<AcademicYear> {
    return this.http.post<any>(`${this.apiUrl}/core/school-years`, data).pipe(
      map(response => response.data)
    );
  }

  updateAcademicYear(id: string, data: Partial<AcademicYear>): Observable<AcademicYear> {
    return this.http.put<any>(`${this.apiUrl}/core/school-years/${id}`, data).pipe(
      map(response => response.data)
    );
  }

  setCurrentYear(id: string): Observable<AcademicYear> {
    // Using update to set is_current = true
    return this.updateAcademicYear(id, { is_current: true } as any);
  }

  getCycles(): Observable<Cycle[]> {
    return this.http.get<any>(`${this.apiUrl}/cycles`).pipe(
      map(response => response.data)
    );
  }

  getLevels(): Observable<Level[]> {
    return this.http.get<any>(`${this.apiUrl}/levels`).pipe(
      map(response => response.data)
    );
  }
}
