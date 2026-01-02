import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-debug-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="p-8 max-w-md mx-auto">
      <h2 class="text-2xl font-bold mb-4">Test de Connexion</h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Email:</label>
          <input 
            [(ngModel)]="email" 
            type="email" 
            class="w-full px-3 py-2 border rounded-md"
            placeholder="admin@college.bf">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Password:</label>
          <input 
            [(ngModel)]="password" 
            type="password" 
            class="w-full px-3 py-2 border rounded-md"
            placeholder="password123">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Role:</label>
          <select [(ngModel)]="role" class="w-full px-3 py-2 border rounded-md">
            <option value="admin">Admin</option>
            <option value="secretary">Secretary</option>
            <option value="accountant">Accountant</option>
            <option value="teacher">Teacher</option>
          </select>
        </div>
        
        <button 
          (click)="testDebug()" 
          class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
          Test Debug
        </button>
        
        <button 
          (click)="testLogin()" 
          class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">
          Test Login
        </button>
      </div>
      
      <div *ngIf="result" class="mt-4 p-4 bg-gray-100 rounded-md">
        <h3 class="font-bold">Résultat:</h3>
        <pre class="text-sm">{{ result | json }}</pre>
      </div>
      
      <div *ngIf="error" class="mt-4 p-4 bg-red-100 rounded-md">
        <h3 class="font-bold text-red-700">Erreur:</h3>
        <pre class="text-sm text-red-600">{{ error | json }}</pre>
      </div>
    </div>
  `
})
export class DebugLoginComponent {
  private http = inject(HttpClient);
  
  email = 'admin@college-abc.bf';
  password = 'password123';
  role = 'admin';
  result: any = null;
  error: any = null;

  testDebug() {
    this.result = null;
    this.error = null;
    
    const payload = {
      email: this.email,
      password: this.password,
      role: this.role
    };

    console.log('Sending debug request:', payload);

    this.http.post('http://localhost:8000/api/debug/login', payload).subscribe({
      next: (response) => {
        console.log('Debug response:', response);
        this.result = response;
      },
      error: (error) => {
        console.error('Debug error:', error);
        this.error = error;
      }
    });
  }

  testLogin() {
    this.result = null;
    this.error = null;
    
    const payload = {
      email: this.email,
      password: this.password,
      role: this.role
    };

    console.log('Sending login request:', payload);

    this.http.post('http://localhost:8000/api/auth/login', payload).subscribe({
      next: (response) => {
        console.log('Login response:', response);
        this.result = response;
      },
      error: (error) => {
        console.error('Login error:', error);
        this.error = error;
      }
    });
  }
}