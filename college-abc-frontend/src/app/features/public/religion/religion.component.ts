import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
  selector: 'app-religion',
  standalone: true,
  imports: [CommonModule, HeroSliderComponent],
  template: `
    <!-- Hero Section -->
    <section class="relative h-[60vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-5xl md:text-6xl font-serif font-bold mb-4">Formation Humaine & Spirituelle</h1>
        <div class="w-24 h-1 bg-secondary mx-auto"></div>
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

    <!-- Horaires -->
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
  `
})
export class ReligionComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1548625361-e88c60eb355c?q=80&w=2070&auto=format&fit=crop', // Chapel
    'https://images.unsplash.com/photo-1515162305285-0293e4767cc2?q=80&w=2071&auto=format&fit=crop', // Church
    'https://images.unsplash.com/photo-1601142634808-38923eb7c560?q=80&w=2070&auto=format&fit=crop'  // Prayer
  ];
}
