import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../../environments/environment';

interface Book {
  id: number;
  title: string;
  author: string;
  isbn: string;
  category: string;
  available_copies: number;
  total_copies: number;
  location: string;
  cover_image?: string;
  status: string;
}

@Component({
  selector: 'app-library',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="p-6 space-y-8 animate-in fade-in duration-500">
      <!-- Header with Search -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 class="text-2xl font-black text-gray-900 leading-tight italic uppercase tracking-tighter">Librairie & Bibliothèque</h1>
          <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Gestion du fonds documentaire et des emprunts</p>
        </div>

        <div class="flex items-center gap-3">
           <div class="relative">
              <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input type="text" [(ngModel)]="searchQuery" (input)="loadBooks()" 
                     placeholder="Titre, Auteur ou ISBN..." 
                     class="pl-12 pr-4 py-3 rounded-2xl border-none bg-white shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-indigo-500 w-64 text-sm font-medium">
           </div>
           
           <button class="bg-indigo-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition active:scale-95 group">
              <i class="pi pi-plus group-hover:rotate-90 transition-transform"></i>
           </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
         <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Ouvrages</div>
            <div class="text-3xl font-black text-gray-900 tracking-tighter">1,284</div>
            <div class="mt-2 text-[10px] font-bold text-emerald-600">+12 ce mois</div>
         </div>
         <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Emprunts Actifs</div>
            <div class="text-3xl font-black text-indigo-600 tracking-tighter">42</div>
            <div class="mt-2 text-[10px] font-bold text-gray-400 italic">80% retournés à temps</div>
         </div>
         <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">En Retard</div>
            <div class="text-3xl font-black text-rose-600 tracking-tighter">07</div>
            <div class="mt-2 text-[10px] font-bold text-rose-400 uppercase">Action requise</div>
         </div>
         <div class="bg-indigo-600 p-6 rounded-3xl shadow-lg shadow-indigo-100 text-white relative overflow-hidden flex flex-col justify-center">
            <div class="relative z-10">
               <div class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-70">Top Catégorie</div>
               <div class="text-xl font-black italic">Sciences Nat.</div>
            </div>
            <i class="pi pi-book absolute -bottom-2 -right-2 text-6xl opacity-10 -rotate-12"></i>
         </div>
      </div>

      <!-- Catalog Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
         <div *ngFor="let book of books" class="bg-white rounded-3xl border border-gray-100 p-4 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
            <div class="aspect-[3/4] rounded-2xl bg-gray-100 mb-4 overflow-hidden relative shadow-inner border border-gray-50 flex items-center justify-center text-gray-300">
               <img *ngIf="book.cover_image" [src]="book.cover_image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
               <i *ngIf="!book.cover_image" class="pi pi-image text-4xl"></i>
               
               <div class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-white/90 backdrop-blur-sm text-[8px] font-black uppercase tracking-widest shadow-sm">
                  {{ book.available_copies }} dispos
               </div>
            </div>
            
            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mb-1 block">{{ book.category }}</span>
            <h3 class="font-black text-gray-900 text-sm leading-tight line-clamp-2 min-h-[2.5rem] group-hover:text-indigo-600 transition-colors uppercase italic">{{ book.title }}</h3>
            <p class="text-[10px] text-gray-400 font-bold mt-1 mb-4 truncate">{{ book.author }}</p>
            
            <div class="flex items-center justify-between gap-2 pt-4 border-t border-gray-50">
               <div class="text-[10px] font-black text-gray-700 tracking-tighter italic">{{ book.isbn }}</div>
               <button class="w-8 h-8 rounded-xl bg-gray-50 text-gray-400 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center">
                  <i class="pi pi-external-link text-xs"></i>
               </button>
            </div>
         </div>

         <!-- Loader or empty state -->
         <div *ngIf="books.length === 0 && !loading" class="col-span-full py-20 bg-white rounded-3xl border border-dashed border-gray-200 text-center flex flex-col items-center justify-center">
            <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center text-gray-200 mb-4">
               <i class="pi pi-inbox text-4xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-400 uppercase tracking-tighter italic">Aucun ouvrage ne correspond à votre recherche.</p>
         </div>
      </div>
    </div>
  `
})
export class LibraryComponent implements OnInit {
  books: Book[] = [];
  loading = false;
  searchQuery = '';

  private http = inject(HttpClient);

  ngOnInit() {
    this.loadBooks();
  }

  loadBooks() {
    this.loading = true;
    let params: any = {};
    if (this.searchQuery) params.search = this.searchQuery;

    this.http.get<any>(`${environment.apiUrl}/library`, { params }).subscribe({
      next: (res) => {
        this.books = res.data || [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        // Fallback demo data
        this.books = [
          { id: 1, title: 'L\'Enfant Noir', author: 'Camara Laye', isbn: '978-2266', category: 'Littérature', available_copies: 12, total_copies: 15, location: 'Rayon A1', status: 'active' },
          { id: 2, title: 'Physique-Chimie 3ème', author: 'Collectif', isbn: '978-201', category: 'Solaire', available_copies: 45, total_copies: 50, location: 'Rayon SC2', status: 'active' },
          { id: 3, title: 'Une Si Longue Lettre', author: 'Mariama Bâ', isbn: '978-284', category: 'Littérature', available_copies: 0, total_copies: 10, location: 'Rayon A2', status: 'active' },
          { id: 4, title: 'Bescherelle - La Conjugaison', author: 'Collectif', isbn: '978-221', category: 'Langues', available_copies: 25, total_copies: 25, location: 'Rayon L1', status: 'active' },
          { id: 5, title: 'Dictionnaire Larousse 2024', author: 'Larousse', isbn: '978-203', category: 'Outils', available_copies: 5, total_copies: 8, location: 'Rayon R1', status: 'active' }
        ];
      }
    });
  }
}
