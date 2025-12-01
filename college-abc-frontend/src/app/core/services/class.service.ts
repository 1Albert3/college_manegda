import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';

export interface ClassRoom {
    id: string;
    name: string;
    level: string;
    stream?: string;
    capacity?: number;
}

@Injectable({
    providedIn: 'root'
})
export class ClassService {
    private apiUrl = 'api/academic/classes'; // URL à configurer

    constructor(private http: HttpClient) { }

    getClasses(): Observable<ClassRoom[]> {
        // TODO: Remplacer par un vrai appel HTTP quand le backend sera connecté
        // return this.http.get<ClassRoom[]>(this.apiUrl);

        // Mock data pour le développement frontend
        return of([
            { id: '1', name: '6ème', level: '6eme', capacity: 30 },
            { id: '2', name: '5ème', level: '5eme', capacity: 30 },
            { id: '3', name: '4ème', level: '4eme', capacity: 30 },
            { id: '4', name: '3ème', level: '3eme', capacity: 30 },
            { id: '5', name: '2nde A', level: '2nde', stream: 'A', capacity: 35 },
            { id: '6', name: '2nde C', level: '2nde', stream: 'C', capacity: 35 },
            { id: '7', name: '1ère A', level: '1ere', stream: 'A', capacity: 35 },
            { id: '8', name: '1ère D', level: '1ere', stream: 'D', capacity: 35 },
            { id: '9', name: 'Tle A', level: 'Tle', stream: 'A', capacity: 35 },
            { id: '10', name: 'Tle D', level: 'Tle', stream: 'D', capacity: 35 },
        ]);
    }
}
