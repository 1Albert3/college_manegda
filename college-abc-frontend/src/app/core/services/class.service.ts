import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface ClassRoom {
  id: string; // Updated to string for UUID support
  name: string;
  level: string; // 'CP', '2nde', etc.
  capacity: number;
  // Lycee specific
  serie?: string;
  // MP specific
  cycle?: string;
}

export type SchoolCycle = 'mp' | 'college' | 'lycee';

@Injectable({
  providedIn: 'root'
})
export class ClassService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  /**
   * Récupérer les classes d'un cycle donné
   * @param cycle 'mp' | 'lycee' | 'college'
   * @param params Filtres optionnels (niveau, année, etc.)
   */
  getClasses(cycle: SchoolCycle = 'mp', params?: any): Observable<any> {
    // Construction de l'URL spécifique au cycle
    // ex: /v1/mp/classes ou /v1/lycee/classes
    const endpoint = `${this.apiUrl}/${cycle}/classes`;
    
    return this.http.get(endpoint, { params });
  }

  // Helpers pratiques

  getClassesMP(params?: any): Observable<any> {
    return this.getClasses('mp', params);
  }

  getClassesLycee(params?: any): Observable<any> {
    return this.getClasses('lycee', params);
  }

  getClassesCollege(params?: any): Observable<any> {
    return this.getClasses('college', params);
  }

  /**
   * Créer une nouvelle classe
   */
  createClass(cycle: SchoolCycle, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/${cycle}/classes`, data);
  }

  /**
   * Mettre à jour une classe
   */
  updateClass(cycle: SchoolCycle, id: string, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${cycle}/classes/${id}`, data);
  }

  /**
   * Supprimer une classe
   */
  deleteClass(cycle: SchoolCycle, id: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${cycle}/classes/${id}`);
  }

  getStudentsByClass(cycle: SchoolCycle, classId: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/${cycle}/classes/${classId}/students`);
  }

  // --- ASSIGNATION LYCÉE ---
  getAssignmentsLycee(classId: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/lycee/classes/${classId}/assignments`);
  }

  assignTeacherLycee(classId: string, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/lycee/classes/${classId}/assignments`, data);
  }

  removeAssignmentLycee(classId: string, assignmentId: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/lycee/classes/${classId}/assignments/${assignmentId}`);
  }

  getAvailableResourcesLycee(): Observable<any> {
    return this.http.get(`${this.apiUrl}/lycee/resources/available`);
  }
}