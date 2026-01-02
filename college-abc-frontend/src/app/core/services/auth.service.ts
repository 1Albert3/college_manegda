import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, catchError, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

export interface User {
  id: string;
  name?: string;
  email: string;
  first_name?: string;
  last_name?: string;
  full_name?: string;
  role: 'admin' | 'director' | 'teacher' | 'student' | 'parent' | 'secretary' | 'accountant' | 'hr' | 'super_admin' | 'direction' | 'secretariat' | 'comptabilite' | 'enseignant' | 'eleve';
  profile_photo?: string;
  phone?: string;
  two_factor_enabled?: boolean;
  permissions?: string[];
  children?: { 
    id: string; 
    name?: string; 
    firstName?: string;
    lastName?: string;
    matricule?: string;
    class?: string;
  }[];
  student_id?: string;
  must_change_password?: boolean;
}

export interface LoginRequest {
  identifier: string;
  password: string;
  role?: 'admin' | 'director' | 'teacher' | 'student' | 'parent' | 'secretary' | 'accountant' | 'hr' | 'super_admin' | 'direction' | 'secretariat' | 'comptabilite' | 'enseignant' | 'eleve';
}



export interface LoginResponse {
  success?: boolean;
  user: User;
  access_token: string;
  token_type?: string;
  requires_2fa: boolean;
  temp_token?: string;
  message: string;
  must_change_password?: boolean;
}

export interface Role {
  value: string;
  label: string;
  icon: string;
  description: string;
  requires_2fa: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api/v1';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  private tokenKey = 'token';
  private userKey = 'user';

  currentUser$ = this.currentUserSubject.asObservable();

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    this.loadStoredUser();
  }

  /**
   * Load stored user at startup
   */
  private loadStoredUser(): void {
    const storedUser = localStorage.getItem(this.userKey);
    const storedToken = localStorage.getItem(this.tokenKey);

    if (storedUser && storedToken) {
      try {
        const user = JSON.parse(storedUser);
        this.currentUserSubject.next(user);
      } catch {
        this.clearStorage();
      }
    }
  }

  /**
   * Get available roles for login page
   */
  getRoles(): Observable<{ roles: Role[] }> {
    return this.http.get<{ roles: Role[] }>(`${this.apiUrl}/auth/roles`);
  }

  /**
   * User login
   */
  login(credentials: LoginRequest): Observable<LoginResponse> {
    const payload = {
      identifier: credentials.identifier,
      password: credentials.password,
      role: credentials.role
    };

    return this.http.post<LoginResponse>(`${this.apiUrl}/auth/login`, payload)
      .pipe(
        tap(response => {
          if (!response.requires_2fa) {
            this.handleLoginSuccess(response);
          }
        }),
        catchError(error => this.handleError(error))
      );
  }

  /**
   * 2FA verification
   */
  verify2FA(tempToken: string, code: string, role: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/auth/verify-2fa`, {
      temp_token: tempToken,
      code,
      role
    }).pipe(
      tap(response => this.handleLoginSuccess(response)),
      catchError(error => this.handleError(error))
    );
  }

  /**
   * Handle successful login
   */
  private handleLoginSuccess(response: LoginResponse): void {
    localStorage.setItem(this.tokenKey, response.access_token);
    localStorage.setItem(this.userKey, JSON.stringify(response.user));
    this.currentUserSubject.next(response.user);
  }

  /**
   * Logout
   */
  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/logout`, {})
      .pipe(
        tap(() => this.clearSession()),
        catchError(error => {
          this.clearSession();
          return throwError(() => error);
        })
      );
  }

  /**
   * Local logout (without API call)
   */
  logoutLocal(): void {
    this.clearSession();
  }

  /**
   * Clear session
   */
  private clearSession(): void {
    this.clearStorage();
    this.currentUserSubject.next(null);
    this.router.navigate(['/login']);
  }

  /**
   * Clear local storage
   */
  private clearStorage(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
  }

  /**
   * Get current user (sync)
   */
  currentUser(): User | null {
    return this.currentUserSubject.value;
  }

  /**
   * Get token
   */
  get token(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  /**
   * Check if user is logged in
   */
  isLoggedIn(): boolean {
    return !!this.token && !!this.currentUser();
  }

  /**
   * Check if user has a specific role
   */
  hasRole(role: string): boolean {
    return this.currentUser()?.role === role;
  }

  /**
   * Check if user has a permission
   */
  hasPermission(permission: string): boolean {
    return this.currentUser()?.permissions?.includes(permission) ?? false;
  }

  /**
   * Check if user is admin
   */
  isAdmin(): boolean {
    return this.hasRole('admin') || this.hasRole('director') || this.hasRole('super_admin');
  }

  // ... existing code ...

  /**
   * Redirect to appropriate dashboard based on role
   * Supports both English and French role names from backend
   */
  redirectToDashboard(): void {
    const role = this.currentUser()?.role;

    switch (role) {
      case 'admin':
      case 'hr':
      case 'director':
      case 'direction': // French
      case 'super_admin':
        this.router.navigate(['/admin/dashboard']);
        break;
      case 'secretary':
      case 'secretariat': // French
        this.router.navigate(['/secretary/dashboard']);
        break;
      case 'accountant':
      case 'comptabilite': // French
        this.router.navigate(['/accounting/dashboard']);
        break;
      case 'teacher':
      case 'enseignant': // French
        this.router.navigate(['/teacher/dashboard']);
        break;
      case 'parent':
        this.router.navigate(['/parents/dashboard']);
        break;
      case 'student':
      case 'eleve': // French
        this.router.navigate(['/student/dashboard']);
        break;
      default:
        this.router.navigate(['/']);
    }
  }

  /**
   * Get authentication headers
   */
  getAuthHeaders(): HttpHeaders {
    const token = this.token;
    return new HttpHeaders({
      'Authorization': token ? `Bearer ${token}` : '',
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });
  }

  /**
   * Change password
   */
  changePassword(currentPassword: string, newPassword: string, confirmPassword: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/change-password`, {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: confirmPassword
    }).pipe(
      catchError(error => this.handleError(error))
    );
  }

  /**
   * Handle errors
   */
  private handleError(error: any): Observable<never> {
    console.error('AuthService Error:', error);
    
    if (error.status === 401) {
      this.clearSession();
    }

    return throwError(() => error);
  }
}
