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
            { path: '', component: HomeComponent, title: 'Collège Privé Wend-Manegda - Accueil' },
            { path: 'about', component: AboutComponent, title: 'Collège Privé Wend-Manegda - Notre Histoire' },
            { path: 'school-life', loadComponent: () => import('./features/public/school-life/school-life.component').then(m => m.SchoolLifeComponent), title: 'Collège Privé Wend-Manegda - Vie Scolaire' },
            { path: 'news', component: NewsComponent, title: 'Collège Privé Wend-Manegda - Actualités' },
            { path: 'inscription', component: InscriptionComponent, title: 'Collège Privé Wend-Manegda - Inscription' },
            { path: 'contact', component: ContactComponent, title: 'Collège Privé Wend-Manegda - Contact' },
            { path: 'faq', loadComponent: () => import('./features/public/faq/faq.component').then(m => m.FaqComponent), title: 'Collège Privé Wend-Manegda - FAQ' },
            { path: 'login', component: LoginComponent, title: 'Collège Privé Wend-Manegda - Espace Parents' },
        ]
    },
    { path: 'parents/dashboard', loadComponent: () => import('./features/parents/dashboard/dashboard.component').then(m => m.ParentDashboardComponent), title: 'Collège Privé Wend-Manegda - Tableau de Bord Parents' },
    // Redirection par défaut
    { path: '**', redirectTo: '' }
];
