import { Routes } from '@angular/router';
import { PublicLayoutComponent } from './layouts/public-layout/public-layout.component';
import { HomeComponent } from './features/public/home/home.component';
import { InscriptionComponent } from './features/public/inscription/inscription.component';
import { AboutComponent } from './features/public/about/about.component';
import { ReligionComponent } from './features/public/religion/religion.component';
import { NewsComponent } from './features/public/news/news.component';
import { ContactComponent } from './features/public/contact/contact.component';
import { LoginComponent } from './features/public/login/login.component';

export const routes: Routes = [
    {
        path: '',
        component: PublicLayoutComponent,
        children: [
            { path: '', component: HomeComponent, title: 'Collège ABC - Accueil' },
            { path: 'about', component: AboutComponent, title: 'Collège ABC - Notre Histoire' },
            { path: 'school-life', loadComponent: () => import('./features/public/school-life/school-life.component').then(m => m.SchoolLifeComponent), title: 'Collège ABC - Vie Scolaire' },
            { path: 'news', component: NewsComponent, title: 'Collège ABC - Actualités' },
            { path: 'inscription', component: InscriptionComponent, title: 'Collège ABC - Inscription' },
            { path: 'contact', component: ContactComponent, title: 'Collège ABC - Contact' },
            { path: 'faq', loadComponent: () => import('./features/public/faq/faq.component').then(m => m.FaqComponent), title: 'Collège ABC - FAQ' },
            { path: 'login', component: LoginComponent, title: 'Collège ABC - Espace Parents' },
        ]
    },
    { path: 'parents/dashboard', loadComponent: () => import('./features/parents/dashboard/dashboard.component').then(m => m.ParentDashboardComponent), title: 'Collège ABC - Tableau de Bord Parents' },
    // Fallback
    { path: '**', redirectTo: '' }
];
