import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
  selector: 'app-school-life',
  standalone: true,
  imports: [CommonModule, HeroSliderComponent],
  template: `
    <!-- Hero Section -->
    <section class="relative h-[50vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-4xl md:text-6xl font-serif font-bold mb-4">Vie Scolaire & Foi</h1>
        <div class="w-24 h-1 bg-secondary mx-auto mb-4"></div>
        <p class="text-xl max-w-2xl mx-auto">Un environnement épanouissant pour grandir humainement et spirituellement.</p>
      </div>
    </section>

    <!-- La Pastorale -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
          <div data-aos="fade-right">
            <h2 class="text-3xl font-serif font-bold text-primary mb-6">La Pastorale Scolaire</h2>
            <p class="text-gray-600 leading-relaxed mb-6">
              La pastorale n'est pas une activité annexe, mais le cœur battant de notre établissement. Elle irrigue l'ensemble de la vie scolaire en proposant aux jeunes un chemin de croissance humaine et spirituelle.
            </p>
            <p class="text-gray-600 leading-relaxed mb-6">
              Animée par une équipe dynamique de prêtres, religieuses et laïcs engagés, elle offre des espaces de parole, de prière et d'action.
            </p>
            <ul class="space-y-4">
              <li class="flex items-center gap-3 text-gray-700">
                <i class="pi pi-check-circle text-secondary text-xl"></i> Préparation aux sacrements (Baptême, Communion, Confirmation)
              </li>
              <li class="flex items-center gap-3 text-gray-700">
                <i class="pi pi-check-circle text-secondary text-xl"></i> Retraites spirituelles annuelles
              </li>
              <li class="flex items-center gap-3 text-gray-700">
                <i class="pi pi-check-circle text-secondary text-xl"></i> Actions caritatives (Carême, Noël)
              </li>
            </ul>
          </div>
          <div class="grid grid-cols-2 gap-4" data-aos="fade-left">
             <img src="https://images.unsplash.com/photo-1601142634808-38923eb7c560?q=80&w=2070&auto=format&fit=crop" class="rounded-lg shadow-lg w-full h-64 object-cover" alt="Prière" />
             <img src="https://images.unsplash.com/photo-1491841550275-ad7854e35ca6?q=80&w=1974&auto=format&fit=crop" class="rounded-lg shadow-lg w-full h-64 object-cover mt-8" alt="Bible" />
          </div>
        </div>
      </div>
    </section>

    <!-- Vie Liturgique (Horaires) -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden" data-aos="fade-up">
          <div class="bg-primary p-6 text-center">
            <h3 class="text-2xl font-serif font-bold text-white">Vie Liturgique</h3>
          </div>
          <div class="p-8 md:p-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
              <div>
                <h4 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Messes</h4>
                <ul class="space-y-4">
                  <li class="flex justify-between">
                    <span class="text-gray-600">Mardi & Jeudi (Élèves)</span>
                    <span class="font-bold text-primary">07h15</span>
                  </li>
                  <li class="flex justify-between">
                    <span class="text-gray-600">Dimanche (Ouvert à tous)</span>
                    <span class="font-bold text-primary">09h00</span>
                  </li>
                </ul>
              </div>
              <div>
                <h4 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Confessions & Écoute</h4>
                <ul class="space-y-4">
                  <li class="flex justify-between">
                    <span class="text-gray-600">Mercredi après-midi</span>
                    <span class="font-bold text-primary">14h - 17h</span>
                  </li>
                  <li class="flex justify-between">
                    <span class="text-gray-600">Sur rendez-vous</span>
                    <span class="font-bold text-primary">Aumônerie</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Clubs, Activités, Sorties -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Clubs & Activités</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Au-delà des cours, nous encourageons nos élèves à développer leurs talents.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
           <!-- Club 1 -->
           <div class="group relative overflow-hidden rounded-xl shadow-lg h-80" data-aos="fade-up">
              <img src="https://images.unsplash.com/photo-1544531586-fde5298cdd40?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Club Environnement" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                 <h3 class="text-2xl font-bold text-white mb-2">Club Environnement</h3>
                 <p class="text-gray-200 text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">Jardinage, recyclage et sensibilisation écologique.</p>
              </div>
           </div>
           <!-- Club 2 -->
           <div class="group relative overflow-hidden rounded-xl shadow-lg h-80" data-aos="fade-up" data-aos-delay="100">
              <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Club Théâtre" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                 <h3 class="text-2xl font-bold text-white mb-2">Club Théâtre</h3>
                 <p class="text-gray-200 text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">Expression scénique et préparation du spectacle de fin d'année.</p>
              </div>
           </div>
           <!-- Club 3 -->
           <div class="group relative overflow-hidden rounded-xl shadow-lg h-80" data-aos="fade-up" data-aos-delay="200">
              <img src="https://images.unsplash.com/photo-1555066931-4365d14bab8c?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Club Informatique" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                 <h3 class="text-2xl font-bold text-white mb-2">Club Informatique</h3>
                 <p class="text-gray-200 text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">Initiation au codage et à la robotique.</p>
              </div>
           </div>
        </div>
      </div>
    </section>

    <!-- Planning des Événements -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Agenda</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Les temps forts de l'année scolaire.</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
           <!-- Event 1 -->
           <div class="bg-white p-6 rounded-xl shadow-md flex flex-col md:flex-row gap-6 items-center" data-aos="fade-left">
              <div class="bg-primary/10 text-primary font-bold rounded-lg p-4 text-center min-w-[100px]">
                 <div class="text-3xl">15</div>
                 <div class="text-sm uppercase">Déc</div>
              </div>
              <div class="flex-1 text-center md:text-left">
                 <h3 class="text-xl font-bold text-gray-800 mb-2">Arbre de Noël</h3>
                 <p class="text-gray-600">Célébration de la Nativité avec chants, sketchs et partage de cadeaux.</p>
              </div>
              <div class="text-sm text-gray-500 font-medium">
                 <i class="pi pi-clock mr-1"></i> 14h00 - 18h00
              </div>
           </div>
           <!-- Event 2 -->
           <div class="bg-white p-6 rounded-xl shadow-md flex flex-col md:flex-row gap-6 items-center" data-aos="fade-left" data-aos-delay="100">
              <div class="bg-secondary/10 text-secondary font-bold rounded-lg p-4 text-center min-w-[100px]">
                 <div class="text-3xl">10</div>
                 <div class="text-sm uppercase">Fév</div>
              </div>
              <div class="flex-1 text-center md:text-left">
                 <h3 class="text-xl font-bold text-gray-800 mb-2">Journée Culturelle</h3>
                 <p class="text-gray-600">Valorisation de nos traditions à travers la danse, la cuisine et l'habillement.</p>
              </div>
              <div class="text-sm text-gray-500 font-medium">
                 <i class="pi pi-clock mr-1"></i> 08h00 - 16h00
              </div>
           </div>
           <!-- Event 3 -->
           <div class="bg-white p-6 rounded-xl shadow-md flex flex-col md:flex-row gap-6 items-center" data-aos="fade-left" data-aos-delay="200">
              <div class="bg-primary/10 text-primary font-bold rounded-lg p-4 text-center min-w-[100px]">
                 <div class="text-3xl">25</div>
                 <div class="text-sm uppercase">Mai</div>
              </div>
              <div class="flex-1 text-center md:text-left">
                 <h3 class="text-xl font-bold text-gray-800 mb-2">Fête de l'Excellence</h3>
                 <p class="text-gray-600">Récompense des meilleurs élèves de l'année scolaire.</p>
              </div>
              <div class="text-sm text-gray-500 font-medium">
                 <i class="pi pi-clock mr-1"></i> 09h00 - 12h00
              </div>
           </div>
        </div>
      </div>
    </section>

    <!-- Services Disponibles -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Services Disponibles</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Des infrastructures et services pour faciliter le quotidien.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
           <!-- Cantine -->
           <div class="bg-neutral-light p-8 rounded-xl shadow-lg border-b-4 border-secondary hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up">
              <div class="w-16 h-16 bg-secondary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
                 <i class="pi pi-shopping-bag text-3xl text-secondary"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">Cantine Scolaire</h3>
              <p class="text-gray-600 text-center mb-4">Un service de restauration équilibré et sain, proposant des menus variés chaque jour pour le déjeuner.</p>
              <ul class="text-sm text-gray-500 space-y-2">
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Repas chauds</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Produits locaux</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Hygiène stricte</li>
              </ul>
           </div>

           <!-- Transport -->
           <div class="bg-neutral-light p-8 rounded-xl shadow-lg border-b-4 border-primary hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="100">
              <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
                 <i class="pi pi-car text-3xl text-primary"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">Transport Scolaire</h3>
              <p class="text-gray-600 text-center mb-4">Un réseau de bus desservant les principaux quartiers de la ville pour un trajet sécurisé.</p>
              <ul class="text-sm text-gray-500 space-y-2">
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-secondary"></i> Chauffeurs qualifiés</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-secondary"></i> Ponctualité</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-secondary"></i> Sécurité garantie</li>
              </ul>
           </div>

           <!-- Activités Parascolaires -->
           <div class="bg-neutral-light p-8 rounded-xl shadow-lg border-b-4 border-secondary hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="200">
              <div class="w-16 h-16 bg-secondary/10 rounded-full flex items-center justify-center mb-6 mx-auto">
                 <i class="pi pi-palette text-3xl text-secondary"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">Activités Parascolaires</h3>
              <p class="text-gray-600 text-center mb-4">Des ateliers artistiques, sportifs et culturels pour l'épanouissement de l'enfant après les cours.</p>
              <ul class="text-sm text-gray-500 space-y-2">
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Musique & Danse</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Arts plastiques</li>
                 <li class="flex items-center justify-center gap-2"><i class="pi pi-check text-primary"></i> Sports collectifs</li>
              </ul>
           </div>
        </div>
      </div>
    </section>

    <!-- Règlement Intérieur -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
           <div data-aos="fade-right">
              <h2 class="text-3xl font-serif font-bold text-primary mb-6">Règlement Intérieur</h2>
              <p class="text-gray-600 leading-relaxed mb-6">
                 Le respect des règles de vie commune est essentiel pour garantir un climat propice au travail et à l'épanouissement de chacun. Notre règlement intérieur définit les droits et devoirs de chaque élève.
              </p>
              <ul class="space-y-4 mb-8">
                 <li class="flex items-start gap-3">
                    <i class="pi pi-check-circle text-secondary mt-1"></i>
                    <span class="text-gray-700">Ponctualité et assiduité aux cours</span>
                 </li>
                 <li class="flex items-start gap-3">
                    <i class="pi pi-check-circle text-secondary mt-1"></i>
                    <span class="text-gray-700">Port de l'uniforme réglementaire</span>
                 </li>
                 <li class="flex items-start gap-3">
                    <i class="pi pi-check-circle text-secondary mt-1"></i>
                    <span class="text-gray-700">Respect mutuel et politesse</span>
                 </li>
                 <li class="flex items-start gap-3">
                    <i class="pi pi-check-circle text-secondary mt-1"></i>
                    <span class="text-gray-700">Interdiction des téléphones portables en classe</span>
                 </li>
              </ul>
              <button class="px-8 py-3 bg-primary text-white font-bold rounded-full hover:bg-primary-dark transition-colors shadow-lg flex items-center gap-2">
                 <i class="pi pi-download"></i> Télécharger le Règlement
              </button>
           </div>
           <div class="relative" data-aos="fade-left">
              <div class="absolute inset-0 bg-secondary transform translate-x-6 translate-y-6 rounded-2xl"></div>
              <img src="https://images.unsplash.com/photo-1577896335477-2858506f9796?q=80&w=2070&auto=format&fit=crop" class="relative rounded-2xl shadow-xl w-full object-cover" alt="Discipline" />
           </div>
        </div>
      </div>
    </section>
  `
})
export class SchoolLifeComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1523580494863-6f3031224c94?q=80&w=2070&auto=format&fit=crop', // Activities
    'https://images.unsplash.com/photo-1511632765486-a01980e01a18?q=80&w=2070&auto=format&fit=crop', // Theatre/Art
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2132&auto=format&fit=crop'  // Classroom/Rules
  ];
}
