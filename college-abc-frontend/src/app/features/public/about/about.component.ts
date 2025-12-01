import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';

@Component({
  selector: 'app-about',
  standalone: true,
  imports: [CommonModule, HeroSliderComponent],
  template: `
    <!-- Section Héros -->
    <section class="relative h-[60vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-5xl md:text-6xl font-serif font-bold mb-4">À Propos</h1>
        <div class="w-24 h-1 bg-secondary mx-auto"></div>
        <p class="text-xl mt-4 max-w-2xl mx-auto">Découvrez l'histoire, la vision et l'équipe qui font l'excellence du Collège Privé Wend-Manegda.</p>
      </div>
    </section>

    <!-- Historique (Chronologie) -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-primary mb-4">Notre Histoire</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Un parcours marqué par la croissance et l'excellence.</p>
        </div>
        <div class="relative border-l-4 border-primary/20 ml-6 md:ml-1/2 space-y-12">
          <!-- Événement 1 -->
          <div class="relative pl-12 md:pl-0" data-aos="fade-up">
            <div class="absolute -left-[14px] md:left-1/2 md:-ml-[14px] w-6 h-6 bg-secondary rounded-full border-4 border-white shadow-lg z-10"></div>
            <div class="md:w-1/2 md:pr-12 md:text-right md:ml-auto md:mr-auto md:absolute md:left-0 md:top-0">
               <span class="text-secondary font-bold text-xl">1999</span>
               <h3 class="text-2xl font-bold text-gray-800 mb-2">Fondation du Collège</h3>
               <p class="text-gray-600">Ouverture des premières classes de 6ème avec 50 élèves.</p>
            </div>
          </div>
          <!-- Événement 2 -->
          <div class="relative pl-12 md:pl-0" data-aos="fade-up" data-aos-delay="100">
            <div class="absolute -left-[14px] md:left-1/2 md:-ml-[14px] w-6 h-6 bg-primary rounded-full border-4 border-white shadow-lg z-10"></div>
            <div class="md:w-1/2 md:pl-12 md:ml-auto">
               <span class="text-secondary font-bold text-xl">2005</span>
               <h3 class="text-2xl font-bold text-gray-800 mb-2">Extension du Campus</h3>
               <p class="text-gray-600">Construction du bâtiment administratif et de la bibliothèque.</p>
            </div>
          </div>
           <!-- Événement 3 -->
          <div class="relative pl-12 md:pl-0" data-aos="fade-up" data-aos-delay="200">
            <div class="absolute -left-[14px] md:left-1/2 md:-ml-[14px] w-6 h-6 bg-secondary rounded-full border-4 border-white shadow-lg z-10"></div>
            <div class="md:w-1/2 md:pr-12 md:text-right md:ml-auto md:mr-auto md:absolute md:left-0 md:top-0">
               <span class="text-secondary font-bold text-xl">2015</span>
               <h3 class="text-2xl font-bold text-gray-800 mb-2">Ouverture du Second Cycle</h3>
               <p class="text-gray-600">Lancement des classes de Lycée (2nde, 1ère, Tle).</p>
            </div>
          </div>
          <!-- Événement 4 -->
          <div class="relative pl-12 md:pl-0" data-aos="fade-up" data-aos-delay="300">
            <div class="absolute -left-[14px] md:left-1/2 md:-ml-[14px] w-6 h-6 bg-primary rounded-full border-4 border-white shadow-lg z-10"></div>
            <div class="md:w-1/2 md:pl-12 md:ml-auto">
               <span class="text-secondary font-bold text-xl">2024</span>
               <h3 class="text-2xl font-bold text-gray-800 mb-2">Digitalisation</h3>
               <p class="text-gray-600">Intégration des nouvelles technologies dans toutes les salles de classe.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Le Mot du Directeur -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row items-center gap-12">
          <div class="w-full md:w-1/3" data-aos="fade-right">
            <div class="relative">
              <div class="absolute inset-0 bg-secondary transform translate-x-4 translate-y-4 rounded-lg"></div>
              <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=1974&auto=format&fit=crop" alt="Directeur" class="relative rounded-lg shadow-xl w-full h-auto object-cover grayscale hover:grayscale-0 transition-all duration-500" />
            </div>
          </div>
          <div class="w-full md:w-2/3" data-aos="fade-left">
            <h2 class="text-3xl font-serif font-bold text-primary mb-6">Le Mot du Directeur</h2>
            <blockquote class="text-xl italic text-gray-600 mb-6 border-l-4 border-secondary pl-6">
              "Bienvenue au Collège Privé Wend-Manegda. Depuis plus de 20 ans, nous nous engageons à offrir une éducation qui allie rigueur intellectuelle et élévation spirituelle. Notre mission est de former des citoyens responsables, éclairés par la foi et compétents pour le monde de demain."
            </blockquote>
            <p class="text-gray-600 leading-relaxed mb-4">
              Notre établissement se distingue par un encadrement personnalisé et une exigence bienveillante. Nous croyons que chaque élève a un talent unique qu'il nous appartient de faire fructifier.
            </p>
            <div class="font-bold text-primary text-lg">M. Jean KABORE</div>
            <div class="text-sm text-gray-500">Directeur Général</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Vision, Mission, Valeurs -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-primary mb-4">Vision & Valeurs</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Les piliers de notre projet éducatif.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow border-t-4 border-primary" data-aos="fade-up" data-aos-delay="100">
            <i class="pi pi-compass text-4xl text-secondary mb-6"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-3">Notre Vision</h3>
            <p class="text-gray-600">Être une référence nationale dans la formation de leaders intègres et compétents.</p>
          </div>
          <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow border-t-4 border-secondary" data-aos="fade-up" data-aos-delay="200">
            <i class="pi pi-flag text-4xl text-secondary mb-6"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-3">Notre Mission</h3>
            <p class="text-gray-600">Offrir un enseignement de qualité accessible à tous, dans le respect des valeurs humaines et chrétiennes.</p>
          </div>
          <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow border-t-4 border-primary" data-aos="fade-up" data-aos-delay="300">
            <i class="pi pi-heart text-4xl text-secondary mb-6"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-3">Nos Valeurs</h3>
            <p class="text-gray-600">Discipline, Travail, Excellence, Solidarité, Foi.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Cycles Enseignés -->
    <section class="py-20 bg-primary text-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-white mb-4">Nos Cycles d'Enseignement</h2>
          <p class="text-blue-100 max-w-2xl mx-auto">Un accompagnement complet de la maternelle au lycée.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
           <!-- Maternelle -->
           <div class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all" data-aos="fade-up">
              <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mb-6 text-2xl font-bold">M</div>
              <h3 class="text-2xl font-bold mb-4">Maternelle</h3>
              <p class="text-blue-100 mb-4">L'éveil et la socialisation.</p>
              <ul class="space-y-2 text-sm text-blue-50">
                <li><i class="pi pi-check mr-2 text-secondary"></i> Petite Section</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> Moyenne Section</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> Grande Section</li>
              </ul>
           </div>
           <!-- Primaire -->
           <div class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all" data-aos="fade-up" data-aos-delay="100">
              <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mb-6 text-2xl font-bold">P</div>
              <h3 class="text-2xl font-bold mb-4">Primaire</h3>
              <p class="text-blue-100 mb-4">Les fondamentaux.</p>
              <ul class="space-y-2 text-sm text-blue-50">
                <li><i class="pi pi-check mr-2 text-secondary"></i> CP1 - CP2</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> CE1 - CE2</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> CM1 - CM2</li>
              </ul>
           </div>
           <!-- Secondaire -->
           <div class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all" data-aos="fade-up" data-aos-delay="200">
              <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mb-6 text-2xl font-bold">S</div>
              <h3 class="text-2xl font-bold mb-4">Secondaire</h3>
              <p class="text-blue-100 mb-4">L'approfondissement et l'orientation.</p>
              <ul class="space-y-2 text-sm text-blue-50">
                <li><i class="pi pi-check mr-2 text-secondary"></i> Collège (6ème - 3ème)</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> Lycée (2nde - Tle)</li>
                <li><i class="pi pi-check mr-2 text-secondary"></i> Séries A, C, D</li>
              </ul>
           </div>
        </div>
      </div>
    </section>

    <!-- Résultats Historiques -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-primary mb-4">Nos Résultats</h2>
          <p class="text-gray-600">L'excellence académique en chiffres.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
           <div class="bg-neutral-light p-6 rounded-xl shadow-md text-center" data-aos="zoom-in">
              <div class="text-5xl font-bold text-secondary mb-2">98%</div>
              <div class="text-gray-600 font-medium">BEPC 2023</div>
           </div>
           <div class="bg-neutral-light p-6 rounded-xl shadow-md text-center" data-aos="zoom-in" data-aos-delay="100">
              <div class="text-5xl font-bold text-secondary mb-2">95%</div>
              <div class="text-gray-600 font-medium">BAC 2023</div>
           </div>
           <div class="bg-neutral-light p-6 rounded-xl shadow-md text-center" data-aos="zoom-in" data-aos-delay="200">
              <div class="text-5xl font-bold text-secondary mb-2">100%</div>
              <div class="text-gray-600 font-medium">CEP 2023</div>
           </div>
           <div class="bg-neutral-light p-6 rounded-xl shadow-md text-center" data-aos="zoom-in" data-aos-delay="300">
              <div class="text-5xl font-bold text-secondary mb-2">+50</div>
              <div class="text-gray-600 font-medium">Mentions Très Bien</div>
           </div>
        </div>
      </div>
    </section>

    <!-- Équipe Administrative -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-primary mb-4">L'Équipe Administrative</h2>
          <p class="text-gray-600">Des professionnels dévoués à la réussite de vos enfants.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
           <!-- Membre 1 -->
           <div class="text-center group" data-aos="fade-up">
              <div class="relative overflow-hidden rounded-xl mb-4 shadow-lg">
                 <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=1974&auto=format&fit=crop" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500" alt="Directeur" />
              </div>
              <h3 class="text-xl font-bold text-gray-800">M. Jean KABORE</h3>
              <p class="text-secondary font-medium">Directeur Général</p>
           </div>
           <!-- Membre 2 -->
           <div class="text-center group" data-aos="fade-up" data-aos-delay="100">
              <div class="relative overflow-hidden rounded-xl mb-4 shadow-lg">
                 <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=1976&auto=format&fit=crop" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500" alt="Directrice Études" />
              </div>
              <h3 class="text-xl font-bold text-gray-800">Mme Sarah OUEDRAOGO</h3>
              <p class="text-secondary font-medium">Directrice des Études</p>
           </div>
           <!-- Membre 3 -->
           <div class="text-center group" data-aos="fade-up" data-aos-delay="200">
              <div class="relative overflow-hidden rounded-xl mb-4 shadow-lg">
                 <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=1974&auto=format&fit=crop" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500" alt="Censeur" />
              </div>
              <h3 class="text-xl font-bold text-gray-800">M. Paul SANKARA</h3>
              <p class="text-secondary font-medium">Censeur</p>
           </div>
           <!-- Membre 4 -->
           <div class="text-center group" data-aos="fade-up" data-aos-delay="300">
              <div class="relative overflow-hidden rounded-xl mb-4 shadow-lg">
                 <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=1961&auto=format&fit=crop" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500" alt="Intendante" />
              </div>
              <h3 class="text-xl font-bold text-gray-800">Mme Aminata DIALLO</h3>
              <p class="text-secondary font-medium">Intendante</p>
           </div>
        </div>
      </div>
    </section>

    <!-- Projet Pédagogique -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
           <div data-aos="fade-right">
              <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=2022&auto=format&fit=crop" class="rounded-2xl shadow-2xl w-full object-cover h-[500px]" alt="Pédagogie" />
           </div>
           <div data-aos="fade-left">
              <h2 class="text-4xl font-serif font-bold text-primary mb-6">Notre Projet Pédagogique</h2>
              <p class="text-gray-600 text-lg leading-relaxed mb-6">
                Au cœur de notre démarche, l'élève est acteur de ses apprentissages. Nous privilégions une approche qui stimule la curiosité, l'esprit critique et l'autonomie.
              </p>
              <div class="space-y-6">
                <div class="flex gap-4">
                   <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center shrink-0">
                      <i class="pi pi-desktop text-secondary text-xl"></i>
                   </div>
                   <div>
                      <h4 class="font-bold text-gray-800 text-lg">Innovation Numérique</h4>
                      <p class="text-gray-600">Utilisation raisonnée des outils digitaux pour enrichir les cours.</p>
                   </div>
                </div>
                <div class="flex gap-4">
                   <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center shrink-0">
                      <i class="pi pi-globe text-secondary text-xl"></i>
                   </div>
                   <div>
                      <h4 class="font-bold text-gray-800 text-lg">Langues Vivantes</h4>
                      <p class="text-gray-600">Anglais renforcé dès le primaire et clubs de langues.</p>
                   </div>
                </div>
                <div class="flex gap-4">
                   <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center shrink-0">
                      <i class="pi pi-users text-secondary text-xl"></i>
                   </div>
                   <div>
                      <h4 class="font-bold text-gray-800 text-lg">Suivi Personnalisé</h4>
                      <p class="text-gray-600">Tutorat et soutien scolaire pour ne laisser personne au bord du chemin.</p>
                   </div>
                </div>
              </div>
           </div>
        </div>
      </div>
    </section>

    <!-- Galerie Photos/Vidéos -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-serif font-bold text-primary mb-4">Vie au Collège</h2>
          <p class="text-gray-600">Retour en images sur nos activités.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
           <div class="col-span-2 row-span-2 relative group overflow-hidden rounded-2xl shadow-lg" data-aos="fade-right">
              <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="Campus" />
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                 <span class="text-white font-bold text-xl">Notre Campus</span>
              </div>
           </div>
           <div class="relative group overflow-hidden rounded-2xl shadow-lg h-64" data-aos="fade-down">
              <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2132&auto=format&fit=crop" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="Classe" />
           </div>
           <div class="relative group overflow-hidden rounded-2xl shadow-lg h-64" data-aos="fade-down" data-aos-delay="100">
              <img src="https://images.unsplash.com/photo-1546410531-bb4caa6b424d?q=80&w=2071&auto=format&fit=crop" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="Sport" />
           </div>
           <div class="relative group overflow-hidden rounded-2xl shadow-lg h-64" data-aos="fade-up">
              <img src="https://images.unsplash.com/photo-1511629091441-ee46146481b6?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="Conférence" />
           </div>
           <div class="relative group overflow-hidden rounded-2xl shadow-lg h-64" data-aos="fade-up" data-aos-delay="100">
              <img src="https://images.unsplash.com/photo-1427504743055-e9ba63450058?q=80&w=2074&auto=format&fit=crop" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="Bibliothèque" />
           </div>
        </div>
      </div>
    </section>
  `
})
export class AboutComponent {
  heroImages = [
    'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070&auto=format&fit=crop', // Library
    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop', // Campus
    'https://images.unsplash.com/photo-1577896335477-2858506f9796?q=80&w=2070&auto=format&fit=crop'  // Students
  ];
}
