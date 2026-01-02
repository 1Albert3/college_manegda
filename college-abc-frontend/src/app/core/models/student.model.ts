export interface Student {
    id?: string;
    matricule?: string;
    firstName: string;
    lastName: string;
    dateOfBirth: Date | string;
    placeOfBirth: string;
    gender: 'M' | 'F';
    address: string;
    photo?: string;
    status: 'active' | 'excluded' | 'transferred' | 'pending';
    
    // Relations
    currentClass?: string; // Nom de la classe (ex: 6ème A)
    parentName?: string;
    parentPhone?: string;
    parentEmail?: string;
    cycle?: 'mp' | 'college' | 'lycee';
}

export interface Parent {
    id?: string;
    fullName: string;
    email: string;
    phone: string;
    relationship: 'pere' | 'mere' | 'tuteur';
}
