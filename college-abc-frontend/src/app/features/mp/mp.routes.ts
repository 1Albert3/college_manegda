import { Routes } from '@angular/router';
import { MpClassesComponent } from './classes/mp-classes.component';
import { MpStudentListComponent } from './students/mp-student-list.component';
import { MpGradeEntryComponent } from './grades/mp-grade-entry.component';
import { MpReportCardsComponent } from './report-cards/mp-report-cards.component';
import { MpAttendanceComponent } from './attendance/mp-attendance.component';

export const MP_ROUTES: Routes = [
  {
    path: '',
    redirectTo: 'classes',
    pathMatch: 'full'
  },
  {
    path: 'classes',
    component: MpClassesComponent,
    data: { title: 'Maternelle & Primaire' }
  },
  {
    path: 'classes/:id/students',
    component: MpStudentListComponent,
    data: { title: 'Liste des Élèves' }
  },
  {
      path: 'classes/:id/grades',
      component: MpGradeEntryComponent,
      data: { title: 'Saisie des Notes' }
  },
  {
      path: 'classes/:id/bulletins',
      component: MpReportCardsComponent,
      data: { title: 'Gestion des Bulletins' }
  },
  {
      path: 'classes/:id/attendance',
      component: MpAttendanceComponent,
      data: { title: 'Gestion des Absences' }
  }
];
