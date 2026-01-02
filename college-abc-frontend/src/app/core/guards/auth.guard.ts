import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Generic role guard creator
 * Supports both English and French role names from backend
 */
const createRoleGuard = (allowedRoles: string[]): CanActivateFn => {
  return (route, state) => {
    const authService = inject(AuthService);
    const router = inject(Router);

    const user = authService.currentUser();
    
    if (!user) {
      router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
      return false;
    }

    if (allowedRoles.includes(user.role)) {
      return true;
    }

    router.navigate(['/']);
    return false;
  };
};

/**
 * Guard for authenticated routes (all logged-in users)
 */
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.isLoggedIn()) {
    return true;
  }

  router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  return false;
};

/**
 * Guard for Admin only (includes French 'direction')
 */
export const adminGuard: CanActivateFn = createRoleGuard(['admin', 'hr', 'director', 'direction', 'super_admin']);

/**
 * Guard for Admin routes (includes teacher for some views)
 */
export const adminOrTeacherGuard: CanActivateFn = createRoleGuard(['admin', 'hr', 'teacher', 'enseignant', 'director', 'direction', 'super_admin']);

/**
 * Guard for Secretary (includes French 'secretariat')
 */
export const secretaryGuard: CanActivateFn = createRoleGuard(['secretary', 'secretariat', 'admin', 'hr', 'director', 'direction', 'super_admin']);

/**
 * Guard for Accountant (includes French 'comptabilite')
 */
export const accountantGuard: CanActivateFn = createRoleGuard(['accountant', 'comptabilite', 'admin', 'hr', 'director', 'direction', 'super_admin']);

/**
 * Guard for Teacher (includes French 'enseignant')
 */
export const teacherGuard: CanActivateFn = createRoleGuard(['teacher', 'enseignant', 'admin', 'hr', 'director', 'direction', 'super_admin']);

/**
 * Guard for Parent
 */
export const parentGuard: CanActivateFn = createRoleGuard(['parent']);

/**
 * Guard for Student (includes French 'eleve')
 */
export const studentGuard: CanActivateFn = createRoleGuard(['student', 'eleve']);

/**
 * Guard for HR
 */
export const hrGuard: CanActivateFn = createRoleGuard(['hr', 'admin', 'director', 'direction', 'super_admin']);

/**
 * Guard for Staff (admin, secretary, accountant, teacher) with French equivalents
 */
export const staffGuard: CanActivateFn = createRoleGuard([
  'admin', 'hr', 'secretary', 'secretariat', 'accountant', 'comptabilite', 
  'teacher', 'enseignant', 'director', 'direction', 'super_admin'
]);

/**
 * Guard for public routes - redirects if already logged in
 */
export const guestGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isLoggedIn()) {
    return true;
  }

  // Redirect to appropriate dashboard based on role (supports French roles)
  const user = authService.currentUser();
  switch (user?.role) {
    case 'admin':
    case 'hr':
    case 'director':
    case 'direction':
    case 'super_admin':
      router.navigate(['/admin/dashboard']);
      break;
    case 'secretary':
    case 'secretariat':
      router.navigate(['/secretary/dashboard']);
      break;
    case 'accountant':
    case 'comptabilite':
      router.navigate(['/accounting/dashboard']);
      break;
    case 'teacher':
    case 'enseignant':
      router.navigate(['/teacher/dashboard']);
      break;
    case 'parent':
      router.navigate(['/parents/dashboard']);
      break;
    case 'student':
    case 'eleve':
      router.navigate(['/student/dashboard']);
      break;
    default:
      router.navigate(['/']);
  }
  return false;
};

// Legacy exports for backward compatibility
export const directorGuard = adminGuard;
export const accountingGuard = accountantGuard;

