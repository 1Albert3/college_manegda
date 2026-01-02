import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { TeacherService } from '../../../core/services/teacher.service';

interface Student {
  id: number | string;
  first_name: string;
  last_name: string;
  matricule: string;
  photo?: string;
}

interface ClassRoom {
  id: string; // Changed to string as IDs are UUIDs
  name: string;
  nom: string; // Add nom
  level: string;
  niveau: string; // Add niveau
  student_count: number;
  effectif: number; // Add effectif
  cycle: string; // Add cycle
}

@Component({
  selector: 'app-teacher-classes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Mes Classes</h1>
          <p class="text-gray-500">Gérez vos élèves et leurs informations</p>
        </div>
        <div class="flex gap-3">
          <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher un élève..."
                 class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
      </div>

      <!-- Class Tabs -->
      <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex gap-2 flex-wrap" *ngIf="!loadingClasses(); else loadingTabs">
          <button *ngFor="let cls of classes()"
                  (click)="selectClass(cls)"
                  class="px-4 py-2 rounded-lg font-medium transition-all"
                  [ngClass]="{
                    'bg-primary text-white': selectedClass()?.id === cls.id,
                    'bg-gray-100 text-gray-700 hover:bg-gray-200': selectedClass()?.id !== cls.id
                  }">
            {{ cls.nom || cls.name }}
            <span class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">{{ cls.effectif || cls.student_count }}</span>
          </button>
           <div *ngIf="classes().length === 0" class="text-gray-500 italic px-4 py-2">
            Aucune classe assignée.
          </div>
        </div>
        <ng-template #loadingTabs>
            <div class="flex gap-2">
                <div class="h-10 w-24 bg-gray-100 rounded-lg animate-pulse"></div>
                <div class="h-10 w-24 bg-gray-100 rounded-lg animate-pulse"></div>
            </div>
        </ng-template>
      </div>

      <!-- Students Grid -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden" *ngIf="selectedClass()">
        <div class="bg-gradient-to-r from-primary to-blue-600 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-users"></i>
            {{ selectedClass()?.nom || 'Sélectionnez une classe' }} - Liste des Élèves
          </h2>
          <div class="flex gap-2">
            <button class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm flex items-center gap-1">
              <i class="pi pi-pencil"></i> Saisir Notes
            </button>
            <button class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm flex items-center gap-1">
              <i class="pi pi-clock"></i> Faire l'Appel
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <div *ngIf="loading()" class="text-center py-8">
            <i class="pi pi-spin pi-spinner text-3xl text-primary"></i>
            <p class="text-gray-500 mt-2">Chargement des élèves...</p>
          </div>

          <div *ngIf="!loading() && filteredStudents().length === 0" class="text-center py-8">
            <i class="pi pi-users text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">Aucun élève trouvé dans cette classe</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" *ngIf="!loading()">
            <div *ngFor="let student of filteredStudents()"
                 class="border border-gray-200 rounded-xl p-4 hover:border-primary hover:shadow-md transition-all cursor-pointer">
              <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold text-lg uppercase">
                  {{ student.first_name?.charAt(0) }}{{ student.last_name?.charAt(0) }}
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-800">{{ student.last_name }} {{ student.first_name }}</h3>
                  <p class="text-sm text-gray-500">{{ student.matricule }}</p>
                  <div class="flex gap-2 mt-3">
                    <button class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Notes</button>
                    <button class="px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200">Absences</button>
                    <!-- <button class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Profil</button> -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class TeacherClassesComponent implements OnInit {
  private http = inject(HttpClient);
  private teacherService = inject(TeacherService); // Inject teacher service
  private apiUrl = environment.apiUrl;

  classes = signal<ClassRoom[]>([]);
  students = signal<Student[]>([]);
  selectedClass = signal<ClassRoom | null>(null);
  loading = signal(false);
  loadingClasses = signal(false);
  searchQuery = '';

  filteredStudents = () => {
    const query = this.searchQuery.toLowerCase();
    return this.students().filter(s => 
      (s.first_name?.toLowerCase() || '').includes(query) ||
      (s.last_name?.toLowerCase() || '').includes(query) ||
      (s.matricule?.toLowerCase() || '').includes(query)
    );
  };

  ngOnInit() {
    this.loadClasses();
  }

  loadClasses() {
    this.loadingClasses.set(true);
    this.teacherService.getDashboard().subscribe({
        next: (data) => {
            if (data.classes && data.classes.length > 0) {
                 // Map to ensure typing compatibility if needed, though they match closely
                 const mappedClasses = data.classes.map(c => ({
                     ...c,
                     name: c.nom,
                     level: c.niveau,
                     student_count: c.effectif,
                     cycle: c.cycle
                 }));
                 this.classes.set(mappedClasses);
                 
                 // Select first class automatically
                 this.selectClass(mappedClasses[0]);
            } else {
                this.classes.set([]);
            }
            this.loadingClasses.set(false);
        },
        error: (err) => {
            console.error(err);
            this.loadingClasses.set(false);
        }
    });
  }

  selectClass(cls: ClassRoom) {
    this.selectedClass.set(cls);
    this.loadStudents(cls);
  }

  loadStudents(cls: ClassRoom) {
    this.loading.set(true);
    
    // Determine the correct endpoint based on cycle
    let endpoint = '';
    if (cls.cycle === 'lycee') {
        endpoint = `/lycee/classes/${cls.id}/students`;
    } else if (cls.cycle === 'college') {
        endpoint = `/college/classes/${cls.id}/students`;
    } else {
        endpoint = `/mp/classes/${cls.id}/students`;
    }

    this.http.get<any>(`${this.apiUrl}${endpoint}`).subscribe({
        next: (res) => {
            // API returns { class: ..., students: [...] }
            // Some controllers might use different structures, so we handle standard 'students' key
            // and fallback to 'data' if wrapped.
            const rawStudents = res.students || res.data || [];
            
            // Map backend fields (nom/prenoms) to frontend interface (last_name/first_name)
            const mappedStudents = rawStudents.map((s: any) => ({
                id: s.id,
                first_name: s.prenoms || s.first_name,
                last_name: s.nom || s.last_name,
                matricule: s.matricule,
                photo: s.photo_identite || s.photo // Align with backend photo field
            }));

            this.students.set(mappedStudents);
            this.loading.set(false);
        },
        error: (err) => {
            console.error('Failed to load students', err);
            this.students.set([]);
            this.loading.set(false);
        }
    });
  }
}
