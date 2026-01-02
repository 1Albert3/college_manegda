import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface User {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  role: string;
  is_active: boolean;
  created_at: string;
}

@Component({
  selector: 'app-hr-management',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  template: `
    <div class="p-6 space-y-8 animate-in fade-in duration-500">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-gray-900 leading-tight">Gestion du Personnel (RH)</h1>
          <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest">Administration des enseignants et du staff administratif</p>
        </div>
        <button (click)="openAddModal()" 
                class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 active:scale-95">
          <i class="pi pi-user-plus"></i>
          Nouveau Collaborateur
        </button>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all">
              <i class="pi pi-users text-xl"></i>
            </div>
            <div class="text-2xl font-black text-gray-900">{{ staffStats.total || 0 }}</div>
          </div>
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Total Effectif</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
              <i class="pi pi-book text-xl"></i>
            </div>
            <div class="text-2xl font-black text-gray-900">{{ staffStats.enseignant || 0 }}</div>
          </div>
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Enseignants</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-all">
              <i class="pi pi-building text-xl"></i>
            </div>
            <div class="text-2xl font-black text-gray-900">{{ staffStats.administration || 0 }}</div>
          </div>
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Administratifs</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-all">
              <i class="pi pi-check-circle text-xl"></i>
            </div>
            <div class="text-2xl font-black text-gray-900">{{ staffStats.active || 0 }}</div>
          </div>
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Comptes Actifs</p>
        </div>
      </div>

      <!-- Filters & List -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
          <div class="relative flex-1 max-w-md">
            <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" [(ngModel)]="searchQuery" (input)="loadStaff()" 
                   placeholder="Rechercher un collaborateur..." 
                   class="w-full pl-12 pr-4 py-3 rounded-2xl border-none bg-white shadow-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-sm">
          </div>
          
          <div class="flex items-center gap-3">
             <select [(ngModel)]="filterRole" (change)="loadStaff()" 
                     class="px-4 py-3 rounded-2xl border-none bg-white shadow-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-indigo-500 text-xs font-black uppercase tracking-widest">
                <option value="">Tous les rôles</option>
                <option value="enseignant">Enseignants</option>
                <option value="direction">Direction</option>
                <option value="comptabilite">Comptabilité</option>
                <option value="secretariat">Secrétariat</option>
             </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-gray-50/50 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                <th class="px-8 py-5">Collaborateur</th>
                <th class="px-8 py-5">Rôle</th>
                <th class="px-8 py-5">Contact</th>
                <th class="px-8 py-5">Date d'arrivée</th>
                <th class="px-8 py-5">Statut</th>
                <th class="px-8 py-5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr *ngFor="let member of staff" class="hover:bg-indigo-50/20 transition-all duration-200 group">
                <td class="px-8 py-5">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-white flex items-center justify-center text-indigo-600 font-black text-sm border border-indigo-50 shadow-sm group-hover:scale-110 transition-transform">
                      {{ (member.first_name.charAt(0) + member.last_name.charAt(0)) | uppercase }}
                    </div>
                    <div>
                      <div class="font-black text-gray-900 text-sm italic uppercase">{{ member.first_name }} {{ member.last_name }}</div>
                      <div class="text-[10px] text-gray-400 font-bold tracking-tight uppercase">{{ member.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                   <span [class]="getRoleClass(member.role)" class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border">
                     {{ member.role }}
                   </span>
                </td>
                <td class="px-8 py-5 text-xs font-bold text-gray-500">
                   {{ member.phone || '--' }}
                </td>
                <td class="px-8 py-5 text-xs text-gray-400 font-medium">
                   {{ member.created_at | date:'dd MMM yyyy' }}
                </td>
                <td class="px-8 py-5">
                   <div class="flex items-center gap-2">
                     <span class="w-2 h-2 rounded-full" [class]="member.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                     <span class="text-[10px] font-black uppercase tracking-tighter" [class]="member.is_active ? 'text-emerald-600' : 'text-rose-600'">
                       {{ member.is_active ? 'Actif' : 'Inactif' }}
                     </span>
                   </div>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="p-2.5 rounded-xl bg-white text-gray-400 hover:text-indigo-600 hover:shadow-sm border border-transparent hover:border-gray-100 transition-all" title="Modifier">
                      <i class="pi pi-pencil"></i>
                    </button>
                    <button class="p-2.5 rounded-xl bg-white text-gray-400 hover:text-rose-600 hover:shadow-sm border border-transparent hover:border-gray-100 transition-all" title="Désactiver">
                      <i class="pi pi-lock"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr *ngIf="staff.length === 0 && !loading">
                 <td colspan="6" class="px-8 py-20 text-center text-gray-400 italic">Aucun personnel trouvé.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="p-6 border-t border-gray-50 bg-gray-50/30 flex items-center justify-between">
           <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Affichage de {{ staff.length }} membres</p>
           <div class="flex items-center gap-2">
              <button class="p-2 rounded-xl bg-white border border-gray-200 text-gray-400 hover:bg-gray-50 disabled:opacity-50" disabled>
                <i class="pi pi-angle-left"></i>
              </button>
              <button class="p-2 rounded-xl bg-white border border-gray-200 text-gray-400 hover:bg-gray-50 disabled:opacity-50" disabled>
                <i class="pi pi-angle-right"></i>
              </button>
           </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajout (Simplifié pour la démo) -->
    <div *ngIf="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-300">
       <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in slide-in-from-bottom-8 duration-500">
          <div class="p-8 border-b border-gray-100 flex items-center justify-between">
             <div>
                <h3 class="text-xl font-black text-gray-900 uppercase">Nouveau Collaborateur</h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Saisie des informations de base</p>
             </div>
             <button (click)="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-50 p-2 rounded-xl">
                <i class="pi pi-times"></i>
             </button>
          </div>
          
          <form [formGroup]="staffForm" (ngSubmit)="submitStaff()" class="p-8 space-y-5">
             <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                   <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Prénom</label>
                   <input type="text" formControlName="first_name" placeholder="Ex: Jean" 
                          class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                </div>
                <div class="space-y-1.5">
                   <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nom</label>
                   <input type="text" formControlName="last_name" placeholder="Ex: Dupont" 
                          class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                </div>
             </div>

             <div class="space-y-1.5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Email Professionnel</label>
                <input type="email" formControlName="email" placeholder="jean.dupont@college-abc.bf" 
                       class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
             </div>

             <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                   <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Rôle</label>
                   <select formControlName="role" class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                      <option value="enseignant">Enseignant</option>
                      <option value="direction">Direction</option>
                      <option value="comptabilite">Comptabilité</option>
                      <option value="secretariat">Secrétariat</option>
                   </select>
                </div>
                <div class="space-y-1.5">
                   <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Téléphone</label>
                   <input type="text" formControlName="phone" placeholder="+226 ..." 
                          class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                </div>
             </div>

             <div class="space-y-1.5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mot de passe</label>
                <input type="text" formControlName="password" placeholder="Mot de passe" 
                       class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                <p class="text-[10px] text-gray-400 italic">Laissez tel quel (Welcome123!) ou définissez un nouveau mot de passe.</p>
             </div>

             <div class="pt-6 flex gap-3">
                <button type="button" (click)="closeAddModal()" 
                        class="flex-1 px-6 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest text-gray-500 hover:bg-gray-50 transition-colors">
                   Annuler
                </button>
                <button type="submit" [disabled]="staffForm.invalid || loading"
                        class="flex-[2] bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 disabled:opacity-50 active:scale-95">
                   <span *ngIf="!loading">Créer le Compte</span>
                   <span *ngIf="loading" class="flex items-center justify-center gap-2">
                      <i class="pi pi-spin pi-spinner"></i> Création...
                   </span>
                </button>
             </div>
          </form>
       </div>
    </div>
  `
})
export class HRManagementComponent implements OnInit {
  staff: User[] = [];
  staffStats: any = {};
  loading = false;
  searchQuery = '';
  filterRole = '';
  showAddModal = false;
  staffForm: FormGroup;

  private http = inject(HttpClient);
  private fb = inject(FormBuilder);

  constructor() {
    this.staffForm = this.fb.group({
      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      phone: [''],
      role: ['enseignant', Validators.required],
      password: ['Welcome123!'] // Default password for new staff
    });
  }

  ngOnInit() {
    this.loadStaff();
    this.loadStats();
  }

  loadStaff() {
    this.loading = true;
    let params: any = {};
    if (this.searchQuery) params.search = this.searchQuery;
    if (this.filterRole) params.role = this.filterRole;

    this.http.get<any>(`${environment.apiUrl}/core/users`, { params }).subscribe({
      next: (res) => {
        // We only want staff, not parents/students
        const staffRoles = ['direction', 'secretariat', 'comptabilite', 'enseignant', 'admin', 'super_admin'];
        this.staff = (res.data || []).filter((u: User) => staffRoles.includes(u.role));
        this.loading = false;
      },
      error: () => this.loading = false
    });
  }

  loadStats() {
    this.http.get<any>(`${environment.apiUrl}/core/users/stats`).subscribe({
      next: (res) => {
        const stats = res.data || {};
        this.staffStats = {
          total: (stats.teacher || 0) + (stats.director || 0) + (stats.accountant || 0) + (stats.secretary || 0),
          enseignant: stats.teacher || 0,
          administration: (stats.director || 0) + (stats.accountant || 0) + (stats.secretary || 0),
          active: this.staff.filter(u => u.is_active).length // Simple approx based on loaded data
        };
      }
    });
  }

  getRoleClass(role: string) {
    switch(role) {
      case 'teacher': return 'bg-indigo-50 text-indigo-700 border-indigo-100';
      case 'director': return 'bg-amber-50 text-amber-700 border-amber-100';
      case 'accountant': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
      case 'secretary': return 'bg-blue-50 text-blue-700 border-blue-100';
      default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
  }

  openAddModal() { this.showAddModal = true; }
  closeAddModal() { this.showAddModal = false; this.staffForm.reset({role: 'teacher', password: 'Welcome123!'}); }

  submitStaff() {
    if (this.staffForm.invalid) return;
    this.loading = true;
    this.http.post(`${environment.apiUrl}/core/users`, this.staffForm.value).subscribe({
        next: () => {
            this.loading = false;
            this.closeAddModal();
            this.loadStaff();
            this.loadStats();
        },
        error: (err) => {
            this.loading = false;
            alert(err.error?.message || 'Erreur lors de la création');
        }
    });
  }
}
