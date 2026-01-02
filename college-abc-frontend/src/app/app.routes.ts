import { Routes } from '@angular/router';
import { PublicLayoutComponent } from './layouts/public-layout/public-layout.component';
import { HomeComponent } from './features/public/home/home.component';

import { AboutComponent } from './features/public/about/about.component';
import { NewsComponent } from './features/public/news/news.component';
import { ContactComponent } from './features/public/contact/contact.component';
import { LoginComponent } from './features/public/login/login.component';
import { authGuard, adminGuard, parentGuard, teacherGuard, studentGuard, secretaryGuard, accountingGuard } from './core/guards/auth.guard';

export const routes: Routes = [
    // ========================================
    // PUBLIC ROUTES
    // ========================================
    {
        path: '',
        component: PublicLayoutComponent,
        children: [
            { path: '', component: HomeComponent, title: 'Collège Privé Wend-Manegda - Accueil' },
            { path: 'about', component: AboutComponent, title: 'Collège Privé Wend-Manegda - Notre Histoire' },
            { path: 'school-life', loadComponent: () => import('./features/public/school-life/school-life.component').then(m => m.SchoolLifeComponent), title: 'Vie Scolaire' },
            { path: 'news', component: NewsComponent, title: 'Actualités' },

            { path: 'contact', component: ContactComponent, title: 'Contact' },
            { path: 'faq', loadComponent: () => import('./features/public/faq/faq.component').then(m => m.FaqComponent), title: 'FAQ' },
            { path: 'login', component: LoginComponent, title: 'Connexion' },
            { path: 'debug-login', loadComponent: () => import('./debug-login.component').then(m => m.DebugLoginComponent), title: 'Debug Login' },
            { path: 'admin-login', loadComponent: () => import('./features/public/admin-login/admin-login.component').then(m => m.AdminLoginComponent), title: 'Connexion Administration' },
            { path: 'change-password', loadComponent: () => import('./features/public/change-password/change-password.component').then(m => m.ChangePasswordComponent), title: 'Changer Mot de Passe' },
        ]
    },

    // ========================================
    // 👔 ADMINISTRATION / DIRECTION
    // ========================================
    {
        path: 'admin',
        loadComponent: () => import('./layouts/admin-layout/admin-layout.component').then(m => m.AdminLayoutComponent),
        canActivate: [adminGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/admin/dashboard/dashboard.component').then(m => m.AdminDashboardComponent), title: 'Admin - Dashboard' },
            { path: 'students', loadComponent: () => import('./features/admin/students/student-list/student-list.component').then(m => m.StudentListComponent), title: 'Admin - Élèves' },
            { path: 'students/register', loadComponent: () => import('./features/admin/students/student-register/student-register.component').then(m => m.StudentRegisterComponent), title: 'Admin - Inscription' },
            { path: 'students/:id/edit', loadComponent: () => import('./features/admin/students/student-register/student-register.component').then(m => m.StudentRegisterComponent), title: 'Admin - Modification' },
            { path: 'students/:id/details', loadComponent: () => import('./features/admin/students/student-register/student-register.component').then(m => m.StudentRegisterComponent), title: 'Admin - Détails' },
            { path: 'academic', loadComponent: () => import('./features/admin/academic/academic.component').then(m => m.AdminAcademicComponent), title: 'Admin - Académique' },
            { path: 'finance', loadChildren: () => import('./features/admin/finance/finance.routes').then(m => m.FINANCE_ROUTES), title: 'Admin - Finance' },
            { path: 'hr', loadComponent: () => import('./features/admin/hr/hr-management.component').then(m => m.HRManagementComponent), title: 'Admin - RH & Personnel' },
            { path: 'library', loadComponent: () => import('./features/admin/library/library.component').then(m => m.LibraryComponent), title: 'Admin - Librairie' },
            { path: 'schedule', loadComponent: () => import('./features/admin/schedule/schedule.component').then(m => m.AdminScheduleComponent), title: 'Admin - Emploi du Temps' },
            { path: 'grades', loadComponent: () => import('./features/admin/grades/grade-list/grade-list.component').then(m => m.GradeListComponent), title: 'Admin - Notes' },
            { path: 'grades/entry', loadComponent: () => import('./features/admin/grades/grade-entry/grade-entry.component').then(m => m.GradeEntryComponent), title: 'Admin - Saisie Notes' },
            { path: 'grades/bulletins', loadComponent: () => import('./features/admin/grades/bulletins/bulletins.component').then(m => m.BulletinsComponent), title: 'Admin - Bulletins' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Admin - Messagerie' },
            { path: 'reports', loadComponent: () => import('./features/admin/reports/admin-reports.component').then(m => m.AdminReportsComponent), title: 'Admin - Rapports' },
            { path: 'validations', loadComponent: () => import('./features/admin/validations/admin-validations.component').then(m => m.AdminValidationsComponent), title: 'Admin - Validations' },
            { path: 'settings', loadComponent: () => import('./features/admin/settings/admin-settings.component').then(m => m.AdminSettingsComponent), title: 'Admin - Paramètres' },
            { path: 'college', loadChildren: () => import('./features/college/college.routes').then(m => m.CollegeRoutes), title: 'Admin - Collège' },
            { path: 'mp', loadChildren: () => import('./features/mp/mp.routes').then(m => m.MP_ROUTES), title: 'Admin - MP' },
            { path: 'lycee', loadChildren: () => import('./features/lycee/lycee.routes').then(m => m.LYCEE_ROUTES), title: 'Admin - Lycée' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // 👨‍🏫 ENSEIGNANTS
    // ========================================
    {
        path: 'teacher',
        loadComponent: () => import('./layouts/teacher-layout/teacher-layout.component').then(m => m.TeacherLayoutComponent),
        canActivate: [teacherGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/teacher/dashboard/teacher-dashboard.component').then(m => m.TeacherDashboardComponent), title: 'Enseignant - Dashboard' },
            { path: 'classes', loadComponent: () => import('./features/teacher/classes/teacher-classes.component').then(m => m.TeacherClassesComponent), title: 'Enseignant - Mes Classes' },
            { path: 'classes/:id', loadComponent: () => import('./features/teacher/classes/teacher-classes.component').then(m => m.TeacherClassesComponent), title: 'Enseignant - Classe' },
            { path: 'grades', loadComponent: () => import('./features/teacher/grades/teacher-grades.component').then(m => m.TeacherGradesComponent), title: 'Enseignant - Notes' },
            { path: 'attendance', loadComponent: () => import('./features/teacher/attendance/teacher-attendance.component').then(m => m.TeacherAttendanceComponent), title: 'Enseignant - Absences' },
            { path: 'homework', loadComponent: () => import('./features/teacher/homework/teacher-homework.component').then(m => m.TeacherHomeworkComponent), title: 'Enseignant - Cahier de Texte' },
            { path: 'schedule', loadComponent: () => import('./features/teacher/schedule/teacher-schedule.component').then(m => m.TeacherScheduleComponent), title: 'Enseignant - Emploi du Temps' },
            { path: 'observations', loadComponent: () => import('./features/teacher/observations/teacher-observations.component').then(m => m.TeacherObservationsComponent), title: 'Enseignant - Observations' },
            { path: 'resources', loadComponent: () => import('./features/teacher/dashboard/teacher-dashboard.component').then(m => m.TeacherDashboardComponent), title: 'Enseignant - Ressources' },
            { path: 'councils', loadComponent: () => import('./features/teacher/dashboard/teacher-dashboard.component').then(m => m.TeacherDashboardComponent), title: 'Enseignant - Conseils' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Enseignant - Messagerie' },
            { path: 'appointments', loadComponent: () => import('./features/teacher/appointments/teacher-appointments.component').then(m => m.TeacherAppointmentsComponent), title: 'Enseignant - RDV' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // 👨‍👩‍👧 PARENTS
    // ========================================
    {
        path: 'parents',
        loadComponent: () => import('./layouts/parent-layout/parent-layout.component').then(m => m.ParentLayoutComponent),
        canActivate: [parentGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/parents/dashboard/dashboard.component').then(m => m.ParentDashboardComponent), title: 'Parents - Dashboard' },
            { path: 'grades', loadComponent: () => import('./features/parents/grades/parent-grades.component').then(m => m.ParentGradesComponent), title: 'Parents - Notes' },
            { path: 'bulletins', loadComponent: () => import('./features/parents/bulletins/parent-bulletins.component').then(m => m.ParentBulletinsComponent), title: 'Parents - Bulletins' },
            { path: 'homework', loadComponent: () => import('./features/parents/homework/parent-homework.component').then(m => m.ParentHomeworkComponent), title: 'Parents - Devoirs' },
            { path: 'attendance', loadComponent: () => import('./features/parents/attendance/parent-attendance.component').then(m => m.ParentAttendanceComponent), title: 'Parents - Absences' },
            { path: 'schedule', loadComponent: () => import('./features/parents/schedule/parent-schedule.component').then(m => m.ParentScheduleComponent), title: 'Parents - Emploi du Temps' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Parents - Messagerie' },
            { path: 'appointments', loadComponent: () => import('./features/parents/appointments/parent-appointments.component').then(m => m.ParentAppointmentsComponent), title: 'Parents - RDV' },
            { path: 'invoices', loadComponent: () => import('./features/parents/invoices/parent-invoices.component').then(m => m.ParentInvoicesComponent), title: 'Parents - Factures' },
            { path: 'payments', loadComponent: () => import('./features/parents/invoices/parent-invoices.component').then(m => m.ParentInvoicesComponent), title: 'Parents - Paiements' },
            { path: 'documents', loadComponent: () => import('./features/parents/dashboard/dashboard.component').then(m => m.ParentDashboardComponent), title: 'Parents - Documents' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // 🎓 ÉLÈVES
    // ========================================
    {
        path: 'student',
        loadComponent: () => import('./layouts/student-layout/student-layout.component').then(m => m.StudentLayoutComponent),
        canActivate: [studentGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/student/dashboard/student-dashboard.component').then(m => m.StudentDashboardComponent), title: 'Élève - Dashboard' },
            { path: 'schedule', loadComponent: () => import('./features/student/schedule/student-schedule.component').then(m => m.StudentScheduleComponent), title: 'Élève - Emploi du Temps' },
            { path: 'grades', loadComponent: () => import('./features/student/grades/student-grades.component').then(m => m.StudentGradesComponent), title: 'Élève - Notes' },
            { path: 'homework', loadComponent: () => import('./features/student/homework/student-homework.component').then(m => m.StudentHomeworkComponent), title: 'Élève - Devoirs' },
            { path: 'submissions', loadComponent: () => import('./features/student/homework/student-homework.component').then(m => m.StudentHomeworkComponent), title: 'Élève - Remises' },
            { path: 'resources', loadComponent: () => import('./features/student/resources/student-resources.component').then(m => m.StudentResourcesComponent), title: 'Élève - Ressources' },
            { path: 'attendance', loadComponent: () => import('./features/student/attendance/student-attendance.component').then(m => m.StudentAttendanceComponent), title: 'Élève - Absences' },
            { path: 'calendar', loadComponent: () => import('./features/student/calendar/student-calendar.component').then(m => m.StudentCalendarComponent), title: 'Élève - Calendrier' },
            { path: 'forum', loadComponent: () => import('./features/student/forum/student-forum.component').then(m => m.StudentForumComponent), title: 'Élève - Forum' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Élève - Messagerie' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // 📋 SECRÉTARIAT
    // ========================================
    {
        path: 'secretary',
        loadComponent: () => import('./layouts/secretary-layout/secretary-layout.component').then(m => m.SecretaryLayoutComponent),
        canActivate: [secretaryGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/secretary/dashboard/secretary-dashboard.component').then(m => m.SecretaryDashboardComponent), title: 'Secrétariat - Dashboard' },
            { path: 'enrollments', loadComponent: () => import('./features/secretary/enrollments/secretary-enrollments.component').then(m => m.SecretaryEnrollmentsComponent), title: 'Secrétariat - Inscriptions' },
            { path: 'documents', loadComponent: () => import('./features/secretary/documents/secretary-documents.component').then(m => m.SecretaryDocumentsComponent), title: 'Secrétariat - Documents' },
            { path: 'classes', loadComponent: () => import('./features/secretary/classes/secretary-classes.component').then(m => m.SecretaryClassesComponent), title: 'Secrétariat - Classes' },
            { path: 'timetable', loadComponent: () => import('./features/secretary/timetable/secretary-timetable.component').then(m => m.SecretaryTimetableComponent), title: 'Secrétariat - Emploi du Temps' },
            { path: 'exams', loadComponent: () => import('./features/secretary/exams/secretary-exams.component').then(m => m.SecretaryExamsComponent), title: 'Secrétariat - Examens' },
            { path: 'invoices', loadComponent: () => import('./features/secretary/invoices/secretary-invoices.component').then(m => m.SecretaryInvoicesComponent), title: 'Secrétariat - Factures' },
            { path: 'payments', loadComponent: () => import('./features/secretary/payments/secretary-payments.component').then(m => m.SecretaryPaymentsComponent), title: 'Secrétariat - Paiements' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Secrétariat - Messagerie' },
            { path: 'students', loadComponent: () => import('./features/secretary/enrollments/secretary-enrollments.component').then(m => m.SecretaryEnrollmentsComponent), title: 'Secrétariat - Dossiers Élèves' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // 💰 COMPTABILITÉ
    // ========================================
    {
        path: 'accounting',
        loadComponent: () => import('./layouts/accounting-layout/accounting-layout.component').then(m => m.AccountingLayoutComponent),
        canActivate: [accountingGuard],
        children: [
            { path: 'dashboard', loadComponent: () => import('./features/accounting/dashboard/accounting-dashboard.component').then(m => m.AccountingDashboardComponent), title: 'Comptabilité - Dashboard' },
            { path: 'payments', loadComponent: () => import('./features/accounting/payments/accounting-payments.component').then(m => m.AccountingPaymentsComponent), title: 'Comptabilité - Paiements' },
            { path: 'validation', loadComponent: () => import('./features/accounting/payments/accounting-payments.component').then(m => m.AccountingPaymentsComponent), title: 'Comptabilité - Validation' },
            { path: 'history', loadComponent: () => import('./features/accounting/payments/accounting-payments.component').then(m => m.AccountingPaymentsComponent), title: 'Comptabilité - Historique' },
            { path: 'invoices', loadComponent: () => import('./features/accounting/invoices/accounting-invoices.component').then(m => m.AccountingInvoicesComponent), title: 'Comptabilité - Factures' },
            { path: 'unpaid', loadComponent: () => import('./features/accounting/unpaid/accounting-unpaid.component').then(m => m.AccountingUnpaidComponent), title: 'Comptabilité - Impayés' },
            { path: 'reminders', loadComponent: () => import('./features/accounting/unpaid/accounting-unpaid.component').then(m => m.AccountingUnpaidComponent), title: 'Comptabilité - Relances' },
            { path: 'reports', loadComponent: () => import('./features/accounting/reports/accounting-reports.component').then(m => m.AccountingReportsComponent), title: 'Comptabilité - Rapports' },
            { path: 'budget', loadComponent: () => import('./features/accounting/reports/accounting-reports.component').then(m => m.AccountingReportsComponent), title: 'Comptabilité - Budget' },
            { path: 'scholarships', loadComponent: () => import('./features/accounting/invoices/accounting-invoices.component').then(m => m.AccountingInvoicesComponent), title: 'Comptabilité - Bourses' },
            { path: 'messages', loadComponent: () => import('./shared/components/messages/messages.component').then(m => m.MessagesComponent), title: 'Comptabilité - Messagerie' },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },

    // ========================================
    // FALLBACK
    // ========================================
    { path: '**', redirectTo: '' }
];
