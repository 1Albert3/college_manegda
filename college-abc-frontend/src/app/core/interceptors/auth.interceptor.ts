import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Router } from '@angular/router';

/**
 * Intercepteur HTTP pour:
 * - Ajouter le token d'authentification à chaque requête
 * - Gérer les erreurs 401 (non authentifié)
 * - Gérer les erreurs 403 (non autorisé)
 */
@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private router: Router) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Récupérer le token
    const token = localStorage.getItem('token');

    // Ajouter le token si présent
    if (token) {
      request = request.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json'
        }
      });
    }

    return next.handle(request).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401) {
          // Token expiré ou invalide
          this.handleUnauthorized();
        } else if (error.status === 403) {
          // Pas autorisé
          this.handleForbidden();
        }
        return throwError(() => error);
      })
    );
  }

  /**
   * Gérer erreur 401 - Non authentifié
   */
  private handleUnauthorized(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.router.navigate(['/login'], {
      queryParams: { message: 'Session expirée. Veuillez vous reconnecter.' }
    });
  }

  /**
   * Gérer erreur 403 - Non autorisé
   */
  private handleForbidden(): void {
    this.router.navigate(['/'], {
      queryParams: { message: 'Vous n\'avez pas accès à cette ressource.' }
    });
  }
}
