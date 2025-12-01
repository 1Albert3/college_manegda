import { Component, HostListener, signal, inject, OnInit } from '@angular/core';
import { RouterOutlet, RouterLink, Router, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';

@Component({
    selector: 'app-public-layout',
    standalone: true,
    imports: [RouterOutlet, RouterLink, CommonModule],
    template: `
    <!-- En-tête -->
    <header [ngClass]="headerClass()" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
      <div class="container mx-auto px-6 flex justify-between items-center h-full">
        <!-- Logo -->
        <a routerLink="/" class="text-2xl font-serif font-bold flex items-center gap-2 transition-colors duration-300 relative z-50">
          <i class="pi pi-shield text-3xl text-secondary"></i>
          <span [class.text-white]="!isSolidHeader()" [class.text-primary]="isSolidHeader()">Collège Privé Wend-Manegda</span>
        </a>

        <!-- Menu Bureau -->
        <nav class="hidden md:flex gap-8">
          <a *ngFor="let item of menuItems" [routerLink]="item.link" 
             class="font-medium hover:text-secondary transition-colors duration-300"
             [class.text-white]="!isSolidHeader()" [class.text-gray-800]="isSolidHeader()">
            {{ item.label }}
          </a>
        </nav>

        <!-- CTA Bureau -->
        <a routerLink="/login" 
           class="hidden md:block px-6 py-2 rounded-full font-semibold transition-all shadow-lg hover:scale-105"
           [class.bg-secondary]="!isSolidHeader()" [class.text-white]="!isSolidHeader()"
           [class.bg-primary]="isSolidHeader()" [class.text-white]="isSolidHeader()">
           Espace Parents
        </a>

        <!-- Bouton Menu Mobile -->
        <button (click)="toggleMobileMenu()" class="md:hidden relative z-50 focus:outline-none">
            <i class="pi text-2xl transition-colors duration-300" 
               [class.pi-bars]="!isMobileMenuOpen()" 
               [class.pi-times]="isMobileMenuOpen()"
               [class.text-white]="!isSolidHeader() && !isMobileMenuOpen()"
               [class.text-primary]="isSolidHeader() || isMobileMenuOpen()">
            </i>
        </button>
      </div>

      <!-- Superposition Menu Mobile -->
      <div class="fixed inset-0 bg-white z-40 flex flex-col items-center justify-center gap-8 transition-transform duration-300 md:hidden"
           [class.translate-y-0]="isMobileMenuOpen()"
           [class.-translate-y-full]="!isMobileMenuOpen()">
           
           <nav class="flex flex-col items-center gap-6 text-xl">
              <a *ngFor="let item of menuItems" [routerLink]="item.link" (click)="closeMobileMenu()"
                 class="font-serif font-bold text-gray-800 hover:text-primary transition-colors">
                 {{ item.label }}
              </a>
           </nav>

           <a routerLink="/login" (click)="closeMobileMenu()"
              class="px-8 py-3 bg-primary text-white rounded-full font-bold shadow-lg hover:bg-secondary transition-colors">
              Espace Parents
           </a>
      </div>
    </header>

    <!-- Contenu Principal -->
    <main>
      <router-outlet></router-outlet>
    </main>

    <!-- Pied de page -->
    <footer class="bg-primary-dark text-white pt-16 pb-8">
      <div class="container mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12">
        <!-- Marque -->
        <div>
          <div class="text-2xl font-serif font-bold text-white mb-4 flex items-center gap-2">
             <i class="pi pi-shield text-3xl text-secondary"></i> Collège Privé Wend-Manegda
          </div>
          <p class="text-gray-300 text-sm leading-relaxed">
            Excellence académique et formation spirituelle pour les leaders de demain.
          </p>
        </div>

        <!-- Liens -->
        <div>
          <h3 class="text-lg font-serif font-bold mb-4 text-secondary">Liens Rapides</h3>
          <ul class="space-y-2 text-gray-300">
            <li><a routerLink="/" class="hover:text-white transition-colors">Accueil</a></li>
            <li><a routerLink="/about" class="hover:text-white transition-colors">Notre Histoire</a></li>
            <li><a routerLink="/inscription" class="hover:text-white transition-colors">Inscriptions</a></li>
            <li><a routerLink="/contact" class="hover:text-white transition-colors">Contact</a></li>
          </ul>
        </div>

        <!-- Contact -->
        <div>
          <h3 class="text-lg font-serif font-bold mb-4 text-secondary">Contact</h3>
          <ul class="space-y-3 text-gray-300">
            <li class="flex items-center gap-3"><i class="pi pi-map-marker text-secondary"></i> 123 Rue de l'Église, Ville</li>
            <li class="flex items-center gap-3"><i class="pi pi-phone text-secondary"></i> +226 00 00 00 00</li>
            <li class="flex items-center gap-3"><i class="pi pi-envelope text-secondary"></i> contact@wend-manegda.bf</li>
          </ul>
        </div>

        <!-- Verset -->
        <div>
          <h3 class="text-lg font-serif font-bold mb-4 text-secondary">Pensée du jour</h3>
          <blockquote class="italic text-gray-300 border-l-4 border-secondary pl-4">
            "Instruis l'enfant selon la voie qu'il doit suivre; Et quand il sera vieux, il ne s'en détournera pas."
            <footer class="text-sm mt-2 not-italic text-secondary">- Proverbes 22:6</footer>
          </blockquote>
        </div>
      </div>
      
      <div class="border-t border-gray-700 mt-12 pt-8 text-center text-gray-400 text-sm">
        &copy; {{ year }} Collège Privé Wend-Manegda. Tous droits réservés.
      </div>
    </footer>
  `
})
export class PublicLayoutComponent implements OnInit {
    private router = inject(Router);
    
    isScrolled = signal(false);
    isMobileMenuOpen = signal(false);
    isLoginPage = signal(false);
    year = new Date().getFullYear();

    menuItems = [
        { label: 'Accueil', link: '/' },
        { label: 'Le Collège', link: '/about' },
        { label: 'Vie Scolaire', link: '/school-life' },
        { label: 'Actualités', link: '/news' },
        { label: 'Inscriptions', link: '/inscription' },
        { label: 'Contact', link: '/contact' },
    ];

    ngOnInit() {
        this.router.events.pipe(
            filter(event => event instanceof NavigationEnd)
        ).subscribe((event: any) => {
            this.isLoginPage.set(event.url.includes('/login'));
            this.isMobileMenuOpen.set(false); // Fermer le menu lors de la navigation
        });
        
        // Vérification initiale
        this.isLoginPage.set(this.router.url.includes('/login'));
    }

    @HostListener('window:scroll', [])
    onWindowScroll() {
        this.isScrolled.set(window.scrollY > 50);
    }

    toggleMobileMenu() {
        this.isMobileMenuOpen.update(v => !v);
    }

    closeMobileMenu() {
        this.isMobileMenuOpen.set(false);
    }

    isSolidHeader() {
        return this.isScrolled() || this.isMobileMenuOpen() || this.isLoginPage();
    }

    headerClass() {
        return this.isSolidHeader()
            ? 'bg-white shadow-md py-2'
            : 'bg-transparent py-6';
    }
}
