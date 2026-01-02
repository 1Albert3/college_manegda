import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { ClassService } from '../../../core/services/class.service';
import { StudentService } from '../../../core/services/student.service';

@Component({
  selector: 'app-mp-student-list',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-6">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1" (click)="goBack()">
              <i class="pi pi-arrow-left"></i> Retour aux classes
           </button>
           <h1 class="text-2xl font-bold text-gray-800" *ngIf="classData">
             Classe {{ classData.niveau }} {{ classData.name || classData.nom }}
           </h1>
           <p class="text-gray-600" *ngIf="classData">
             Effectif: <span class="font-medium text-blue-600">{{ students.length }}</span> / {{ classData.seuil_maximum || classData.capacity }} élèves
           </p>
        </div>
        <div class="flex gap-2">
           <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
              <i class="pi pi-print"></i>
              <span>Liste</span>
           </button>
           <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
              <i class="pi pi-user-plus"></i>
              <span>Inscrire Élève</span>
           </button>
        </div>
      </div>

      <!-- Liste Élèves -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                    <th class="px-6 py-4">Matricule</th>
                    <th class="px-6 py-4">Nom Prénoms</th>
                    <th class="px-6 py-4">Date Nais.</th>
                    <th class="px-6 py-4">Sexe</th>
                    <th class="px-6 py-4 text-center">Statut</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr *ngFor="let student of students" class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ student.matricule }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 font-medium">
                        {{ student.nom }} {{ student.prenoms }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ student.date_naissance | date:'dd/MM/yyyy' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span [class]="student.sexe === 'M' ? 'text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-xs' : 'text-pink-600 bg-pink-50 px-2 py-0.5 rounded text-xs'">
                            {{ student.sexe }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Actif
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition-colors mr-2" title="Voir dossier">
                            <i class="pi pi-eye"></i>
                        </button>
                        <button class="text-gray-400 hover:text-red-500 p-1 rounded hover:bg-red-50 transition-colors" title="Retirer">
                            <i class="pi pi-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr *ngIf="students.length === 0">
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="pi pi-users text-4xl text-gray-300 mb-3"></i>
                            <p class="font-medium">Aucun élève dans cette classe</p>
                            <p class="text-sm mt-1">Utilisez le bouton "Inscrire Élève" pour commencer.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
      </div>
    </div>
  `
})
export class MpStudentListComponent implements OnInit {
  classId: string | null = null;
  classData: any = null;
  students: any[] = [];
  loading = true;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private classService: ClassService
  ) {}

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    if (this.classId) {
      this.loadData();
    }
  }

  loadData() {
    this.loading = true;
    // Utilisation de la méthode spécifique qu'on a définie côté backend
    // On doit appeler getStudentsByClass du service.
    // Attention: ClassService.getStudentsByClass pointe vers {cycle}/classes/{id} -> qui renvoie { class: {...}, students: [...] } selon notre controller
    // Modifions ClassService si besoin ou utilisons-le tel quel.
    
    // Pour l'instant on suppose que le service appelle la bonne URL.
    // Notre route API ajoutée est: mp/classes/{id}/students
    // Le service standard fait: get(endpoint, { params })
    
    // Appel direct via HttpClient si le service n'est pas parfaitement aligné, 
    // ou mieux, on utilise le service générique s'il fait un simple GET sur l'url.
    
    // Utilisons classService.getStudentsByClass('mp', this.classId) en supposant qu'il pointe vers la bonne URL 
    // ou on verra après.
    // Dans ClassService: return this.http.get(`${this.apiUrl}/${cycle}/classes/${classId}`); -> CA C'EST SHOW CLASS
    // Mais notre controller pour SHOW CLASS (ClassMPController@show) renvoie 'enrollments.student'.
    // Donc en fait, getStudentsByClass('mp', id) tel que défini dans le ClassService actuel (qui fait un GET sur show) 
    // VA renvoyer la classe AVEC les élèves via la relation.
    
    // MAIS attendez, j'ai ajouté une route spécifique `classes/{class}/students` qui renvoie `{class:..., students: [...]}`.
    // C'est plus propre car la liste est triée et plate.
    // Je vais adapter l'URL d'appel ici ou modifier le service plus tard.
    // Pour l'instant, faisons l'appel manuellement via le service dans une version ad-hoc
    // ou supposons que getStudentsByClass va être modifié pour pointer vers /students
    
    // Pour rester simple et robuste: j'utilise la route 'show' existante du service si elle renvoie déjà les élèves, 
    // OU je hardcode l'appel ici pour l'instant via inject HttpClient? Non, mauvaise pratique.
    
    // Je vais utiliser classService.getStudentsByClass('mp', this.classId) 
    // qui fait un GET sur /mp/classes/{id}.
    // Le controller @show renvoie:
    // $class = ClassMP::with([..., 'enrollments.student'])->findOrFail($id);
    // return response()->json($class);
    // Donc `res.enrollments` contiendra la liste.
    
    this.classService.getStudentsByClass('mp', this.classId!).subscribe({
        next: (data) => {
           // Si on passe par la route 'show' standard
           this.classData = data;
           // Mapping des élèves depuis enrollments ou students direct
           if (data.enrollments) {
               this.students = data.enrollments.map((e: any) => e.student).filter((s: any) => s);
           } else if (data.students) {
               // Si on utilisait la route dédiée
               this.students = data.students;
           }
           
           this.loading = false;
        },
        error: (err) => {
            console.error('Erreur chargement élèves', err);
            this.loading = false;
        }
    });

    // NOTE: Si on voulait utiliser la route optimisée `mp/classes/{id}/students` que j'ai créée,
    // il faudrait une méthode spécifique dans le service.
    // Mais la route 'show' fonctionne aussi si elle inclut enrollments.
  }

  goBack() {
    this.router.navigate(['/admin/mp/classes']);
  }
}
