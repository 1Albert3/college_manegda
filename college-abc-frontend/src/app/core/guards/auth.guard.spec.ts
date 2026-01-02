import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { authGuard, adminGuard, parentGuard } from './auth.guard';
import { AuthService } from '../services/auth.service';

describe('Auth Guards', () => {
  let authService: jasmine.SpyObj<AuthService>;
  let router: jasmine.SpyObj<Router>;

  beforeEach(() => {
    authService = jasmine.createSpyObj('AuthService', ['isLoggedIn', 'currentUser']);
    router = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router }
      ]
    });
  });

  describe('authGuard', () => {
    it('should allow navigation when user is logged in', () => {
      authService.isLoggedIn.and.returnValue(true);
      
      TestBed.runInInjectionContext(() => {
        const result = authGuard({} as any, { url: '/test' } as any);
        expect(result).toBe(true);
      });
    });

    it('should redirect to login when user is not logged in', () => {
      authService.isLoggedIn.and.returnValue(false);
      
      TestBed.runInInjectionContext(() => {
        const result = authGuard({} as any, { url: '/test' } as any);
        expect(result).toBe(false);
        expect(router.navigate).toHaveBeenCalledWith(['/login'], { queryParams: { returnUrl: '/test' } });
      });
    });
  });

  describe('adminGuard', () => {
    it('should allow admin users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Admin', email: 'admin@test.com', role: 'admin' });
      
      TestBed.runInInjectionContext(() => {
        const result = adminGuard({} as any, { url: '/admin' } as any);
        expect(result).toBe(true);
      });
    });

    it('should allow super_admin users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Super Admin', email: 'super@test.com', role: 'super_admin' });
      
      TestBed.runInInjectionContext(() => {
        const result = adminGuard({} as any, { url: '/admin' } as any);
        expect(result).toBe(true);
      });
    });

    it('should allow teacher users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Teacher', email: 'teacher@test.com', role: 'teacher' });
      
      TestBed.runInInjectionContext(() => {
        const result = adminGuard({} as any, { url: '/admin' } as any);
        expect(result).toBe(true);
      });
    });

    it('should deny parent users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Parent', email: 'parent@test.com', role: 'parent' });
      
      TestBed.runInInjectionContext(() => {
        const result = adminGuard({} as any, { url: '/admin' } as any);
        expect(result).toBe(false);
        expect(router.navigate).toHaveBeenCalledWith(['/']);
      });
    });

    it('should redirect to login when not authenticated', () => {
      authService.currentUser.and.returnValue(null);
      
      TestBed.runInInjectionContext(() => {
        const result = adminGuard({} as any, { url: '/admin' } as any);
        expect(result).toBe(false);
        expect(router.navigate).toHaveBeenCalledWith(['/login'], jasmine.any(Object));
      });
    });
  });

  describe('parentGuard', () => {
    it('should allow parent users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Parent', email: 'parent@test.com', role: 'parent' });
      
      TestBed.runInInjectionContext(() => {
        const result = parentGuard({} as any, { url: '/parents/dashboard' } as any);
        expect(result).toBe(true);
      });
    });

    it('should deny admin users', () => {
      authService.currentUser.and.returnValue({ id: 1, name: 'Admin', email: 'admin@test.com', role: 'admin' });
      
      TestBed.runInInjectionContext(() => {
        const result = parentGuard({} as any, { url: '/parents/dashboard' } as any);
        expect(result).toBe(false);
        expect(router.navigate).toHaveBeenCalledWith(['/']);
      });
    });
  });
});
