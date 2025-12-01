import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { HeroSliderComponent } from '../../../shared/components/hero-slider/hero-slider.component';
import { EnrollmentService } from '../../../core/services/enrollment.service';
import { Tilt3dDirective } from '../../../shared/directives/tilt-3d.directive';

@Component({
  selector: 'app-inscription',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, HeroSliderComponent, Tilt3dDirective],
  template: `
    <!-- Hero Section -->
    <section class="relative h-[50vh] flex items-center justify-center overflow-hidden">
      <app-hero-slider [images]="heroImages"></app-hero-slider>
      <div class="relative z-10 text-center text-white px-6" data-aos="fade-up">
        <h1 class="text-4xl md:text-6xl font-serif font-bold mb-4">Pré-inscription</h1>
        <div class="w-24 h-1 bg-secondary mx-auto mb-4"></div>
        <p class="text-xl max-w-2xl mx-auto">Rejoignez l'excellence académique et spirituelle.</p>
      </div>
    </section>

    <!-- Conditions d'Admission -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Conditions d'Admission</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Les critères pour rejoindre notre établissement.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
           <div appTilt3d class="bg-neutral-light p-8 rounded-xl border-t-4 border-primary shadow-lg transform transition-all duration-300" data-aos="fade-up">
              <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mb-6 text-primary text-2xl">
                 <i class="pi pi-file"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4">Dossier Scolaire</h3>
              <p class="text-gray-600">Présentation des bulletins de notes des deux dernières années avec une moyenne satisfaisante.</p>
           </div>
           <div appTilt3d class="bg-neutral-light p-8 rounded-xl border-t-4 border-secondary shadow-lg transform transition-all duration-300" data-aos="fade-up" data-aos-delay="100">
              <div class="w-14 h-14 bg-secondary/10 rounded-full flex items-center justify-center mb-6 text-secondary text-2xl">
                 <i class="pi pi-pencil"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4">Test de Niveau</h3>
              <p class="text-gray-600">Un test écrit en Français et Mathématiques est obligatoire pour toute nouvelle inscription.</p>
           </div>
           <div appTilt3d class="bg-neutral-light p-8 rounded-xl border-t-4 border-primary shadow-lg transform transition-all duration-300" data-aos="fade-up" data-aos-delay="200">
              <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mb-6 text-primary text-2xl">
                 <i class="pi pi-comments"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-4">Entretien</h3>
              <p class="text-gray-600">Un entretien avec l'élève et ses parents pour valider l'adhésion au projet éducatif.</p>
           </div>
        </div>
      </div>
    </section>

    <!-- Procédure -->
    <section class="py-20 bg-neutral-light">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Procédure d'Inscription</h2>
          <div class="flex justify-center gap-4 mb-8">
             <button (click)="activeTab = 'new'" [class]="activeTab === 'new' ? 'bg-primary text-white' : 'bg-white text-gray-600'" class="px-6 py-2 rounded-full font-bold transition-all shadow-md">Nouveaux Élèves</button>
             <button (click)="activeTab = 'old'" [class]="activeTab === 'old' ? 'bg-primary text-white' : 'bg-white text-gray-600'" class="px-6 py-2 rounded-full font-bold transition-all shadow-md">Réinscriptions</button>
          </div>
        </div>

        <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-2xl shadow-xl" data-aos="fade-up">
           <div *ngIf="activeTab === 'new'" class="space-y-6">
              <h3 class="text-2xl font-bold text-gray-800 mb-6">Pour les Nouveaux Élèves</h3>
              <ol class="relative border-l border-gray-200 ml-4 space-y-8">
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-secondary rounded-full -left-4 ring-4 ring-white text-white font-bold">1</span>
                     <h4 class="font-bold text-lg text-gray-800">Pré-inscription en ligne</h4>
                     <p class="text-gray-600">Remplissez le formulaire ci-dessous pour initier le dossier.</p>
                  </li>
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-primary rounded-full -left-4 ring-4 ring-white text-white font-bold">2</span>
                     <h4 class="font-bold text-lg text-gray-800">Dépôt du dossier physique</h4>
                     <p class="text-gray-600">Déposez les bulletins et pièces administratives au secrétariat.</p>
                  </li>
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-secondary rounded-full -left-4 ring-4 ring-white text-white font-bold">3</span>
                     <h4 class="font-bold text-lg text-gray-800">Test et Entretien</h4>
                     <p class="text-gray-600">L'élève passe les tests de niveau et l'entretien de motivation.</p>
                  </li>
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white font-bold">4</span>
                     <h4 class="font-bold text-lg text-gray-800">Validation et Paiement</h4>
                     <p class="text-gray-600">Après acceptation, règlement des frais d'inscription pour valider la place.</p>
                  </li>
              </ol>
           </div>

           <div *ngIf="activeTab === 'old'" class="space-y-6">
              <h3 class="text-2xl font-bold text-gray-800 mb-6">Pour les Anciens Élèves</h3>
              <ol class="relative border-l border-gray-200 ml-4 space-y-8">
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-secondary rounded-full -left-4 ring-4 ring-white text-white font-bold">1</span>
                     <h4 class="font-bold text-lg text-gray-800">Validation du passage</h4>
                     <p class="text-gray-600">Le conseil de classe valide le passage en classe supérieure.</p>
                  </li>
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-primary rounded-full -left-4 ring-4 ring-white text-white font-bold">2</span>
                     <h4 class="font-bold text-lg text-gray-800">Mise à jour du dossier</h4>
                     <p class="text-gray-600">Vérification et mise à jour des informations administratives.</p>
                  </li>
                  <li class="ml-6">
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-green-500 rounded-full -left-4 ring-4 ring-white text-white font-bold">3</span>
                     <h4 class="font-bold text-lg text-gray-800">Paiement des frais</h4>
                     <p class="text-gray-600">Règlement des frais de réinscription avant la date limite.</p>
                  </li>
              </ol>
           </div>
        </div>
      </div>
    </section>

    <!-- Formulaire d'inscription -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-6 max-w-4xl">
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-primary mb-4">Formulaire de Pré-inscription</h2>
          <p class="text-gray-600 text-lg">Remplissez ce formulaire pour initier le dossier de votre enfant.</p>
        </div>

        <div class="bg-neutral-light rounded-2xl shadow-xl p-8 md:p-12 border-t-4 border-secondary" data-aos="fade-up" data-aos-delay="100">
          <form [formGroup]="enrollmentForm" (ngSubmit)="onSubmit()" class="space-y-12">
            
            <!-- Section Élève -->
            <div>
              <h2 class="text-2xl font-serif font-bold text-primary mb-8 border-b pb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="pi pi-user text-primary text-xl"></i> 
                </div>
                Informations de l'Élève
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Nom de famille <span class="text-red-500">*</span></label>
                  <input formControlName="lastName" type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="Ex: OUEDRAOGO" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Prénoms <span class="text-red-500">*</span></label>
                  <input formControlName="firstName" type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="Ex: Jean Pierre" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Date de naissance <span class="text-red-500">*</span></label>
                  <input formControlName="birthDate" type="date" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Lieu de naissance <span class="text-red-500">*</span></label>
                  <input formControlName="birthPlace" type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="Ex: Ouagadougou" />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Adresse de résidence <span class="text-red-500">*</span></label>
                  <input formControlName="address" type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="Ex: Secteur 12, Rue 12.34, Porte 567" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Sexe <span class="text-red-500">*</span></label>
                  <select formControlName="gender" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white">
                    <option value="">Sélectionner</option>
                    <option value="M">Masculin</option>
                    <option value="F">Féminin</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Classe demandée <span class="text-red-500">*</span></label>
                  <select formControlName="requestedClass" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white">
                    <option value="">Sélectionner une classe</option>
                    <option value="6eme">6ème</option>
                    <option value="5eme">5ème</option>
                    <option value="4eme">4ème</option>
                    <option value="3eme">3ème</option>
                    <option value="2nde">2nde</option>
                    <option value="1ere">1ère</option>
                    <option value="Tle">Terminale</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Section Parent -->
            <div>
              <h2 class="text-2xl font-serif font-bold text-primary mb-8 border-b pb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="pi pi-users text-primary text-xl"></i> 
                </div>
                Responsable Légal
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Nom & Prénom <span class="text-red-500">*</span></label>
                  <input formControlName="parentName" type="text" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="Nom du parent" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Lien de parenté <span class="text-red-500">*</span></label>
                  <select formControlName="parentRelationship" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white">
                    <option value="pere">Père</option>
                    <option value="mere">Mère</option>
                    <option value="tuteur">Tuteur Légal</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                  <input formControlName="parentEmail" type="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="email@exemple.com" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Téléphone <span class="text-red-500">*</span></label>
                  <input formControlName="parentPhone" type="tel" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white" placeholder="+226 ..." />
                </div>
              </div>
            </div>

            <!-- Submit -->
            <div class="pt-8 text-center">
              <button type="submit" [disabled]="enrollmentForm.invalid" class="px-12 py-4 bg-secondary text-white font-bold text-lg rounded-full shadow-xl hover:bg-primary transition-all transform hover:scale-105 border-2 border-secondary hover:border-primary disabled:opacity-50 disabled:cursor-not-allowed">
                Envoyer la demande
              </button>
              <p class="mt-6 text-sm text-gray-500 italic">
                * Cette pré-inscription ne vaut pas admission définitive. Vous serez contacté pour la suite du dossier.
              </p>
            </div>

          </form>
        </div>
      </div>
    </section>

    <!-- Tarifs et Modalités -->
    <section class="py-20 bg-primary text-white">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-3xl font-serif font-bold text-white mb-4">Tarifs et Modalités</h2>
          <p class="text-blue-100 max-w-2xl mx-auto">Une transparence totale sur les frais de scolarité.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
           <!-- Collège -->
           <div appTilt3d class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all" data-aos="fade-up">
              <h3 class="text-2xl font-bold mb-2">Collège</h3>
              <p class="text-secondary font-bold text-lg mb-6">6ème à 3ème</p>
              <div class="text-4xl font-bold mb-2">250.000 <span class="text-lg font-normal">FCFA</span></div>
              <p class="text-sm text-blue-200 mb-8">Par année scolaire</p>
              <ul class="space-y-3 text-sm text-blue-50">
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Inscription: 50.000 FCFA</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Scolarité: 200.000 FCFA</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Tenue incluse</li>
              </ul>
           </div>

           <!-- Lycée -->
           <div appTilt3d class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all transform md:-translate-y-4 shadow-2xl relative" data-aos="fade-up" data-aos-delay="100">
              <div class="absolute top-0 right-0 bg-secondary text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">Populaire</div>
              <h3 class="text-2xl font-bold mb-2">Lycée</h3>
              <p class="text-secondary font-bold text-lg mb-6">2nde à Terminale</p>
              <div class="text-4xl font-bold mb-2">325.000 <span class="text-lg font-normal">FCFA</span></div>
              <p class="text-sm text-blue-200 mb-8">Par année scolaire</p>
              <ul class="space-y-3 text-sm text-blue-50">
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Inscription: 75.000 FCFA</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Scolarité: 250.000 FCFA</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Tenue incluse</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Accès Labo Informatique</li>
              </ul>
           </div>

           <!-- Cantine & Transport -->
           <div appTilt3d class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 hover:bg-white/20 transition-all" data-aos="fade-up" data-aos-delay="200">
              <h3 class="text-2xl font-bold mb-2">Services Annexes</h3>
              <p class="text-secondary font-bold text-lg mb-6">Optionnel</p>
              <div class="text-lg font-bold mb-8">Sur devis</div>
              <ul class="space-y-3 text-sm text-blue-50">
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Cantine scolaire (Déjeuner)</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Transport scolaire (Bus)</li>
                <li class="flex items-center gap-2"><i class="pi pi-check text-secondary"></i> Activités périscolaires</li>
              </ul>
           </div>
        </div>
        
        <div class="text-center mt-12 text-blue-200 text-sm italic">
           * Les tarifs sont susceptibles d'être révisés annuellement. Facilités de paiement disponibles sur demande.
        </div>
      </div>
    </section>
  `
})
export class InscriptionComponent {
  activeTab: 'new' | 'old' = 'new';
  
  heroImages = [
    'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=2070&auto=format&fit=crop', // Writing/Exam
    'https://images.unsplash.com/photo-1427504743055-e9ba63450058?q=80&w=2074&auto=format&fit=crop', // Classroom
    'https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=2070&auto=format&fit=crop'  // Meeting/Discussion
  ];

  private fb = inject(FormBuilder);
  private enrollmentService = inject(EnrollmentService);

  enrollmentForm = this.fb.group({
    lastName: ['', Validators.required],
    firstName: ['', Validators.required],
    birthDate: ['', Validators.required],
    birthPlace: ['', Validators.required],
    address: ['', Validators.required],
    gender: ['', Validators.required],
    requestedClass: ['', Validators.required],
    parentName: ['', Validators.required],
    parentRelationship: ['pere', Validators.required],
    parentEmail: ['', [Validators.required, Validators.email]],
    parentPhone: ['', Validators.required]
  });

  onSubmit() {
    if (this.enrollmentForm.valid) {
      const formValue = this.enrollmentForm.value;
      const requestData: any = {
        student: {
          lastName: formValue.lastName,
          firstName: formValue.firstName,
          birthDate: formValue.birthDate,
          birthPlace: formValue.birthPlace,
          address: formValue.address,
          gender: formValue.gender,
          requestedClass: formValue.requestedClass
        },
        parent: {
          fullName: formValue.parentName,
          relationship: formValue.parentRelationship,
          email: formValue.parentEmail,
          phone: formValue.parentPhone
        }
      };

      this.enrollmentService.submitEnrollment(requestData).subscribe({
        next: (response) => {
          alert('Votre demande a été envoyée avec succès !');
          this.enrollmentForm.reset();
        },
        error: (err) => console.error(err)
      });
    }
  }
}
