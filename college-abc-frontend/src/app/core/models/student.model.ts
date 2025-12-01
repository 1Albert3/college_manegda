export interface Student {
    id?: string;
    matricule?: string;
    first_name: string;
    last_name: string;
    date_of_birth: Date;
    gender: 'M' | 'F';
    place_of_birth: string;
    address: string;
    photo?: string;
    status?: string;
    medical_info?: any;
}

export interface Parent {
    id?: string;
    name: string;
    email: string;
    phone: string;
    relationship: 'pere' | 'mere' | 'tuteur';
}
