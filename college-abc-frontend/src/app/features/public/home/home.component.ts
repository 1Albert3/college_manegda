import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterLink, HeroSliderComponent],
  template: `
    <!-- Section Héros -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
      <!-- Curseur d'arrière-plan -->
      <app-hero-slider [images]="heroImages"></app-hero-slider>

      <!-- Contenu -->
      <div class="relative z-20 text-center text-white px-6 max-w-5xl mx-auto" data-aos="fade-up" data-aos-duration="1000">
        <h1 class="text-3xl md:text-5xl lg:text-7xl font-serif font-bold mb-6 leading-tight drop-shadow-lg">
          L'excellence académique, <br/>
          <span class="text-secondary">la foi en héritage</span>
        </h1>
        <p class="text-xl md:text-2xl mb-12 font-light text-gray-100 max-w-3xl mx-auto drop-shadow-md">
          Un établissement d'exception pour former les esprits et les cœurs des leaders de demain.
        </p>
        <div class="flex flex-col md:flex-row gap-6 justify-center">
          <a routerLink="/inscription" class="px-8 py-4 bg-secondary hover:bg-white hover:text-primary text-white font-bold rounded-full transition-all transform hover:scale-105 shadow-xl border-2 border-secondary">
            Pré-inscrire mon enfant
          </a>
          <a routerLink="/about" class="px-8 py-4 bg-transparent border-2 border-white hover:bg-white hover:text-primary text-white font-bold rounded-full transition-all hover:scale-105 shadow-lg">
            Découvrir le collège
          </a>
        </div>
      </div>
      
      <!-- Indicateur de défilement vers le bas -->
      <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce z-20">
        <i class="pi pi-angle-down text-4xl text-white/80"></i>
      </div>
    </section>

    <!-- Pourquoi nous choisir ? -->
    <section class="py-24 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-20" data-aos="fade-up">
          <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif font-bold text-primary mb-6">Pourquoi choisir le Collège Privé Wend-Manegda ?</h2>
          <div class="w-24 h-1.5 bg-secondary mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
          <!-- Carte 1 -->
          <div class="bg-white p-10 rounded-2xl shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-2 group border-b-4 border-transparent hover:border-secondary" data-aos="fade-up" data-aos-delay="100">
            <div class="w-20 h-20 bg-primary/5 rounded-full flex items-center justify-center mb-8 group-hover:bg-primary group-hover:text-white transition-colors duration-300">
              <i class="pi pi-book text-4xl text-primary group-hover:text-white"></i>
            </div>
            <h3 class="text-2xl font-serif font-bold text-gray-800 mb-4 group-hover:text-primary transition-colors">Pédagogie d'Excellence</h3>
            <p class="text-gray-600 leading-relaxed">
              Un programme rigoureux enrichi par des méthodes modernes, visant 100% de réussite aux examens et l'admission dans les meilleures universités.
            </p>
          </div>

          <!-- Carte 2 -->
          <div class="bg-white p-10 rounded-2xl shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-2 group border-b-4 border-transparent hover:border-secondary" data-aos="fade-up" data-aos-delay="200">
            <div class="w-20 h-20 bg-secondary/10 rounded-full flex items-center justify-center mb-8 group-hover:bg-secondary group-hover:text-white transition-colors duration-300">
              <i class="pi pi-heart text-4xl text-secondary group-hover:text-white"></i>
            </div>
            <h3 class="text-2xl font-serif font-bold text-gray-800 mb-4 group-hover:text-primary transition-colors">Formation Spirituelle</h3>
            <p class="text-gray-600 leading-relaxed">
              Une éducation ancrée dans les valeurs chrétiennes : charité, respect, et service du prochain, pour former des hommes et des femmes intègres.
            </p>
          </div>

          <!-- Carte 3 -->
          <div class="bg-white p-10 rounded-2xl shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-2 group border-b-4 border-transparent hover:border-secondary" data-aos="fade-up" data-aos-delay="300">
            <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-8 group-hover:bg-green-600 group-hover:text-white transition-colors duration-300">
              <i class="pi pi-home text-4xl text-green-600 group-hover:text-white"></i>
            </div>
            <h3 class="text-2xl font-serif font-bold text-gray-800 mb-4 group-hover:text-primary transition-colors">Cadre Épanouissant</h3>
            <p class="text-gray-600 leading-relaxed">
              Un campus sécurisé, moderne et verdoyant, propice à l'étude, au sport et au développement personnel de chaque élève.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Religieuse -->
    <section class="py-24 bg-white overflow-hidden">
      <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row items-center gap-16">
          <!-- Grille d'images -->
          <div class="w-full md:w-1/2 grid grid-cols-2 gap-4 relative" data-aos="fade-right">
             <!-- Élément décoratif -->
             <div class="absolute -top-10 -left-10 w-32 h-32 bg-secondary/20 rounded-full blur-3xl"></div>
             
            <img src="https://images.unsplash.com/photo-1548625361-e88c60eb355c?q=80&w=2070&auto=format&fit=crop" alt="Chapelle" class="rounded-2xl shadow-lg w-full h-80 object-cover transform translate-y-8 hover:scale-105 transition-transform duration-500" />
            <img src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?q=80&w=2073&auto=format&fit=crop" alt="Messe" class="rounded-2xl shadow-lg w-full h-80 object-cover hover:scale-105 transition-transform duration-500" />
          </div>

          <!-- Contenu -->
          <div class="w-full md:w-1/2" data-aos="fade-left">
            <div class="flex items-center gap-2 mb-4">
                <span class="h-px w-8 bg-secondary"></span>
                <h4 class="text-secondary font-bold uppercase tracking-widest text-sm">Pastorale</h4>
            </div>
            <h2 class="text-4xl md:text-5xl font-serif font-bold text-primary mb-8">Grandir dans la Foi</h2>
            <p class="text-gray-600 mb-8 leading-relaxed text-lg">
              Au Collège Privé Wend-Manegda, nous croyons que l'éducation ne se limite pas à l'intellect. Nous accompagnons chaque jeune dans sa quête de sens et sa vie spirituelle, dans le respect de la liberté de chacun.
            </p>
            <ul class="space-y-6 mb-10">
              <li class="flex items-start gap-4">
                <div class="mt-1 bg-primary/10 p-2 rounded-full">
                    <i class="pi pi-check text-primary font-bold"></i>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800">Messes & Célébrations</h5>
                    <p class="text-sm text-gray-500">Des temps forts liturgiques pour rythmer l'année.</p>
                </div>
              </li>
              <li class="flex items-start gap-4">
                <div class="mt-1 bg-primary/10 p-2 rounded-full">
                    <i class="pi pi-check text-primary font-bold"></i>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800">Catéchèse & Culture Religieuse</h5>
                    <p class="text-sm text-gray-500">Un parcours adapté à chaque âge pour approfondir sa foi.</p>
                </div>
              </li>
              <li class="flex items-start gap-4">
                <div class="mt-1 bg-primary/10 p-2 rounded-full">
                    <i class="pi pi-check text-primary font-bold"></i>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800">Solidarité & Service</h5>
                    <p class="text-sm text-gray-500">Apprendre à donner et à servir les plus fragiles.</p>
                </div>
              </li>
            </ul>
            <a routerLink="/religion" class="inline-flex items-center gap-2 text-primary font-bold hover:text-secondary transition-colors text-lg group">
              En savoir plus sur notre projet pastoral 
              <i class="pi pi-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Appel à l'action final -->
    <section class="py-24 bg-primary relative overflow-hidden">
      <!-- Motif d'arrière-plan -->
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 30px 30px;"></div>
      
      <div class="container mx-auto px-6 relative z-10 text-center" data-aos="zoom-in">
        <h2 class="text-4xl md:text-5xl font-serif font-bold text-white mb-8">Prêt à rejoindre la famille Wend-Manegda ?</h2>
        <p class="text-xl text-blue-100 mb-12 max-w-2xl mx-auto leading-relaxed">
          Les inscriptions pour l'année scolaire 2024-2025 sont ouvertes. Offrez à votre enfant un avenir brillant dans un cadre bienveillant.
        </p>
        <a routerLink="/inscription" class="inline-block px-12 py-5 bg-secondary text-white font-bold text-xl rounded-full shadow-2xl hover:bg-white hover:text-primary transition-all transform hover:-translate-y-1 border-2 border-secondary hover:border-white">
          Commencer la pré-inscription
        </a>
      </div>
    </section>
  `
})
export class HomeComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop', // Campus
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2132&auto=format&fit=crop', // Classroom / Students
    'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1986&auto=format&fit=crop', // Graduation / Success
    'https://images.unsplash.com/photo-1598556776374-227366324312?q=80&w=2070&auto=format&fit=crop'  // Library / Study
  ];
}
