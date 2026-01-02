import { Routes } from '@angular/router';
import { CollegeClassesComponent } from './classes/college-classes.component';

export const CollegeRoutes: Routes = [
  {
    path: '',
    redirectTo: 'classes',
    pathMatch: 'full'
  },
  {
    path: 'classes',
    component: CollegeClassesComponent,
    data: { title: 'Collège - Classes' }
  },
  {
    path: 'classes/:id/students',
    loadComponent: () => import('./students/college-student-list.component').then(m => m.CollegeStudentListComponent),
    data: { title: 'Collège - Liste des Élèves' }
  },
  {
    path: 'classes/:id/grades',
    loadComponent: () => import('./grades/college-grade-entry.component').then(m => m.CollegeGradeEntryComponent),
    data: { title: 'Collège - Saisie des Notes' }
  },
  {
    path: 'classes/:id/bulletins',
    loadComponent: () => import('./report-cards/college-report-cards.component').then(m => m.CollegeReportCardsComponent),
    data: { title: 'Collège - Bulletins' }
  }
];
