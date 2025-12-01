import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface EnrollmentRequest {
  student: {
    firstName: string;
    lastName: string;
    birthDate: string;
    gender: string;
    previousSchool: string;
    gradeLevel: string;
  };
  parents: {
    fatherName: string;
    fatherPhone: string;
    motherName: string;
    motherPhone: string;
    email: string;
    address: string;
  };
  documents: {
    reportCards: File | null;
    birthCertificate: File | null;
  };
}

@Injectable({
  providedIn: 'root'
})
export class EnrollmentService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Soumet une demande de pré-inscription.
   */
  submitEnrollment(data: EnrollmentRequest): Observable<any> {
    const formData = new FormData();

    // Append student data
    formData.append('student[firstName]', data.student.firstName);
    formData.append('student[lastName]', data.student.lastName);
    formData.append('student[birthDate]', data.student.birthDate);
    formData.append('student[gender]', data.student.gender);
    formData.append('student[previousSchool]', data.student.previousSchool);
    formData.append('student[gradeLevel]', data.student.gradeLevel);

    // Append parent data
    formData.append('parents[fatherName]', data.parents.fatherName);
    formData.append('parents[fatherPhone]', data.parents.fatherPhone);
    formData.append('parents[motherName]', data.parents.motherName);
    formData.append('parents[motherPhone]', data.parents.motherPhone);
    formData.append('parents[email]', data.parents.email);
    formData.append('parents[address]', data.parents.address);

    // Append files
    if (data.documents.reportCards) {
      formData.append('reportCards', data.documents.reportCards);
    }
    if (data.documents.birthCertificate) {
      formData.append('birthCertificate', data.documents.birthCertificate);
    }

    return this.http.post(`${this.apiUrl}/enroll`, formData);
  }
}
