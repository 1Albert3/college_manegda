import { Component, signal, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AcademicService, AcademicYear } from '../../../core/services/academic.service';

@Component({
  selector: 'app-admin-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Paramètres</h1>
        <p class="text-gray-500">Configuration du système</p>
      </div>

      <!-- Settings Tabs -->
      <div class="flex gap-2 border-b overflow-x-auto">
        <button *ngFor="let tab of tabs()" (click)="activeTab = tab.id"
                class="px-4 py-3 font-medium text-sm whitespace-nowrap border-b-2 transition-colors"
                [ngClass]="activeTab === tab.id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
          <i [class]="tab.icon + ' mr-2'"></i>{{ tab.name }}
        </button>
      </div>

      <!-- General Settings -->
      <div *ngIf="activeTab === 'general'" class="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <h2 class="font-bold text-gray-800 border-b pb-2">Informations de l'établissement</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-1">Nom de l'établissement</label>
            <input type="text" [(ngModel)]="settings.schoolName" class="w-full px-4 py-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Acronyme</label>
            <input type="text" [(ngModel)]="settings.acronym" class="w-full px-4 py-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" [(ngModel)]="settings.email" class="w-full px-4 py-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Téléphone</label>
            <input type="tel" [(ngModel)]="settings.phone" class="w-full px-4 py-2 border rounded-lg">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Adresse</label>
            <input type="text" [(ngModel)]="settings.address" class="w-full px-4 py-2 border rounded-lg">
          </div>
        </div>
        <div class="flex justify-end">
          <button (click)="saveSettings()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Enregistrer</button>
        </div>
      </div>

      <!-- Academic Settings -->
      <div *ngIf="activeTab === 'academic'" class="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <h2 class="font-bold text-gray-800 border-b pb-2">Configuration académique</h2>
        
        <!-- School Year Selection -->
        <div class="bg-gray-50 p-4 rounded-lg border">
            <label class="block text-sm font-medium mb-2">Année scolaire actuelle</label>
            <div class="flex gap-2">
                <select [(ngModel)]="currentYearId" class="flex-1 px-4 py-2 border rounded-lg" (change)="onYearChange()">
                    <option [ngValue]="null">Sélectionner une année</option>
                    <option *ngFor="let year of academicYears()" [value]="year.id">
                        {{ year.name }} ({{ year.start_date | date }} - {{ year.end_date | date }})
                    </option>
                </select>
                <button (click)="setCurrentYear()" [disabled]="!currentYearId || isLoading" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
                    Définir comme courante
                </button>
            </div>
            <p *ngIf="currentApiYear" class="text-sm text-gray-500 mt-2">
                Année active (API): <span class="font-bold text-green-700">{{ currentApiYear.name }}</span>
            </p>
        </div>

        <!-- Create New School Year -->
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <h3 class="font-medium text-blue-800 mb-3">Nouvelle Année Scolaire</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Nom (ex: 2025-2026)</label>
                    <input type="text" [(ngModel)]="newYear.name" placeholder="2025-2026" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Début</label>
                    <input type="date" [(ngModel)]="newYear.start_date" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Fin</label>
                    <input type="date" [(ngModel)]="newYear.end_date" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" [(ngModel)]="newYear.is_current" class="mr-2"> Définir comme courante immédiatement
                </label>
                <button (click)="createYear()" [disabled]="isLoading" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Créer l'année
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-1">Trimestre en cours (Esthétique)</label>
            <select [(ngModel)]="settings.currentTrimester" class="w-full px-4 py-2 border rounded-lg">
              <option value="1">1er Trimestre</option>
              <option value="2">2ème Trimestre</option>
              <option value="3">3ème Trimestre</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Note maximale</label>
            <input type="number" [(ngModel)]="settings.maxGrade" class="w-full px-4 py-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Moyenne de passage</label>
            <input type="number" [(ngModel)]="settings.passingGrade" class="w-full px-4 py-2 border rounded-lg">
          </div>
        </div>
      </div>

      <!-- Users Management -->
      <div *ngIf="activeTab === 'users'" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="font-bold text-gray-800">Gestion des utilisateurs</h2>
          <button (click)="showNewUser = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
            <i class="pi pi-plus mr-2"></i>Nouvel utilisateur
          </button>
        </div>
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
               <th class="px-6 py-3">Utilisateur</th>
               <th class="px-6 py-3">Rôle</th>
               <th class="px-6 py-3">Statut</th>
               <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr *ngFor="let user of users()" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ user.name }}</div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full" [ngClass]="getRoleClass(user.role)">{{ user.role }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full" [ngClass]="user.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                  {{ user.active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button (click)="editUser(user)" class="text-blue-600 hover:text-blue-900 mr-2"><i class="pi pi-pencil"></i></button>
                <button (click)="toggleUserStatus(user)" class="text-orange-600 hover:text-orange-900"><i class="pi pi-lock"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Notifications Settings -->
      <div *ngIf="activeTab === 'notifications'" class="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <h2 class="font-bold text-gray-800 border-b pb-2">Paramètres de notification</h2>
        <div class="space-y-4">
          <label *ngFor="let notif of notifications()" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <div class="font-medium">{{ notif.name }}</div>
              <div class="text-sm text-gray-500">{{ notif.description }}</div>
            </div>
            <button (click)="toggleNotification(notif)" 
                    class="w-12 h-6 rounded-full transition-colors"
                    [ngClass]="notif.enabled ? 'bg-green-500' : 'bg-gray-300'">
              <div class="w-5 h-5 bg-white rounded-full shadow transition-transform"
                   [ngClass]="notif.enabled ? 'translate-x-6' : 'translate-x-0.5'"></div>
            </button>
          </label>
        </div>
      </div>
    </div>
  `
})
export class AdminSettingsComponent implements OnInit {
  private academicService = inject(AcademicService);

  activeTab = 'general';
  showNewUser = false;
  isLoading = false;

  tabs = signal([
    { id: 'general', name: 'Général', icon: 'pi pi-cog' },
    { id: 'academic', name: 'Académique', icon: 'pi pi-book' },
    { id: 'users', name: 'Utilisateurs', icon: 'pi pi-users' },
    { id: 'notifications', name: 'Notifications', icon: 'pi pi-bell' },
  ]);

  // Mock settings for other tabs
  settings = {
    schoolName: 'Collège Privé Wend-Manegda',
    acronym: 'CPWM',
    email: 'contact@college-wm.bf',
    phone: '+226 25 00 00 00',
    address: 'Ouagadougou, Burkina Faso',
    currentYear: '',
    currentTrimester: '1',
    maxGrade: 20,
    passingGrade: 10
  };

  // Academic Settings Data
  academicYears = signal<AcademicYear[]>([]);
  currentYearId: string | null = null;
  currentApiYear: AcademicYear | null = null;

  newYear = {
    name: '',
    start_date: '',
    end_date: '',
    is_current: false
  };

  users = signal([
    { id: 1, name: 'Admin Principal', email: 'admin@college-wm.bf', role: 'Admin', active: true },
    { id: 2, name: 'M. Ouédraogo', email: 'ouedraogo@college-wm.bf', role: 'Enseignant', active: true },
    { id: 3, name: 'Mme Sawadogo', email: 'sawadogo@college-wm.bf', role: 'Secrétaire', active: true },
    { id: 4, name: 'M. Kaboré', email: 'kabore@college-wm.bf', role: 'Comptable', active: false },
  ]);

  notifications = signal([
    { id: 1, name: 'Nouvelles inscriptions', description: 'Recevoir une notification pour chaque nouvelle inscription', enabled: true },
    { id: 2, name: 'Paiements reçus', description: 'Notification lors de la réception d\'un paiement', enabled: true },
    { id: 3, name: 'Absences', description: 'Alertes d\'absences non justifiées', enabled: false },
    { id: 4, name: 'Notes saisies', description: 'Notification quand un enseignant saisit des notes', enabled: true },
  ]);

  ngOnInit() {
    this.loadAcademicData();
  }

  loadAcademicData() {
    this.isLoading = true;
    this.academicService.getAcademicYears().subscribe({
        next: (years) => {
            this.academicYears.set(years);
            const current = years.find(y => y.is_current);
            if (current) {
                this.currentYearId = current.id;
                this.currentApiYear = current;
                this.settings.currentYear = current.name;
            }
            this.isLoading = false;
        },
        error: (err) => {
            console.error('Error loading years', err);
            this.isLoading = false;
        }
    });
  }

  onYearChange() {
      // Just UI update, confirmation via button needed
  }

  setCurrentYear() {
      if (!this.currentYearId) return;
      this.isLoading = true;
      this.academicService.setCurrentYear(this.currentYearId).subscribe({
          next: () => {
              alert('Année scolaire définie comme courante !');
              this.loadAcademicData(); // Reload to refresh status
          },
          error: (err) => {
              alert('Erreur lors de la mise à jour.');
              console.error(err);
              this.isLoading = false;
          }
      });
  }

  createYear() {
      if (!this.newYear.name || !this.newYear.start_date || !this.newYear.end_date) {
          alert('Veuillez remplir tous les champs de la nouvelle année.');
          return;
      }
      this.isLoading = true;
      this.academicService.createAcademicYear(this.newYear).subscribe({
          next: () => {
              alert('Année scolaire créée avec succès !');
              this.newYear = { name: '', start_date: '', end_date: '', is_current: false }; // Reset
              this.loadAcademicData();
          },
          error: (err) => {
              alert('Erreur lors de la création.');
              console.error(err);
              this.isLoading = false;
          }
      });
  }


  getRoleClass(role: string) {
    const classes: Record<string, string> = {
      'Admin': 'bg-purple-100 text-purple-800',
      'Enseignant': 'bg-blue-100 text-blue-800',
      'Secrétaire': 'bg-teal-100 text-teal-800',
      'Comptable': 'bg-emerald-100 text-emerald-800'
    };
    return classes[role] || 'bg-gray-100 text-gray-800';
  }

  saveSettings() { 
      // This is for General settings mainly
      alert('Paramètres généraux enregistrés (Mock) !'); 
  }
  
  editUser(user: any) { alert('Modifier: ' + user.name); }
  toggleUserStatus(user: any) { user.active = !user.active; }
  toggleNotification(notif: any) { notif.enabled = !notif.enabled; }
}
