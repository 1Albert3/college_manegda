import { Component, signal, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-exams',
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
          <h1 class="text-2xl font-bold text-gray-800">Gestion des Examens</h1>
          <p class="text-gray-500">Organisez les sessions d'examen et générez les convocations</p>
        </div>
        <button (click)="showNewExam = true"
                class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 flex items-center gap-2 font-bold shadow-sm transition">
          <i class="pi pi-plus"></i> Planifier un examen
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
          <p class="text-gray-500 text-sm font-medium">Examens planifiés</p>
          <p class="text-2xl font-bold text-gray-800">{{ upcomingExams().length }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <p class="text-gray-500 text-sm font-medium">En cours</p>
          <p class="text-2xl font-bold text-gray-800">{{ getCountByStatus('ongoing') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <p class="text-gray-500 text-sm font-medium">Terminés</p>
          <p class="text-2xl font-bold text-gray-800">{{ getCountByStatus('completed') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
          <p class="text-gray-500 text-sm font-medium">Convocations à envoyer</p>
          <p class="text-2xl font-bold text-gray-800">{{ pendingConvocations() }}</p>
        </div>
      </div>

      <!-- Upcoming Exams -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-calendar"></i>
            Sessions d'Examens
          </h2>
          <select [(ngModel)]="filterStatus"
                  class="px-3 py-1.5 bg-white/20 text-white rounded-lg text-sm border-0 focus:ring-0 cursor-pointer font-medium">
            <option value="" class="text-gray-800">Tous</option>
            <option value="scheduled" class="text-gray-800">Planifiés</option>
            <option value="ongoing" class="text-gray-800">En cours</option>
            <option value="completed" class="text-gray-800">Terminés</option>
          </select>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr class="text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-4">Examen</th>
                <th class="px-6 py-4">Classes</th>
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4">Durée</th>
                <th class="px-6 py-4">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let exam of filteredExams()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800">{{ exam.title }}</div>
                  <div class="text-sm text-gray-500 font-medium">{{ exam.subject }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span *ngFor="let cls of exam.classes" 
                          class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-full text-xs font-bold">{{ cls }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600 font-medium flex items-center gap-2"><i class="pi pi-calendar text-gray-400"></i>{{ exam.date }}</td>
                <td class="px-6 py-4 text-gray-600 font-medium">{{ exam.duration }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-700': exam.status === 'scheduled',
                          'bg-blue-100 text-blue-700': exam.status === 'ongoing',
                          'bg-green-100 text-green-700': exam.status === 'completed'
                        }">
                    {{ getStatusLabel(exam.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Convocations">
                      <i class="pi pi-file"></i>
                    </button>
                    <button class="p-2 text-teal-600 hover:bg-teal-50 rounded-lg transition" title="Salles">
                      <i class="pi pi-building"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:bg-gray-50 rounded-lg transition" title="Modifier">
                      <i class="pi pi-pencil"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- New Exam Modal -->
      <div *ngIf="showNewExam" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewExam = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-teal-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Planifier un Examen</h2>
            <button (click)="showNewExam = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="createExam()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Titre de l'examen</label>
              <input type="text" [(ngModel)]="newExam.title" name="title" placeholder="Ex: Examen de fin de trimestre"
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Matière</label>
                <select [(ngModel)]="newExam.subject" name="subject"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 bg-white transition">
                  <option>Mathématiques</option>
                  <option>Français</option>
                  <option>Anglais</option>
                  <option>Histoire-Géo</option>
                  <option>SVT</option>
                  <option>Physique-Chimie</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Durée</label>
                <select [(ngModel)]="newExam.duration" name="duration"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 bg-white transition">
                  <option>1h</option>
                  <option>1h30</option>
                  <option>2h</option>
                  <option>3h</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Date</label>
                <input type="date" [(ngModel)]="newExam.date" name="date"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Heure</label>
                <input type="time" [(ngModel)]="newExam.time" name="time"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Classes concernées</label>
              <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto border border-gray-200 rounded-xl p-3 bg-gray-50">
                <label *ngFor="let cls of availableClasses" class="flex items-center gap-2 px-3 py-2 border border-gray-200 bg-white rounded-lg cursor-pointer hover:border-teal-500 transition">
                  <input type="checkbox" [value]="cls" class="rounded text-teal-600 focus:ring-teal-500">
                  <span class="text-sm font-medium text-gray-700">{{ cls }}</span>
                </label>
              </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showNewExam = false"
                      class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
              <button type="submit"
                      class="px-5 py-2.5 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition shadow-lg shadow-teal-200">Planifier</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `
})
export class SecretaryExamsComponent implements OnInit {
  private http = inject(HttpClient);
  
  showNewExam = false;
  filterStatus = '';
  
  showSuccessToast = false;
  successMessage = '';

  availableClasses: string[] = [];
  
  newExam = { title: '', subject: 'Mathématiques', duration: '2h', date: '', time: '08:00', classes: [] };
  
  upcomingExams = signal<any[]>([]);

  pendingConvocations = signal(45);

  ngOnInit() {
    this.loadClasses();
  }

  loadClasses() {
    this.http.get<any[]>(`${environment.apiUrl}/academic/classrooms`).subscribe({
      next: (data) => {
        this.availableClasses = data.map(c => c.name);
      },
      error: (err) => console.error('Error loading classes for exams:', err)
    });
  }

  getCountByStatus(status: string): number {
    return this.upcomingExams().filter(e => e.status === status).length;
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'scheduled': 'Planifié', 'ongoing': 'En cours', 'completed': 'Terminé'
    };
    return labels[status] || status;
  }

  filteredExams = () => {
    if (!this.filterStatus) return this.upcomingExams();
    return this.upcomingExams().filter(e => e.status === this.filterStatus);
  };

  createExam() {
    this.showToast('Examen planifié avec succès !');
    this.showNewExam = false;
    this.newExam = { title: '', subject: 'Mathématiques', duration: '2h', date: '', time: '08:00', classes: [] };
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
