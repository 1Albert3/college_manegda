import { Injectable, signal, inject } from '@angular/core';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, catchError, of } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface User {
  id: number;
  name: string;
  role: 'parent' | 'student' | 'teacher' | 'admin';
  email: string;
  children?: Student[];
  token?: string;
}

export interface Student {
  id: number;
  firstName: string;
  lastName: string;
  class: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);
  // private apiUrl = environment.apiUrl;
  private apiUrl = 'http://localhost:8000/api';

  // Signal pour gérer l'état de l'utilisateur connecté de manière réactive
  currentUser = signal<User | null>(this.getUserFromStorage());

  /**
   * Connecte l'utilisateur via l'API backend.
   */
  login(credentials: any): Observable<any> {
    console.log('Attempting login with:', credentials);
    return this.http.post<any>(`${this.apiUrl}/login`, credentials).pipe(
      tap(response => {
        console.log('Login response:', response);
        // Adapt the response to match the User interface if necessary
        // Assuming backend returns { user: User, token: string }
        const user: User = { ...response.user, token: response.token };
        this.currentUser.set(user);
        localStorage.setItem('currentUser', JSON.stringify(user));
      }),
      catchError(error => {
        console.error('Login error details:', error);
        console.error('Status:', error.status);
        console.error('Message:', error.message);
        if (error.error) {
            console.error('Backend error:', error.error);
        }
        throw error;
      })
    );
  }

  /**
   * Déconnecte l'utilisateur et redirige vers la page de login.
   */
  logout() {
    this.currentUser.set(null);
    localStorage.removeItem('currentUser');
    this.router.navigate(['/login']);
  }

  isLoggedIn(): boolean {
    return !!this.currentUser();
  }

  private getUserFromStorage(): User | null {
    const userStr = localStorage.getItem('currentUser');
    return userStr ? JSON.parse(userStr) : null;
  }
}
