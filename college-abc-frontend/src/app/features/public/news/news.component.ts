import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';
import { NewsService, NewsItem, OfficialDocument } from '../../../core/services/news.service';
import { Observable } from 'rxjs';
import { Tilt3dDirective } from '../../../shared/directives/tilt-3d.directive';

@Component({
    selector: 'app-news',
    standalone: true,
    imports: [CommonModule, HeroSliderComponent, Tilt3dDirective],
    template: `
    <!-- Hero Section -->
    <section class="relative h-[50vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-4xl md:text-6xl font-serif font-bold mb-4">Actualités</h1>
        <div class="w-24 h-1 bg-secondary mx-auto mb-4"></div>
        <p class="text-xl max-w-2xl mx-auto">Restez informés de la vie de notre établissement.</p>
      </div>
    </section>

    <!-- Informations Importantes -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">Informations Importantes</h2>
          <div class="w-16 h-1 bg-secondary mx-auto rounded-full"></div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
          <!-- Info Card 1 -->
          <div appTilt3d class="bg-red-50 border-l-4 border-red-500 p-6 rounded-r-lg shadow-sm flex items-start gap-4 transform transition-all duration-300" data-aos="fade-right">
             <div class="bg-red-100 p-3 rounded-full shrink-0">
                <i class="pi pi-exclamation-circle text-red-600 text-xl"></i>
             </div>
             <div>
                <h3 class="font-bold text-red-800 text-lg mb-2">Réinscriptions 2024-2025</h3>
                <p class="text-red-700 text-sm">La date limite pour les réinscriptions est fixée au 30 Juillet 2024. Passé ce délai, les places ne seront plus garanties.</p>
             </div>
          </div>
          
          <!-- Info Card 2 -->
          <div appTilt3d class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg shadow-sm flex items-start gap-4 transform transition-all duration-300" data-aos="fade-left">
             <div class="bg-blue-100 p-3 rounded-full shrink-0">
                <i class="pi pi-info-circle text-blue-600 text-xl"></i>
             </div>
             <div>
                <h3 class="font-bold text-blue-800 text-lg mb-2">Réunion des Parents d'Élèves</h3>
                <p class="text-blue-700 text-sm">Une assemblée générale se tiendra le Samedi 15 Juin à 09h00 dans l'amphithéâtre du collège.</p>
             </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Activités du Collège -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">Activités du Collège</h2>
          <p class="text-gray-600">La vie scolaire, culturelle et sportive.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <ng-container *ngIf="news$ | async as newsList">
            <article *ngFor="let news of newsList" appTilt3d class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300" data-aos="fade-up">
                <div class="h-48 overflow-hidden">
                <img [src]="news.imageUrl" [alt]="news.category" class="w-full h-full object-cover transform hover:scale-110 transition-transform duration-500" />
                </div>
                <div class="p-6">
                <div class="text-xs font-bold text-secondary uppercase tracking-wider mb-2">{{ news.category }}</div>
                <h2 class="text-xl font-bold text-gray-800 mb-3 hover:text-primary transition-colors">{{ news.title }}</h2>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                    {{ news.excerpt }}
                </p>
                <div class="flex justify-between items-center border-t pt-4">
                    <span class="text-gray-400 text-xs"><i class="pi pi-calendar mr-1"></i> {{ news.date | date:'dd MMM yyyy' }}</span>
                    <button class="text-primary font-bold text-sm hover:text-secondary">Lire plus</button>
                </div>
                </div>
            </article>
          </ng-container>
        </div>
      </div>
    </section>

    <!-- Communiqués Publics -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6 max-w-4xl">
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">Communiqués Publics</h2>
          <p class="text-gray-600">Documents officiels et annonces de la direction.</p>
        </div>

        <div class="space-y-4">
           <ng-container *ngIf="documents$ | async as docs">
               <div *ngFor="let doc of docs" appTilt3d class="bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-secondary transition-colors flex items-center justify-between group" data-aos="fade-up">
                  <div class="flex items-center gap-4">
                     <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center text-red-600 shrink-0">
                        <i class="pi pi-file-pdf text-2xl"></i>
                     </div>
                     <div>
                        <h3 class="font-bold text-gray-800 group-hover:text-primary transition-colors">{{ doc.title }}</h3>
                        <p class="text-sm text-gray-500">Publié le {{ doc.date | date:'dd MMM yyyy' }}</p>
                     </div>
                  </div>
                  <button class="w-10 h-10 rounded-full bg-white shadow-md flex items-center justify-center text-gray-400 group-hover:text-secondary group-hover:scale-110 transition-all">
                     <i class="pi pi-download"></i>
                  </button>
               </div>
           </ng-container>
        </div>
      </div>
    </section>
  `
})
export class NewsComponent implements OnInit {
  private newsService = inject(NewsService);
  
  news$!: Observable<NewsItem[]>;
  documents$!: Observable<OfficialDocument[]>;

  heroImages = [
    'https://images.unsplash.com/photo-1504711434969-e33886168f5c?q=80&w=2070&auto=format&fit=crop', // News/Reading
    'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070&auto=format&fit=crop', // Library
    'https://images.unsplash.com/photo-1577896335477-2858506f9796?q=80&w=2070&auto=format&fit=crop'  // Students
  ];

  ngOnInit() {
    this.news$ = this.newsService.getLatestNews();
    this.documents$ = this.newsService.getOfficialDocuments();
  }
}
