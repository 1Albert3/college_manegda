import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
  selector: 'app-faq',
  standalone: true,
  imports: [CommonModule, HeroSliderComponent],
  template: `
    <!-- Hero Section -->
    <section class="relative h-[40vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-4xl md:text-5xl font-serif font-bold mb-4">Foire Aux Questions</h1>
        <div class="w-24 h-1 bg-secondary mx-auto mb-4"></div>
        <p class="text-xl max-w-2xl mx-auto">Trouvez les réponses à vos questions les plus fréquentes.</p>
      </div>
    </section>

    <div class="py-20 bg-neutral-light min-h-screen">
      <div class="container mx-auto px-6 max-w-4xl">
         
         <div class="space-y-4" data-aos="fade-up">
            <!-- Question 1 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
               <details class="group">
                  <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-6 bg-white hover:bg-gray-50 transition-colors">
                     <span class="text-lg font-bold text-gray-800">Quels sont les documents nécessaires pour l'inscription ?</span>
                     <span class="transition group-open:rotate-180">
                        <i class="pi pi-chevron-down text-secondary"></i>
                     </span>
                  </summary>
                  <div class="text-gray-600 p-6 border-t bg-gray-50">
                     Pour une première inscription, vous aurez besoin de l'acte de naissance, des bulletins des deux dernières années, de 2 photos d'identité et du certificat de scolarité de l'établissement précédent.
                  </div>
               </details>
            </div>

            <!-- Question 2 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
               <details class="group">
                  <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-6 bg-white hover:bg-gray-50 transition-colors">
                     <span class="text-lg font-bold text-gray-800">Proposez-vous un service de cantine ?</span>
                     <span class="transition group-open:rotate-180">
                        <i class="pi pi-chevron-down text-secondary"></i>
                     </span>
                  </summary>
                  <div class="text-gray-600 p-6 border-t bg-gray-50">
                     Oui, notre établissement dispose d'une cantine scolaire proposant des repas équilibrés et variés chaque midi. L'inscription à la cantine est optionnelle et peut se faire au trimestre ou à l'année.
                  </div>
               </details>
            </div>

            <!-- Question 3 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
               <details class="group">
                  <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-6 bg-white hover:bg-gray-50 transition-colors">
                     <span class="text-lg font-bold text-gray-800">Quels sont les horaires des cours ?</span>
                     <span class="transition group-open:rotate-180">
                        <i class="pi pi-chevron-down text-secondary"></i>
                     </span>
                  </summary>
                  <div class="text-gray-600 p-6 border-t bg-gray-50">
                     Les cours débutent à 07h30 et se terminent à 12h30 le matin. L'après-midi, ils reprennent à 15h00 pour finir à 17h00. Le mercredi après-midi est libre.
                  </div>
               </details>
            </div>

            <!-- Question 4 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
               <details class="group">
                  <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-6 bg-white hover:bg-gray-50 transition-colors">
                     <span class="text-lg font-bold text-gray-800">Y a-t-il une tenue scolaire obligatoire ?</span>
                     <span class="transition group-open:rotate-180">
                        <i class="pi pi-chevron-down text-secondary"></i>
                     </span>
                  </summary>
                  <div class="text-gray-600 p-6 border-t bg-gray-50">
                     Oui, le port de l'uniforme est obligatoire pour tous les élèves. Le tissu est disponible à l'intendance de l'école. Les modèles de couture doivent respecter le règlement intérieur.
                  </div>
               </details>
            </div>
         </div>

      </div>
    </div>
  `
})
export class FaqComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=2070&auto=format&fit=crop', // Writing
    'https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=2070&auto=format&fit=crop'  // Meeting
  ];
}
