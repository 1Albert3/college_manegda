import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { Router } from '@angular/router';
import { AuthService, LoginRequest, LoginResponse, User } from './auth.service';
import { environment } from '../../../environments/environment';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;
  let routerSpy: jasmine.SpyObj<Router>;

  const mockUser: User = {
    id: '1',
    email: 'test@example.com',
    first_name: 'Test',
    last_name: 'User',
    role: 'admin'
  };

  const mockLoginResponse: LoginResponse = {
    user: mockUser,
    token: 'mock-token',
    requires_2fa: false,
    message: 'Login successful'
  };

  beforeEach(() => {
    const routerSpyObj = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        AuthService,
        { provide: Router, useValue: routerSpyObj }
      ]
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
    routerSpy = TestBed.inject(Router) as jasmine.SpyObj<Router>;

    // Clear localStorage before each test
    localStorage.clear();
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  describe('Authentication', () => {
    it('should be created', () => {
      expect(service).toBeTruthy();
    });

    it('should login successfully', () => {
      const credentials: LoginRequest = {
        identifier: 'test@example.com',
        password: 'password',
        role: 'admin'
      };

      service.login(credentials).subscribe(response => {
        expect(response).toEqual(mockLoginResponse);
        expect(localStorage.getItem('token')).toBe('mock-token');
        expect(localStorage.getItem('user')).toBe(JSON.stringify(mockUser));
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/auth/login`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({
        email: credentials.identifier,
        password: credentials.password,
        role: credentials.role
      });
      req.flush(mockLoginResponse);
    });

    it('should handle login error', () => {
      const credentials: LoginRequest = {
        identifier: 'test@example.com',
        password: 'wrong-password',
        role: 'admin'
      };

      service.login(credentials).subscribe({
        next: () => fail('Should have failed'),
        error: (error) => {
          expect(error.status).toBe(422);
        }
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/auth/login`);
      req.flush({ message: 'Invalid credentials' }, { status: 422, statusText: 'Unprocessable Entity' });
    });

    it('should logout successfully', () => {
      // Set up authenticated state
      localStorage.setItem('token', 'mock-token');
      localStorage.setItem('user', JSON.stringify(mockUser));
      service['currentUserSubject'].next(mockUser);

      service.logout().subscribe(response => {
        expect(localStorage.getItem('token')).toBeNull();
        expect(localStorage.getItem('user')).toBeNull();
        expect(routerSpy.navigate).toHaveBeenCalledWith(['/login']);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/auth/logout`);
      expect(req.request.method).toBe('POST');
      req.flush({ message: 'Logout successful' });
    });

    it('should get user info', () => {
      service.getMe().subscribe(response => {
        expect(response.user).toEqual(mockUser);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/auth/me`);
      expect(req.request.method).toBe('GET');
      req.flush({ user: mockUser });
    });
  });

  describe('Role Checking', () => {
    beforeEach(() => {
      service['currentUserSubject'].next(mockUser);
    });

    it('should check if user has specific role', () => {
      expect(service.hasRole('admin')).toBe(true);
      expect(service.hasRole('teacher')).toBe(false);
    });

    it('should check if user is admin', () => {
      expect(service.isAdmin()).toBe(true);
    });

    it('should check if user is teacher', () => {
      expect(service.isTeacher()).toBe(false);
    });

    it('should check if user is logged in', () => {
      localStorage.setItem('token', 'mock-token');
      expect(service.isLoggedIn()).toBe(true);
    });
  });

  describe('Dashboard Redirection', () => {
    it('should redirect admin to admin dashboard', () => {
      service['currentUserSubject'].next({ ...mockUser, role: 'admin' });
      service.redirectToDashboard();
      expect(routerSpy.navigate).toHaveBeenCalledWith(['/admin/dashboard']);
    });

    it('should redirect teacher to teacher dashboard', () => {
      service['currentUserSubject'].next({ ...mockUser, role: 'teacher' });
      service.redirectToDashboard();
      expect(routerSpy.navigate).toHaveBeenCalledWith(['/teacher/dashboard']);
    });

    it('should redirect parent to parent dashboard', () => {
      service['currentUserSubject'].next({ ...mockUser, role: 'parent' });
      service.redirectToDashboard();
      expect(routerSpy.navigate).toHaveBeenCalledWith(['/parents/dashboard']);
    });
  });

  describe('Token Management', () => {
    it('should return token from localStorage', () => {
      localStorage.setItem('token', 'test-token');
      expect(service.token).toBe('test-token');
    });

    it('should return null when no token', () => {
      expect(service.token).toBeNull();
    });

    it('should load stored user on initialization', () => {
      localStorage.setItem('token', 'test-token');
      localStorage.setItem('user', JSON.stringify(mockUser));
      
      // Create new service instance to test initialization
      const newService = new AuthService(TestBed.inject(HttpClientTestingModule), routerSpy);
      
      expect(newService.currentUser()).toEqual(mockUser);
    });
  });
});