import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { StudentService } from '../../../../core/services/student.service';
import { Student } from '../../../../core/models/student.model';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  template: `
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Gestion des Élèves</h1>
          <p class="text-gray-600">Liste complète des élèves inscrits</p>
        </div>
        <a routerLink="/admin/students/register" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors flex items-center gap-2 shadow-md">
          <i class="pi pi-user-plus"></i>
          <span>Inscrire un élève</span>
        </a>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input type="text" [(ngModel)]="searchTerm" (input)="filterStudents()" placeholder="Rechercher par nom, matricule..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <select [(ngModel)]="selectedClass" (change)="filterStudents()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white">
          <option value="">Toutes les classes</option>
          <option value="6ème A">6ème A</option>
          <option value="6ème B">6ème B</option>
          <option value="5ème A">5ème A</option>
          <option value="3ème A">3ème A</option>
        </select>
        <select [(ngModel)]="selectedStatus" (change)="filterStudents()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white">
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="pending">En attente</option>
          <option value="excluded">Exclu</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
              <tr>
                <th class="px-6 py-4">Matricule</th>
                <th class="px-6 py-4">Élève</th>
                <th class="px-6 py-4">Classe</th>
                <th class="px-6 py-4">Parent</th>
                <th class="px-6 py-4 text-center">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let student of filteredStudents()" class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-4 font-mono text-sm text-gray-500">{{ student.matricule }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold overflow-hidden">
                      <img *ngIf="student.photo" [src]="student.photo" class="w-full h-full object-cover">
                      <span *ngIf="!student.photo">{{ student.firstName.charAt(0) }}{{ student.lastName.charAt(0) }}</span>
                    </div>
                    <div>
                      <div class="font-bold text-gray-800">{{ student.lastName }} {{ student.firstName }}</div>
                      <div class="text-xs text-gray-500">{{ student.gender === 'M' ? 'Garçon' : 'Fille' }} • {{ student.dateOfBirth | date:'dd/MM/yyyy' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-sm font-bold">{{ student.currentClass }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-800">{{ student.parentName }}</div>
                  <div class="text-xs text-gray-500">{{ student.parentPhone }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                  <span [class]="getStatusClass(student.status)">
                    {{ getStatusLabel(student.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button [routerLink]="['/admin/students', student.id, 'edit']" [queryParams]="{cycle: student.cycle}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Modifier">
                      <i class="pi pi-pencil"></i>
                    </button>
                    <button [routerLink]="['/admin/students', student.id, 'details']" [queryParams]="{cycle: student.cycle}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg" title="Voir dossier">
                      <i class="pi pi-eye"></i>
                    </button>
                    <button (click)="deleteStudent(student)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Supprimer">
                      <i class="pi pi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr *ngIf="filteredStudents().length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  <div class="flex flex-col items-center gap-2">
                    <i class="pi pi-search text-4xl text-gray-300"></i>
                    <p>Aucun élève trouvé</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination (Mock) -->
        <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
          <div>Affichage de {{ filteredStudents().length }} élèves</div>
          <div class="flex gap-2">
            <button class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50" disabled>Précédent</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50" disabled>Suivant</button>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentListComponent implements OnInit {
  private studentService = inject(StudentService);
  
  students = signal<Student[]>([]);
  filteredStudents = signal<Student[]>([]);
  
  searchTerm = '';
  selectedClass = '';
  selectedStatus = '';

  ngOnInit() {
    this.loadStudents();
  }

  loadStudents() {
    this.studentService.getAllStudentsForAdmin().subscribe({
      next: (response: any) => {
        // Handle both formats: { data: [...] } or direct array
        const students = Array.isArray(response) ? response : (response.data || []);
        this.students.set(students);
        this.filterStudents();
      },
      error: () => {
        // Fallback demo data
        this.students.set([
          { id: '1', matricule: 'MAT2024001', firstName: 'Amadou', lastName: 'Ouédraogo', gender: 'M', dateOfBirth: '2010-03-15', currentClass: '6ème A', parentName: 'Paul Ouédraogo', parentPhone: '70112233', status: 'active', photo: '' },
          { id: '2', matricule: 'MAT2024002', firstName: 'Fatou', lastName: 'Traoré', gender: 'F', dateOfBirth: '2011-07-22', currentClass: '5ème A', parentName: 'Marie Traoré', parentPhone: '71223344', status: 'active', photo: '' },
          { id: '3', matricule: 'MAT2024003', firstName: 'Ibrahima', lastName: 'Koné', gender: 'M', dateOfBirth: '2009-11-05', currentClass: '3ème A', parentName: 'Salif Koné', parentPhone: '72334455', status: 'pending', photo: '' }
        ] as any);
        this.filterStudents();
      }
    });
  }

  filterStudents() {
    let result = this.students();

    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      result = result.filter(s => 
        s.lastName.toLowerCase().includes(term) || 
        s.firstName.toLowerCase().includes(term) || 
        (s.matricule && s.matricule.toLowerCase().includes(term))
      );
    }

    if (this.selectedClass) {
      result = result.filter(s => s.currentClass === this.selectedClass);
    }

    if (this.selectedStatus) {
      result = result.filter(s => s.status === this.selectedStatus);
    }

    this.filteredStudents.set(result);
  }

  getStatusClass(status: string): string {
    switch (status) {
      case 'active': return 'px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700';
      case 'pending': return 'px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700';
      case 'excluded': return 'px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700';
      default: return 'px-2 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'active': return 'Actif';
      case 'pending': return 'En attente';
      case 'excluded': return 'Exclu';
      default: return status;
    }
  }

  deleteStudent(student: any) {
    if (confirm(`Voulez-vous vraiment supprimer l'élève ${student.firstName} ${student.lastName} ?`)) {
      this.studentService.deleteStudent(student.id, student.cycle || 'mp').subscribe({
        next: () => {
          this.loadStudents();
        },
        error: (err) => {
          console.error('Erreur suppression', err);
          alert('Erreur lors de la suppression : ' + (err.error?.message || 'Serveur indisponible'));
        }
      });
    }
  }

  editStudent(student: any) {
    // For now, redirect to detail or show a message
    alert('Modification de ' + student.firstName + ' ' + student.lastName + ' (Bientôt disponible)');
  }
}
