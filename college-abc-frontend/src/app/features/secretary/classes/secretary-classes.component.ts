import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface ClassRoom {
  id: number;
  name: string;
  level: string;
  capacity: number;
  currentCount: number;
  mainTeacher: string;
  students: any[];
  cycle?: string; // Ajouté pour gérer le chargement dynamique
}

@Component({
  selector: 'app-secretary-classes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-teal-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Affectation des Classes</h1>
          <p class="text-gray-500">Gérez les classes et affectez les élèves</p>
        </div>
        <button (click)="showAssignModal = true"
                class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 flex items-center gap-2 font-bold shadow-sm transition">
          <i class="pi pi-user-plus"></i> Affecter un élève
        </button>
      </div>

      <!-- Classes Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div *ngIf="loadingClasses" class="col-span-full py-8 text-center text-gray-500">
             <i class="pi pi-spin pi-spinner text-2xl mb-2"></i><br>Chargement des classes...
        </div>
        <div *ngFor="let cls of classes()" 
             (click)="selectClass(cls)"
             class="bg-white rounded-xl shadow-sm p-5 border-2 cursor-pointer transition-all hover:shadow-md hover:-translate-y-1"
             [ngClass]="{'border-teal-500 ring-2 ring-teal-100': selectedClass?.id === cls.id, 'border-transparent border-gray-100': selectedClass?.id !== cls.id}">
          <div class="flex items-center justify-between mb-3">
            <span class="text-lg font-bold text-gray-800">{{ cls.name }}</span>
            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-bold uppercase tracking-wider">{{ cls.level }}</span>
          </div>
          <div class="flex items-center gap-2 mb-3">
            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                   [style.width.%]="(cls.currentCount / cls.capacity) * 100"
                   [ngClass]="{
                     'bg-green-500': (cls.currentCount / cls.capacity) < 0.8,
                     'bg-yellow-500': (cls.currentCount / cls.capacity) >= 0.8 && (cls.currentCount / cls.capacity) < 1,
                     'bg-red-500': (cls.currentCount / cls.capacity) >= 1
                   }"></div>
            </div>
            <span class="text-sm font-bold text-gray-600">{{ cls.currentCount }}<span class="text-gray-400 font-normal">/{{ cls.capacity }}</span></span>
          </div>
          <div class="text-sm text-gray-500 flex items-center gap-2">
            <i class="pi pi-user text-teal-600"></i> {{ cls.mainTeacher }}
          </div>
        </div>
      </div>

      <!-- Class Details -->
      <div *ngIf="selectedClass" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-users"></i>
            {{ selectedClass.name }} - Liste des Élèves ({{ selectedClass.currentCount }})
          </h2>
          <div class="relative">
             <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-white/60"></i>
             <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..."
                 class="pl-10 pr-4 py-1.5 rounded-lg text-sm w-48 bg-white/20 text-white placeholder-white/60 border-0 focus:ring-2 focus:ring-white/50 transition">
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr class="text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3">Élève</th>
                <th class="px-6 py-3">Matricule</th>
                <!-- <th class="px-6 py-3">Date de naissance</th> -->
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngIf="loadingStudents" class="bg-gray-50">
                  <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Chargement des élèves...</td>
              </tr>
              <tr *ngIf="!loadingStudents && filteredStudents().length === 0" class="bg-gray-50">
                  <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">Aucun élève dans cette classe.</td>
              </tr>
              <tr *ngFor="let student of filteredStudents()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold text-sm shrink-0 uppercase">
                      {{ (student.firstName?.charAt(0) || '') }}{{ (student.lastName?.charAt(0) || '') }}
                    </div>
                    <span class="font-bold text-gray-800">{{ student.lastName }} {{ student.firstName }}</span>
                  </div>
                </td>
                <td class="px-6 py-3 text-gray-600 font-mono text-sm font-medium">{{ student.matricule }}</td>
                <!-- <td class="px-6 py-3 text-gray-600 font-medium">{{ student.birthDate }}</td> -->
                <td class="px-6 py-3">
                  <button (click)="transferStudent(student)" class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center gap-1 transition">
                    <i class="pi pi-arrow-right-arrow-left"></i> Transférer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Assign Modal -->
      <div *ngIf="showAssignModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showAssignModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-teal-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Affecter un élève</h2>
             <button (click)="showAssignModal = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Rechercher l'élève</label>
              <input type="text" [(ngModel)]="studentSearchModal" placeholder="Nom ou matricule..."
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Affecter à la classe</label>
              <select [(ngModel)]="targetClass"
                      class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 bg-white transition">
                <option *ngFor="let cls of classes()" [value]="cls.id">
                  {{ cls.name }} ({{ cls.currentCount }}/{{ cls.capacity }})
                </option>
              </select>
            </div>
          </div>
          <div class="border-t px-6 py-4 flex justify-end gap-3 bg-gray-50 mt-2">
            <button (click)="showAssignModal = false"
                    class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
            <button (click)="confirmAssignment()"
                    class="px-5 py-2.5 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition shadow-lg shadow-teal-200">Affecter</button>
          </div>
        </div>
      </div>
    </div>
  `
})
export class SecretaryClassesComponent implements OnInit {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  classes = signal<ClassRoom[]>([]);
  selectedClass: ClassRoom | null = null;
  searchQuery = '';
  showAssignModal = false;
  studentSearchModal = '';
  targetClass = '';
  
  showSuccessToast = false;
  successMessage = '';
  loadingClasses = false;
  loadingStudents = false;

  ngOnInit() {
    this.loadClasses();
  }

  loadClasses() {
    this.loadingClasses = true;
    this.http.get<any[]>(`${this.apiUrl}/v1/academic/classrooms`).subscribe({
        next: (data) => {
            // Map data correctly
            const mapped = data.map(d => ({
                ...d,
                students: [] // Initialize empty students array
            }));
            this.classes.set(mapped);
            this.loadingClasses = false;
        },
        error: (err) => {
            console.error(err);
            this.loadingClasses = false;
        }
    });
  }

  selectClass(cls: ClassRoom) {
    this.selectedClass = cls;
    this.loadStudents(cls);
  }

  loadStudents(cls: ClassRoom) {
      if(cls.students && cls.students.length > 0 && cls.currentCount === cls.students.length) return; // cache

      this.loadingStudents = true;
      let endpoint = '';
      if (cls.cycle === 'lycee') endpoint = `/lycee/classes/${cls.id}/students`;
      else if (cls.cycle === 'college') endpoint = `/college/classes/${cls.id}/students`;
      else endpoint = `/mp/classes/${cls.id}/students`;

      this.http.get<any>(`${this.apiUrl}${endpoint}`).subscribe({
          next: (res) => {
              // Standardize student format
              const students = (res.data || []).map((s: any) => ({
                  id: s.id,
                  firstName: s.first_name || s.prenoms,
                  lastName: s.last_name || s.nom,
                  matricule: s.matricule,
                  birthDate: s.date_of_birth || s.date_naissance || ''
              }));
              
              // Update the class in the signal is tricky, let's update reference
              cls.students = students;
              this.loadingStudents = false;
          },
          error: (err) => {
              console.error(err);
              cls.students = [];
              this.loadingStudents = false;
          }
      });
  }

  filteredStudents = () => {
    if (!this.selectedClass) return [];
    if (!this.selectedClass.students) return [];
    
    const q = this.searchQuery.toLowerCase();
    return this.selectedClass.students.filter(s =>
      (s.firstName?.toLowerCase() || '').includes(q) ||
      (s.lastName?.toLowerCase() || '').includes(q) ||
      (s.matricule?.toLowerCase() || '').includes(q)
    );
  };

  transferStudent(student: any) {
    this.studentSearchModal = `${student.lastName} ${student.firstName}`;
    this.showAssignModal = true;
  }

  confirmAssignment() {
    this.showToast('Élève affecté avec succès ! (Simulation)');
    this.showAssignModal = false;
    this.studentSearchModal = '';
    this.targetClass = '';
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
