import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
    selector: 'app-contact',
    standalone: true,
    imports: [CommonModule, HeroSliderComponent, RouterLink],
    template: `
    <!-- Section Héros -->
    <section class="relative h-[50vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-4xl md:text-6xl font-serif font-bold mb-4">Contact</h1>
        <div class="w-24 h-1 bg-secondary mx-auto mb-4"></div>
        <p class="text-xl max-w-2xl mx-auto">Nous sommes à votre écoute.</p>
      </div>
    </section>

    <div class="py-20 bg-neutral-light min-h-screen">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">Contactez-nous</h2>
          <p class="text-gray-600">Une question ? Besoin d'informations ?</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-20">
          <!-- Formulaire -->
          <div class="bg-white p-8 rounded-2xl shadow-xl" data-aos="fade-right">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Envoyez-nous un message</h2>
            <form class="space-y-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nom complet</label>
                <input type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" placeholder="Votre nom" />
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                <input type="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" placeholder="votre@email.com" />
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Sujet</label>
                <select class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50">
                  <option>Renseignements Inscription</option>
                  <option>Vie Scolaire</option>
                  <option>Comptabilité</option>
                  <option>Autre</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Message</label>
                <textarea rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" placeholder="Votre message..."></textarea>
              </div>
              <button type="button" class="w-full py-4 bg-primary text-white font-bold rounded-lg shadow-lg hover:bg-primary-light transition-all">
                Envoyer le message
              </button>
            </form>
          </div>

          <!-- Infos & Carte -->
          <div class="space-y-8" data-aos="fade-left">
            <!-- Cartes d'information -->
            <div class="grid grid-cols-1 gap-6">
              <a href="https://maps.google.com/?q=123+Rue+de+l'Église,+Ouagadougou" target="_blank" class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4 hover:shadow-lg transition-all hover:bg-gray-50 group cursor-pointer">
                <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center text-secondary text-xl group-hover:bg-secondary group-hover:text-white transition-colors">
                  <i class="pi pi-map-marker"></i>
                </div>
                <div>
                  <h3 class="font-bold text-gray-800">Adresse</h3>
                  <p class="text-gray-600 text-sm">123 Rue de l'Église, Quartier Saint-Léon, Ouagadougou</p>
                </div>
              </a>
              
              <a href="tel:+22625000000" class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4 hover:shadow-lg transition-all hover:bg-gray-50 group cursor-pointer">
                <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center text-secondary text-xl group-hover:bg-secondary group-hover:text-white transition-colors">
                  <i class="pi pi-phone"></i>
                </div>
                <div>
                  <h3 class="font-bold text-gray-800">Téléphone</h3>
                  <p class="text-gray-600 text-sm">+226 25 00 00 00 / +226 70 00 00 00</p>
                </div>
              </a>

              <a href="mailto:secretariat@wend-manegda.bf" class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4 hover:shadow-lg transition-all hover:bg-gray-50 group cursor-pointer">
                <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center text-secondary text-xl group-hover:bg-secondary group-hover:text-white transition-colors">
                  <i class="pi pi-envelope"></i>
                </div>
                <div>
                  <h3 class="font-bold text-gray-800">Email</h3>
                  <p class="text-gray-600 text-sm">secretariat@wend-manegda.bf</p>
                </div>
              </a>
            </div>

            <!-- Boutons Réseaux Sociaux -->
            <div class="flex gap-4 justify-center md:justify-start pt-4">
               <a href="#" class="w-12 h-12 bg-[#1877F2] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                  <i class="pi pi-facebook text-xl"></i>
               </a>
               <a href="#" class="w-12 h-12 bg-[#25D366] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                  <i class="pi pi-whatsapp text-xl"></i>
               </a>
               <a href="#" class="w-12 h-12 bg-[#0A66C2] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                  <i class="pi pi-linkedin text-xl"></i>
               </a>
               <a href="#" class="w-12 h-12 bg-[#E4405F] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                  <i class="pi pi-instagram text-xl"></i>
               </a>
            </div>

            <!-- Emplacement Carte -->
            <div class="bg-gray-200 rounded-2xl h-80 w-full flex items-center justify-center relative overflow-hidden shadow-inner group">
               <iframe 
                 src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3897.356247321456!2d-1.533333!3d12.366667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xe2e95ecceaa44cd%3A0x799f677f827d543!2sOuagadougou%2C%20Burkina%20Faso!5e0!3m2!1sen!2s!4v1625000000000!5m2!1sen!2s" 
                 width="100%" 
                 height="100%" 
                 style="border:0;" 
                 allowfullscreen="" 
                 loading="lazy"
                 class="absolute inset-0 grayscale group-hover:grayscale-0 transition-all duration-500">
               </iframe>
            </div>
          </div>
        </div>

        <!-- Appel à l'action FAQ -->
        <div class="max-w-4xl mx-auto text-center mt-20" data-aos="fade-up">
           <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
             <h2 class="text-2xl font-serif font-bold text-primary mb-4">Besoin d'aide supplémentaire ?</h2>
             <p class="text-gray-600 mb-8 text-lg">Vous rencontrez des soucis ? Consultez notre FAQ pour trouver des réponses rapides.</p>
             <a routerLink="/faq" class="inline-flex items-center gap-2 px-8 py-3 bg-secondary text-white font-bold rounded-full hover:bg-secondary-dark transition-all shadow-md hover:shadow-xl transform hover:-translate-y-1">
               <i class="pi pi-question-circle text-xl"></i>
               Consulter notre FAQ
             </a>
           </div>
        </div>

      </div>
    </div>
  `
})
export class ContactComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop', // Contact/Phone/Laptop
    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop', // Campus
    'https://images.unsplash.com/photo-1577896335477-2858506f9796?q=80&w=2070&auto=format&fit=crop'  // Students
  ];
}
