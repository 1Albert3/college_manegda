import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-grade-list',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="container mx-auto px-4 py-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 font-serif">Gestion des Notes</h2>
        <a routerLink="/admin/grades/entry" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg shadow-lg transition-all flex items-center gap-2">
          <i class="pi pi-plus"></i> Saisir des notes
        </a>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm p-8 text-center">
        <p class="text-gray-500 text-lg">Le tableau de bord des notes sera disponible bientôt.</p>
        <p class="text-gray-400 mt-2">Commencez par saisir des notes via le bouton ci-dessus.</p>
      </div>
    </div>
  `
})
export class GradeListComponent {}
