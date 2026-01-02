import { Routes } from '@angular/router';
import { LyceeClassesComponent } from './classes/lycee-classes.component';

export const LYCEE_ROUTES: Routes = [
  {
    path: '',
    redirectTo: 'classes',
    pathMatch: 'full'
  },
  {
    path: 'classes',
    component: LyceeClassesComponent,
    data: { title: 'Lycée - Classes' }
  },
  {
    path: 'classes/:id/students',
    loadComponent: () => import('./students/lycee-student-list.component').then(m => m.LyceeStudentListComponent),
    data: { title: 'Lycée - Liste des Élèves' }
  },
  {
    path: 'classes/:id/grades',
    loadComponent: () => import('./grades/lycee-grade-entry.component').then(m => m.LyceeGradeEntryComponent),
    data: { title: 'Lycée - Saisie des Notes' }
  },
  {
    path: 'classes/:id/bulletins',
    loadComponent: () => import('./report-cards/lycee-report-cards.component').then(m => m.LyceeReportCardsComponent),
    data: { title: 'Lycée - Bulletins' }
  }
];
